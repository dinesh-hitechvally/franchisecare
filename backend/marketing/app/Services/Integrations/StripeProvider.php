<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/**
 * Stripe customers: imported as People, and People can be sent to Stripe as customers (a restricted API key needs Customers: Read to import,
 * Write to send; connecting with one click covers both).
 */
class StripeProvider extends Provider
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function category(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return 'Import the customers of your Stripe account (people who paid you or subscribed), and optionally send your People to it as customers.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'secret_key', 'label' => 'Restricted API key', 'type' => 'password', 'placeholder' => 'rk_live_…', 'help' => 'Created in the Stripe dashboard under Developers → API keys (see the steps). Customers: Read is enough to import; to send people to Stripe it also needs Write.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the Stripe dashboard open Developers → API keys and choose Create restricted key.',
            'Give it a name, set Customers to Read (choose Write as well if you want to send your People to Stripe), and leave everything else on None.',
            'Create the key and copy it (it starts with rk_ and is shown only once).',
            'Paste the key here. Use a restricted key rather than your secret key: it can only reach customers.',
        ];
    }

    public function autoConnect(): ?array
    {
        // read_write, although the app only ever reads customers: Stripe refuses read_only to a Connect platform it has not approved for it
        // ("Please use the read_write scope"), and a platform cannot tell beforehand whether it has been.
        return ['mode' => 'oauth', 'authorize' => 'https://connect.stripe.com/oauth/authorize', 'token' => 'https://connect.stripe.com/oauth/token', 'scopes' => ['read_write'], 'token_secret' => 'secret_key', 'client_auth' => 'bearer'];
    }

    public function scopeNotes(): array
    {
        return ['read_write' => ['why' => 'Read your customers to import them, and add your People as customers when sending. (Stripe does not offer a read-only connection to new platforms.)', 'for' => ['import', 'send']]];
    }

    public function checksScopes(): bool
    {
        return true;
    }

    /**
     * Whether the connection may write. A customer with an invalid email is asked for: Stripe turns it down as invalid (400) when writing is
     * allowed, and with a permission error (403) when it is not, so no customer is ever created.
     */
    public function checkScopes(Integration $integration): array
    {
        $response = $this->post($integration, '/customers', ['email' => 'not-an-email']);

        return ['read_write' => match (true) {
            $response->status() === 401 => throw new IntegrationException('Stripe no longer accepts this connection. Reconnect it.'),
            $response->status() === 403 => false,
            $response->serverError() => null,
            default => true,
        }];
    }

    /** Stripe Connect names them differently: the client id of the Connect app and the platform's secret key. */
    public function oauthEnv(): array
    {
        return [
            [
                'name' => 'STRIPE_CONNECT_CLIENT_ID', 
                'example' => 'ca_xxxxxxxxxxxxxxxxxxxxxxxx'
            ], 
            [
                'name' => 'STRIPE_CONNECT_SECRET_KEY', 
                'example' => 'sk_live_xxxxxxxxxxxxxxxxxxxxxxxx'
            ]
        ];
    }

    protected function oauthAccount(array $tokens): ?string
    {
        return $tokens['raw']['stripe_user_id'] ?? null;
    }

    public function consentNote(): string
    {
        return 'Stripe does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Paying customers are not automatically people who agreed to marketing: choose “Everyone” only if you have their permission.';
    }

    public function supportsExport(): bool
    {
        return true;
    }

    public function exportNote(): string
    {
        return 'Send adds your People to Stripe as customers, matched by email so nobody is added twice. A customer already in Stripe is only completed (a blank name or phone is filled, nothing is overwritten) and is never deleted. Whether the person is subscribed here is saved on the customer as the metadata field marketing_subscribed (yes or no), because Stripe has no marketing permission of its own.';
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://api.stripe.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'secret_key'));
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', 'https://api.stripe.com/v1'.$path, ['headers' => ['Authorization' => 'Bearer '.$this->secret($i, 'secret_key')], 'query' => $query]);
        if (! $response->successful()) {
            throw $this->failed($response, 'Stripe refused the key. Check it, and that it has read access to Customers.', 'Stripe could not find that.');
        }

        return $response;
    }

    /** A write to the Stripe API (it takes form-encoded bodies, not JSON). */
    protected function post(Integration $i, string $path, array $form, array $headers = [])
    {
        return $this->send($i, 'POST', 'https://api.stripe.com/v1'.$path, ['headers' => ['Authorization' => 'Bearer '.$this->secret($i, 'secret_key')] + $headers, 'form' => $form]);
    }

    /**
     * Adds or completes each person as a Stripe customer. The customer is found by email; a new one is created, an existing one only gets
     * its blank name / phone filled. The subscription is kept in the customer's metadata (marketing_subscribed = yes / no).
     */
    public function export(Integration $integration, iterable $people): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'error' => null, 'results' => []];
        $denied = 'Stripe refused the key. Sending people needs Write access to Customers: use a key with Customers set to Write, or reconnect with one click.';

        foreach ($people as $person) {
            $email = mb_strtolower(trim((string) $person->email));
            if ($email === '') {
                $stats['skipped']++;
                $stats['results'][$person->id] = ['status' => 'skipped', 'external_id' => null, 'message' => 'No email address.'];

                continue;
            }
            $name = trim($person->first_name.' '.$person->last_name);
            $phone = trim((string) $person->phone);
            $meta = ['marketing_subscribed' => $person->is_subscribed ? 'yes' : 'no'];

            $found = $this->send($integration, 'GET', 'https://api.stripe.com/v1/customers', ['headers' => ['Authorization' => 'Bearer '.$this->secret($integration, 'secret_key')], 'query' => ['email' => $email, 'limit' => 1]]);
            if (in_array($found->status(), [401, 403], true)) {
                throw $this->failed($found, $denied, '');
            }
            $customer = $found->successful() ? $found->json('data.0') : null;

            if ($found->successful() && $customer) {
                $fill = array_filter(['name' => empty($customer['name']) ? $name : '', 'phone' => empty($customer['phone']) ? $phone : ''], fn ($v) => $v !== '');
                $response = $this->post($integration, '/customers/'.$customer['id'], $fill + ['metadata' => $meta]);
                $done = $fill ? 'Already a customer: filled in '.implode(' and ', array_keys($fill)).'.' : 'Already a customer: up to date.';
            } elseif ($found->successful()) {
                // an idempotency key makes a retried request return the first answer instead of creating a second customer
                $response = $this->post($integration, '/customers', array_filter(['email' => $email, 'name' => $name, 'phone' => $phone], fn ($v) => $v !== '') + ['metadata' => $meta], ['Idempotency-Key' => 'fc-person-'.$person->id.'-'.md5($email)]);
                $done = 'Created as a customer.';
            } else {
                $response = $found; // the lookup itself failed: report it below
            }

            if ($response->successful()) {
                $stats['sent']++;
                $stats['results'][$person->id] = ['status' => 'sent', 'external_id' => (string) ($response->json('id') ?: ($customer['id'] ?? '')), 'message' => $done];
            } elseif (in_array($response->status(), [401, 403], true)) {
                throw $this->failed($response, $denied, '');
            } else {
                $message = (string) ($response->json('error.message') ?? 'Stripe refused this person.');
                $stats['failed']++;
                $stats['error'] ??= $message;
                $stats['results'][$person->id] = ['status' => 'failed', 'external_id' => null, 'message' => $message];
            }
        }

        return $stats;
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['limit' => 1]);

        try { // the business name is a nicety: a restricted key may not be allowed to read the account
            $account = $this->get($integration, '/account')->json();
            $name = $account['business_profile']['name'] ?? $account['settings']['dashboard']['display_name'] ?? null;
        } catch (IntegrationException) {
            $name = null;
        }

        return (string) ($name ?: 'Stripe account');
    }

    /** The cursor is the id of the last customer of the page (Stripe's own paging). Only new customers are asked for after the first import. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $body = $this->get($integration, '/customers', array_filter([
            'limit' => $limit, 'starting_after' => $cursor, 'created' => $since ? ['gte' => $since->getTimestamp()] : null,
        ], fn ($v) => $v !== null))->json() ?? [];

        $customers = [];
        $last = null;
        foreach ($body['data'] ?? [] as $c) {
            $last = $c['id'] ?? $last;
            [$first, $surname] = array_pad(explode(' ', trim((string) ($c['name'] ?? '')), 2), 2, null);
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $first, $surname, $c['phone'] ?? null, null);
        }

        return [$customers, ! empty($body['has_more']) && $last ? $last : null];
    }
}

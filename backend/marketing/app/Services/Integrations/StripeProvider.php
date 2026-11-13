<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Stripe customers, through a restricted API key with read access to Customers. */
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
        return 'Import the customers of your Stripe account (people who paid you or subscribed).';
    }

    public function fields(): array
    {
        return [
            ['key' => 'secret_key', 'label' => 'Restricted API key', 'type' => 'password', 'placeholder' => 'rk_live_…', 'help' => 'Created in the Stripe dashboard under Developers → API keys (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the Stripe dashboard open Developers → API keys and choose Create restricted key.',
            'Give it a name, set Customers to Read, and leave everything else on None.',
            'Create the key and copy it (it starts with rk_ and is shown only once).',
            'Paste the key here. Use a restricted key rather than your secret key: it can only read customers.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://connect.stripe.com/oauth/authorize', 'token' => 'https://connect.stripe.com/oauth/token', 'scopes' => ['read_only'], 'token_secret' => 'secret_key', 'client_auth' => 'bearer'];
    }

    protected function oauthAccount(array $tokens): ?string
    {
        return $tokens['raw']['stripe_user_id'] ?? null;
    }

    public function consentNote(): string
    {
        return 'Stripe does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Paying customers are not automatically people who agreed to marketing: choose “Everyone” only if you have their permission.';
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

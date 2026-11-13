<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Klaviyo profiles, through a private API key (scopes: profiles:read, subscriptions:read, accounts:read). */
class KlaviyoProvider extends Provider
{
    public function key(): string
    {
        return 'klaviyo';
    }

    public function label(): string
    {
        return 'Klaviyo';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import your Klaviyo profiles with their email marketing consent.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'private_key', 'label' => 'Private API key', 'type' => 'password', 'placeholder' => 'pk_…', 'help' => 'Created under Settings → Account → API keys.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Klaviyo open Settings → Account → API keys and choose Create Private API Key.',
            'Name it (for example “FranchiseCare Marketing”), choose Custom Key and give it Read access to Profiles, Subscriptions and Accounts.',
            'Create the key and copy it (it starts with pk_ and is shown only once).',
            'Paste the key here.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://www.klaviyo.com/oauth/authorize', 'token' => 'https://a.klaviyo.com/oauth/token', 'scopes' => ['accounts:read', 'profiles:read', 'subscriptions:read'], 'token_secret' => 'private_key', 'client_auth' => 'basic', 'pkce' => true];
    }

    public function consentNote(): string
    {
        return 'Klaviyo records each profile’s email marketing consent: profiles that are subscribed are subscribed here, profiles that unsubscribed stay unsubscribed, and profiles that never subscribed are imported but not subscribed.';
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://a.klaviyo.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'private_key'));
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', 'https://a.klaviyo.com/api'.$path, [
            'headers' => ['Authorization' => ($this->secret($i, 'oauth') ? 'Bearer ' : 'Klaviyo-API-Key ').$this->secret($i, 'private_key'), 'revision' => config('services.integrations.klaviyo.api_version', '2025-04-15'), 'Accept' => 'application/vnd.api+json'],
            'query' => $query,
        ]);
        if (! $response->successful()) {
            throw $this->failed($response, 'Klaviyo refused the key. Check it, and that it can read Profiles, Subscriptions and Accounts.', 'Klaviyo could not find that. Check the key is a private API key (it starts with pk_).');
        }

        return $response;
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/profiles/', ['page[size]' => 1]);

        try { // the account's name is a nicety: a key without the accounts scope still works
            $name = $this->get($integration, '/accounts/')->json('data.0.attributes.contact_information.organization_name');
        } catch (IntegrationException) {
            $name = null;
        }

        return (string) ($name ?: 'Klaviyo account');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $size = 100): array
    {
        $query = array_filter([
            'page[size]' => $size, 'page[cursor]' => $cursor, 'additional-fields[profile]' => 'subscriptions',
            'filter' => $since ? 'greater-than(updated,'.$since->copy()->utc()->format('Y-m-d\TH:i:s\Z').')' : null,
        ], fn ($v) => $v !== null);
        $body = $this->get($integration, '/profiles/', $query)->json() ?? [];

        $customers = [];
        foreach ($body['data'] ?? [] as $p) {
            $a = $p['attributes'] ?? [];
            $consent = match ($a['subscriptions']['email']['marketing']['consent'] ?? null) {
                'SUBSCRIBED' => 'subscribed',
                'UNSUBSCRIBED' => 'unsubscribed',
                null => null,
                default => 'none', // never subscribed
            };
            $customers[] = $this->customer($p['id'] ?? '', $a['email'] ?? null, $a['first_name'] ?? null, $a['last_name'] ?? null, $a['phone_number'] ?? null, $consent);
        }

        // the next page is a full address: the cursor is inside it
        $next = null;
        if ($url = $body['links']['next'] ?? null) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            $next = $q['page']['cursor'] ?? null;
        }

        return [$customers, $next ? (string) $next : null];
    }
}

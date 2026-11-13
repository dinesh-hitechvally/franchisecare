<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Pipedrive people (contacts), through a personal API token. */
class PipedriveProvider extends ApiProvider
{
    public function key(): string
    {
        return 'pipedrive';
    }

    public function label(): string
    {
        return 'Pipedrive';
    }

    public function category(): string
    {
        return 'crm';
    }

    public function description(): string
    {
        return 'Import the people (contacts) of your Pipedrive CRM, with their marketing status.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_token', 'label' => 'Personal API token', 'type' => 'password', 'placeholder' => 'API token', 'help' => 'From your Pipedrive personal preferences (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Pipedrive open your profile menu → Personal preferences → API.',
            'Copy your personal API token.',
            'Paste it here. It can read what you can see in Pipedrive, so use the account of someone who may see all contacts.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://oauth.pipedrive.com/oauth/authorize', 'token' => 'https://oauth.pipedrive.com/oauth/token', 'token_secret' => 'api_token', 'client_auth' => 'basic'];
    }

    /** Pipedrive tells each company its own API address. */
    public function oauthConnect(Integration $integration, array $tokens, array $input): void
    {
        parent::oauthConnect($integration, $tokens, $input);
        $domain = rtrim((string) ($tokens['raw']['api_domain'] ?? ''), '/');
        if (! preg_match('~^https://[a-z0-9-]+\.pipedrive\.com$~i', $domain)) {
            throw new IntegrationException('Pipedrive did not say which address your account uses. Please try again.');
        }
        $settings = $integration->settings ?? [];
        $settings['config']['api_domain'] = $domain;
        $integration->settings = $settings;
    }

    public function consentNote(): string
    {
        return 'Pipedrive keeps a marketing status per person. Subscribed people are subscribed here; people who unsubscribed stay unsubscribed; the rest are imported but not subscribed.';
    }

    protected function api(Integration $i): string
    {
        return rtrim($this->option($i, 'api_domain', 'https://api.pipedrive.com'), '/').'/v1';
    }

    protected function headers(Integration $i): array
    {
        $token = $this->secret($i, 'api_token');

        return $this->secret($i, 'oauth') ? ['Authorization' => 'Bearer '.$token] : ['x-api-token' => $token];
    }

    protected function refused(): string
    {
        return 'Pipedrive refused the token. Copy it again from Personal preferences → API.';
    }

    public function test(Integration $integration): string
    {
        $me = $this->get($integration, '/users/me');

        return (string) ($me['data']['company_name'] ?? $me['data']['name'] ?? 'Pipedrive');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $start = max(0, (int) $cursor);
        $body = $this->get($integration, '/persons', ['start' => $start, 'limit' => $limit, 'sort' => 'id ASC']);

        $customers = [];
        foreach ($body['data'] ?? [] as $p) {
            $customers[] = $this->customer(
                $p['id'] ?? '', $this->firstOf($p['email'] ?? [], 'value'), $p['first_name'] ?? null, $p['last_name'] ?? null, $this->firstOf($p['phone'] ?? [], 'value'),
                match ($p['marketing_status'] ?? null) { 'subscribed' => 'subscribed', 'unsubscribed' => 'unsubscribed', 'no_consent' => 'none', default => null },
            );
        }
        $page = $body['additional_data']['pagination'] ?? [];

        return [$customers, ! empty($page['more_items_in_collection']) ? (string) ($page['next_start'] ?? $start + $limit) : null];
    }
}

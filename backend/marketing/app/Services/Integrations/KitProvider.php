<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Kit (formerly ConvertKit) subscribers, through a v4 API key. */
class KitProvider extends ApiProvider
{
    public function key(): string
    {
        return 'kit';
    }

    public function label(): string
    {
        return 'Kit (ConvertKit)';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import your Kit (ConvertKit) subscribers. Unsubscribed and bounced subscribers stay unsubscribed.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'V4 API key', 'type' => 'password', 'placeholder' => 'kit_…', 'help' => 'Created under Settings → Developer (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Kit open Settings → Developer and, under API Keys, choose Add a new key (a V4 key).',
            'Name it and copy the key.',
            'Paste it here.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://app.kit.com/oauth/authorize', 'token' => 'https://api.kit.com/v4/oauth/token', 'token_secret' => 'api_key', 'token_format' => 'json'];
    }

    public function consentNote(): string
    {
        return 'Kit subscribers with the status “active” are subscribed here. Unsubscribed, bounced, complained and cancelled subscribers are unsubscribed.';
    }

    protected function api(Integration $i): string
    {
        return 'https://api.kit.com/v4';
    }

    protected function headers(Integration $i): array
    {
        $key = $this->secret($i, 'api_key');

        return $this->secret($i, 'oauth') ? ['Authorization' => 'Bearer '.$key] : ['X-Kit-Api-Key' => $key];
    }

    protected function refused(): string
    {
        return 'Kit refused the key. Use a V4 API key from Settings → Developer.';
    }

    public function test(Integration $integration): string
    {
        $account = $this->get($integration, '/account');

        return (string) ($account['account']['name'] ?? $account['user']['email'] ?? 'Kit account');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 500): array
    {
        $body = $this->get($integration, '/subscribers', array_filter([
            'per_page' => $limit, 'after' => $cursor, 'status' => 'all', 'updated_after' => $this->stamp($since),
        ]));

        $customers = [];
        foreach ($body['subscribers'] ?? [] as $s) {
            $customers[] = $this->customer($s['id'] ?? '', $s['email_address'] ?? null, $s['first_name'] ?? null, null, null, ($s['state'] ?? 'active') === 'active' ? 'subscribed' : 'unsubscribed');
        }
        $page = $body['pagination'] ?? [];

        return [$customers, ! empty($page['has_next_page']) ? ($page['end_cursor'] ?? null) : null];
    }
}

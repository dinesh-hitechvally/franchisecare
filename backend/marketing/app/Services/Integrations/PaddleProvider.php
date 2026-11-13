<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Paddle Billing customers, through an API key with the Customers read permission. */
class PaddleProvider extends ApiProvider
{
    public function key(): string
    {
        return 'paddle';
    }

    public function label(): string
    {
        return 'Paddle';
    }

    public function category(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return 'Import the customers of your Paddle Billing account, with whether they agreed to marketing.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'pdl_live_apikey_…', 'help' => 'Created under Developer tools → Authentication (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Paddle open Developer tools → Authentication and choose New API key.',
            'Name it and give it the Customers “Read” permission only.',
            'Copy the key (shown once) and paste it here. Sandbox keys work against the Paddle sandbox.',
        ];
    }

    public function consentNote(): string
    {
        return 'Paddle records whether a customer agreed to marketing. Those customers are subscribed here; the others are imported but not subscribed. Choose “Everyone” only if you have permission.';
    }

    protected function api(Integration $i): string
    {
        return str_contains($this->secret($i, 'api_key'), '_sdbx_') ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'api_key')];
    }

    protected function refused(): string
    {
        return 'Paddle refused the key. Check it, and that it can read Customers.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['per_page' => 1]);

        return str_contains($this->api($integration), 'sandbox') ? 'Paddle sandbox' : 'Paddle account';
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 200): array
    {
        $body = $this->get($integration, '/customers', array_filter(['per_page' => $limit, 'after' => $cursor, 'order_by' => 'id[ASC]']));

        $customers = [];
        $last = null;
        foreach ($body['data'] ?? [] as $c) {
            $last = $c['id'] ?? $last;
            [$first, $surname] = $this->split($c['name'] ?? null);
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $first, $surname, null, ! empty($c['marketing_consent']) ? 'subscribed' : 'none');
        }

        return [$customers, ! empty($body['meta']['pagination']['has_more']) ? $last : null];
    }
}

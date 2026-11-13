<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Square customers (the customer directory), through an access token with the Customers read permission. */
class SquareProvider extends ApiProvider
{
    public function key(): string
    {
        return 'square';
    }

    public function label(): string
    {
        return 'Square';
    }

    public function category(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return 'Import the customers in your Square customer directory.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => 'EAAA…', 'help' => 'From the Square Developer dashboard (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'Open the Square Developer dashboard (developer.squareup.com), and create an application (or open one).',
            'Open its Credentials page, choose Production, and copy the Access token. The application needs the CUSTOMERS_READ permission.',
            'Paste the token here.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://connect.squareup.com/oauth2/authorize', 'token' => 'https://connect.squareup.com/oauth2/token', 'scopes' => ['CUSTOMERS_READ', 'MERCHANT_PROFILE_READ'], 'authorize_params' => ['session' => 'false'], 'token_secret' => 'access_token', 'token_format' => 'json'];
    }

    protected function oauthAccount(array $tokens): ?string
    {
        return $tokens['raw']['merchant_id'] ?? null;
    }

    public function consentNote(): string
    {
        return 'Square records customers who unsubscribed from email: they stay unsubscribed here. It does not record who agreed to marketing, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have permission.';
    }

    protected function api(Integration $i): string
    {
        return 'https://connect.squareup.com/v2';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token'), 'Square-Version' => '2025-01-23'];
    }

    protected function refused(): string
    {
        return 'Square refused the token. Use a Production access token that can read customers.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['limit' => 1]);
        try {
            $merchant = $this->get($integration, '/merchants/me');
        } catch (IntegrationException) {
            $merchant = [];
        }

        return (string) ($merchant['merchant']['business_name'] ?? 'Square account');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $body = $this->get($integration, '/customers', array_filter(['limit' => $limit, 'cursor' => $cursor, 'sort_field' => 'CREATED_AT', 'sort_order' => 'ASC']));

        $customers = [];
        foreach ($body['customers'] ?? [] as $c) {
            $customers[] = $this->customer(
                $c['id'] ?? '', $c['email_address'] ?? null, $c['given_name'] ?? null, $c['family_name'] ?? null, $c['phone_number'] ?? null,
                ! empty($c['preferences']['email_unsubscribed']) ? 'unsubscribed' : null,
            );
        }

        return [$customers, $body['cursor'] ?? null];
    }
}

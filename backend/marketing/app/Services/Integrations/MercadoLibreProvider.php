<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Mercado Libre: the buyers of the seller's orders, with the name, phone and the email Mercado Libre gives for each order. */
class MercadoLibreProvider extends MarketplaceProvider
{
    public function key(): string
    {
        return 'mercadolibre';
    }

    public function label(): string
    {
        return 'Mercado Libre';
    }

    public function description(): string
    {
        return 'Import the buyers of your Mercado Libre orders, with their name, phone and the email Mercado Libre gives.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'App ID', 'type' => 'text', 'placeholder' => '1234567890123456', 'help' => 'From your application at developers.mercadolibre.com (Your applications).', 'secret' => false],
            ['key' => 'client_secret', 'label' => 'Secret key', 'type' => 'password', 'placeholder' => 'Secret key', 'help' => 'From the same application.', 'secret' => true],
            ['key' => 'refresh_token', 'label' => 'Refresh token', 'type' => 'password', 'placeholder' => 'TG-…', 'help' => 'Created when you sign in to your own application as the seller (see the steps). It can be used once; the connection keeps the new one it is given each time.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'At developers.mercadolibre.com create an application (with the seller’s account) and set any redirect URL for it.',
            'Open the authorization page of your country with your App ID, sign in as the seller and approve it; the address you are sent back to carries a code.',
            'Exchange the code for tokens (POST https://api.mercadolibre.com/oauth/token with grant_type=authorization_code, your App ID and secret, the code and the redirect URL) and copy the refresh_token.',
            'Paste the App ID, the secret and the refresh token here. The app needs offline access so that the refresh token is issued.',
        ];
    }

    protected function api(Integration $i): string
    {
        return 'https://api.mercadolibre.com';
    }

    protected function token(Integration $i): string
    {
        return $this->minted($i, $this->api($i).'/oauth/token', [
            'grant_type' => 'refresh_token', 'client_id' => $this->option($i, 'client_id'), 'client_secret' => $this->secret($i, 'client_secret'), 'refresh_token' => $this->secret($i, 'refresh_token'),
        ]);
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->token($i)];
    }

    protected function refused(): string
    {
        return 'Mercado Libre refused the credentials. Check the App ID, the secret and the refresh token (a refresh token works only once; create a new one if it was used elsewhere).';
    }

    public function test(Integration $integration): string
    {
        $me = $this->get($integration, '/users/me');

        return (string) ($me['nickname'] ?? 'Mercado Libre seller');
    }

    /** The cursor is the offset into the orders. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 50): array
    {
        $offset = (int) $cursor;
        $seller = $this->get($integration, '/users/me')['id'] ?? null;
        $body = $this->get($integration, '/orders/search', array_filter([
            'seller' => $seller, 'sort' => 'date_asc', 'limit' => $limit, 'offset' => $offset,
            'order.date_last_updated.from' => $this->stamp($since, 'Y-m-d\TH:i:s.000-00:00'),
        ], fn ($v) => $v !== null));

        $customers = [];
        foreach ($body['results'] ?? [] as $o) {
            $b = $o['buyer'] ?? [];
            $phone = trim(($b['phone']['area_code'] ?? '').' '.($b['phone']['number'] ?? ''));
            $customers[] = $this->customer($b['id'] ?? $o['id'] ?? '', $b['email'] ?? null, $b['first_name'] ?? null, $b['last_name'] ?? null, $phone !== '' ? $phone : null, null);
        }

        return [$customers, $offset + $limit < (int) ($body['paging']['total'] ?? 0) ? (string) ($offset + $limit) : null];
    }
}

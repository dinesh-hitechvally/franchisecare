<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Etsy: the buyers of the shop's receipts (orders), with their name and email. */
class EtsyProvider extends MarketplaceProvider
{
    public function key(): string
    {
        return 'etsy';
    }

    public function label(): string
    {
        return 'Etsy';
    }

    public function description(): string
    {
        return 'Import the buyers of your Etsy shop’s orders, with their name and email.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key (keystring:shared secret)', 'type' => 'password', 'placeholder' => 'keystring:sharedsecret', 'help' => 'From your app at etsy.com/developers/your-apps: the keystring and the shared secret, joined with a colon.', 'secret' => true],
            ['key' => 'refresh_token', 'label' => 'Refresh token', 'type' => 'password', 'placeholder' => '12345678.abc…', 'help' => 'The refresh token you get when you approve your own app with the transactions_r permission (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'Create an app at etsy.com/developers/your-apps (the shop owner’s Etsy account) and wait for it to be approved.',
            'Copy its keystring and shared secret, and paste them here joined with a colon: keystring:sharedsecret.',
            'Approve your app once with the transactions_r permission (Etsy’s OAuth 2.0 page) and copy the refresh token it returns. It is valid for 90 days and is renewed each time the connection syncs.',
            'Easier: use “Connect” instead, which does all of this for you once the server has the Etsy app’s keys.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://www.etsy.com/oauth/connect', 'token' => 'https://api.etsy.com/v3/public/oauth/token', 'scopes' => ['transactions_r', 'shops_r'], 'token_secret' => 'access_token', 'pkce' => true];
    }

    protected function api(Integration $i): string
    {
        return 'https://api.etsy.com/v3/application';
    }

    /** Etsy wants the app's keystring and shared secret on every request. */
    protected function apiKey(Integration $i): string
    {
        return $this->secret($i, 'api_key') ?: (string) config('services.integrations.etsy.client_id').':'.(string) config('services.integrations.etsy.client_secret');
    }

    protected function token(Integration $i): string
    {
        if (! empty(($i->secrets ?? [])['oauth'])) {
            return $this->secret($i, 'access_token');
        }

        return $this->minted($i, 'https://api.etsy.com/v3/public/oauth/token', [
            'grant_type' => 'refresh_token', 'client_id' => strtok($this->apiKey($i), ':'), 'refresh_token' => $this->secret($i, 'refresh_token'),
        ]);
    }

    protected function headers(Integration $i): array
    {
        return ['x-api-key' => $this->apiKey($i), 'Authorization' => 'Bearer '.$this->token($i)];
    }

    protected function refused(): string
    {
        return 'Etsy refused the credentials. Check the API key and that the app was approved with the transactions_r permission.';
    }

    /** The shop of the signed-in seller. */
    protected function shop(Integration $i): int
    {
        $me = $this->get($i, '/users/me');
        if (empty($me['shop_id'])) {
            throw new IntegrationException('This Etsy account has no shop. Connect the shop owner’s account.');
        }

        return (int) $me['shop_id'];
    }

    public function test(Integration $integration): string
    {
        $shop = $this->get($integration, '/shops/'.$this->shop($integration));

        return (string) ($shop['shop_name'] ?? 'Etsy shop');
    }

    /** The cursor is the offset into the receipts. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = (int) $cursor;
        $body = $this->get($integration, '/shops/'.$this->shop($integration).'/receipts', array_filter([
            'limit' => $limit, 'offset' => $offset, 'min_last_modified' => $since?->getTimestamp(),
        ], fn ($v) => $v !== null));

        $customers = [];
        foreach ($body['results'] ?? [] as $r) {
            [$first, $last] = $this->split($r['name'] ?? null);
            $customers[] = $this->customer($r['buyer_user_id'] ?? $r['receipt_id'] ?? '', $r['buyer_email'] ?? null, $first, $last, null, null);
        }

        return [$customers, $offset + $limit < (int) ($body['count'] ?? 0) ? (string) ($offset + $limit) : null];
    }
}

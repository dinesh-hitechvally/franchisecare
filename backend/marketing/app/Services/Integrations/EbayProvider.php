<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** eBay: the buyers of the seller's orders (Sell Fulfillment API), with the contact details eBay gives for the order. */
class EbayProvider extends MarketplaceProvider
{
    protected const SCOPE = 'https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly';

    protected const TOKEN_URL = 'https://api.ebay.com/identity/v1/oauth2/token';

    public function key(): string
    {
        return 'ebay';
    }

    public function label(): string
    {
        return 'eBay';
    }

    public function description(): string
    {
        return 'Import the buyers of your eBay orders, with the name, email and phone eBay gives for each order.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'App ID (client ID)', 'type' => 'text', 'placeholder' => 'YourName-App-PRD-xxxxxxxx-xxxxxxxx', 'help' => 'From your keyset on developer.ebay.com → Application Keys (Production).', 'secret' => false],
            ['key' => 'client_secret', 'label' => 'Cert ID (client secret)', 'type' => 'password', 'placeholder' => 'PRD-xxxxxxxxxxxx-xxxx-xxxx', 'help' => 'From the same keyset.', 'secret' => true],
            ['key' => 'refresh_token', 'label' => 'Refresh token', 'type' => 'password', 'placeholder' => 'v^1.1#i^1#…', 'help' => 'The refresh token you get when you sign in to your own app with the sell.fulfillment.readonly permission (see the steps). It lasts about 18 months.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'Join the eBay Developers Program (developer.ebay.com) and create a Production keyset: you get an App ID and a Cert ID.',
            'Under User Tokens, create a RuName for the app, and sign in with the seller account to get an authorization code, then exchange it for a refresh token. The permission needed is sell.fulfillment.readonly.',
            'Paste the App ID, the Cert ID and the refresh token here.',
            'Easier: use “Connect” instead, which does the sign-in for you once the server has the eBay app’s keys and RuName. In the RuName’s settings, set “Your auth accepted URL” to the callback URL shown in Help.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://auth.ebay.com/oauth2/authorize', 'token' => self::TOKEN_URL, 'scopes' => [self::SCOPE], 'token_secret' => 'access_token', 'client_auth' => 'basic'];
    }

    /** eBay does not take the callback address itself but the name of a "RuName" registered for it. */
    protected function ruName(): string
    {
        return (string) config('services.integrations.ebay.ru_name');
    }

    public function autoConnectReady(): bool
    {
        return parent::autoConnectReady() && $this->ruName() !== '';
    }

    public function oauthEnv(): array
    {
        return [...parent::oauthEnv(), ['name' => 'EBAY_RUNAME', 'example' => 'Your_Name-YourApp-PRD-xxxxxxxxx']];
    }

    public function authorizeUrl(array $input, string $state, string $redirect, ?string $challenge = null): string
    {
        return parent::authorizeUrl($input, $state, $this->ruName(), $challenge);
    }

    protected function exchangeParams(string $code, string $redirect, ?string $verifier): array
    {
        return parent::exchangeParams($code, $this->ruName(), $verifier);
    }

    protected function api(Integration $i): string
    {
        return 'https://api.ebay.com/sell/fulfillment/v1';
    }

    protected function token(Integration $i): string
    {
        if (! empty(($i->secrets ?? [])['oauth'])) {
            return $this->secret($i, 'access_token');
        }

        return $this->minted($i, self::TOKEN_URL, ['grant_type' => 'refresh_token', 'refresh_token' => $this->secret($i, 'refresh_token'), 'scope' => self::SCOPE], ['basic' => [$this->option($i, 'client_id'), $this->secret($i, 'client_secret')]]);
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->token($i)];
    }

    protected function refused(): string
    {
        return 'eBay refused the credentials. Check the App ID, Cert ID and refresh token, and that the token has the sell.fulfillment.readonly permission.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/order', ['limit' => 1]);

        return 'eBay seller account';
    }

    /** The cursor is the offset into the orders. A first import reads the last two years (the most eBay keeps); later ones only the orders changed since. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 200): array
    {
        $offset = (int) $cursor;
        $stamp = fn (CarbonInterface $t) => $t->copy()->utc()->format('Y-m-d\TH:i:s.000\Z');
        $body = $this->get($integration, '/order', [
            'limit' => $limit, 'offset' => $offset,
            'filter' => $since ? 'lastmodifieddate:['.$stamp($since).'..]' : 'creationdate:['.$stamp(now()->subYears(2)->addDay()).'..]',
        ]);

        $customers = [];
        foreach ($body['orders'] ?? [] as $o) {
            $registered = $o['buyer']['buyerRegistrationAddress'] ?? [];
            $shipTo = $o['fulfillmentStartInstructions'][0]['shippingStep']['shipTo'] ?? [];
            [$first, $last] = $this->split($registered['fullName'] ?? $shipTo['fullName'] ?? null);
            $customers[] = $this->customer(
                $o['buyer']['username'] ?? $o['orderId'] ?? '', $registered['email'] ?? $shipTo['email'] ?? null, $first, $last,
                $registered['primaryPhone']['phoneNumber'] ?? $shipTo['primaryPhone']['phoneNumber'] ?? null, null,
            );
        }

        return [$customers, $offset + $limit < (int) ($body['total'] ?? 0) ? (string) ($offset + $limit) : null];
    }
}

<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/**
 * Daraz (Open Platform): the buyers of the seller's orders. Daraz gives a buyer's name and phone number but not an email, so these People are
 * identified by their phone number. Every request is signed with the app secret.
 */
class DarazProvider extends MarketplaceProvider
{
    /** The Open Platform address of each country. */
    protected const COUNTRIES = [
        'pk' => 'https://api.daraz.pk/rest', 'bd' => 'https://api.daraz.com.bd/rest', 'lk' => 'https://api.daraz.lk/rest',
        'np' => 'https://api.daraz.com.np/rest', 'mm' => 'https://api.shop.com.mm/rest',
    ];

    public function key(): string
    {
        return 'daraz';
    }

    public function label(): string
    {
        return 'Daraz';
    }

    public function description(): string
    {
        return 'Import the buyers of your Daraz orders, with their name and phone number (Daraz does not share emails).';
    }

    public function fields(): array
    {
        return [
            ['key' => 'country', 'label' => 'Country', 'type' => 'text', 'placeholder' => 'pk', 'help' => 'pk (Pakistan), bd (Bangladesh), lk (Sri Lanka), np (Nepal) or mm (Myanmar).', 'secret' => false],
            ['key' => 'app_key', 'label' => 'App key', 'type' => 'text', 'placeholder' => '123456', 'help' => 'From your app at open.daraz.com (App Console).', 'secret' => false],
            ['key' => 'app_secret', 'label' => 'App secret', 'type' => 'password', 'placeholder' => 'App secret', 'help' => 'From the same app. It signs every request.', 'secret' => true],
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => '50000…', 'help' => 'Created when the seller authorizes your app (App Console → Authorization).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'At open.daraz.com create an app (App Console) and get it approved for the order APIs.',
            'Copy the app key and app secret.',
            'Authorize the app with your seller account (App Console → Authorization) and copy the access token it gives.',
            'Paste the country (pk, bd, lk, np or mm), the app key, the secret and the token here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Daraz shares a buyer’s name and phone number to fulfil the order, not an email, and does not record marketing permission. On “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have the buyers’ permission and Daraz’s rules allow it.';
    }

    protected function api(Integration $i): string
    {
        $country = strtolower($this->option($i, 'country', 'pk'));
        if (! isset(self::COUNTRIES[$country])) {
            throw new IntegrationException('The country must be pk, bd, lk, np or mm.');
        }

        return self::COUNTRIES[$country];
    }

    /** Daraz uses no headers: the key, the token and a signature go in the address. */
    protected function headers(Integration $i): array
    {
        return [];
    }

    protected function token(Integration $i): string
    {
        return $this->secret($i, 'access_token');
    }

    protected function refused(): string
    {
        return 'Daraz refused the credentials. Check the country, the app key and secret, and the access token.';
    }

    /** The signature of a request: HMAC-SHA256 of the API path plus every parameter (name then value, sorted by name), made with the app secret, in capitals. */
    protected function sign(string $path, array $params, string $secret): string
    {
        ksort($params);
        $text = $path;
        foreach ($params as $k => $v) {
            $text .= $k.$v;
        }

        return strtoupper(hash_hmac('sha256', $text, $secret));
    }

    protected function get(Integration $i, string $path, array $query = [], array $headers = []): array
    {
        $params = $query + ['app_key' => $this->option($i, 'app_key'), 'timestamp' => (string) (int) (microtime(true) * 1000), 'sign_method' => 'sha256', 'access_token' => $this->token($i)];
        $params['sign'] = $this->sign($path, $params, $this->secret($i, 'app_secret'));

        $response = $this->send($i, 'GET', $this->api($i).$path, ['query' => $params]);
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || (string) ($body['code'] ?? '0') !== '0') {
            $why = is_array($body) ? ($body['message'] ?? $body['code'] ?? null) : null;
            if ($response->successful() || in_array($response->status(), [401, 403], true)) {
                throw new IntegrationException($this->refused().($why ? ' (Daraz said: '.mb_substr((string) $why, 0, 120).')' : ''));
            }

            throw $this->failed($response, $this->refused(), 'Daraz could not find that. Check the details you entered.');
        }

        return $body;
    }

    public function test(Integration $integration): string
    {
        $seller = $this->get($integration, '/seller/get');

        return (string) ($seller['data']['name'] ?? $seller['data']['name_company'] ?? 'Daraz seller');
    }

    /** The cursor is the offset into the orders. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = (int) $cursor;
        $body = $this->get($integration, '/orders/get', [
            'limit' => (string) $limit, 'offset' => (string) $offset, 'sort_direction' => 'ASC',
            ($since ? 'update_after' : 'created_after') => ($since ?? now()->subYears(2))->copy()->utc()->format('Y-m-d\TH:i:s+00:00'),
        ]);

        $orders = $body['data']['orders'] ?? [];
        $customers = [];
        foreach ($orders as $o) {
            $phone = $o['address_billing']['phone'] ?? $o['address_shipping']['phone'] ?? null;
            $customers[] = $this->customer($o['order_id'] ?? '', null, $o['customer_first_name'] ?? null, $o['customer_last_name'] ?? null, $phone, null);
        }

        return [$customers, count($orders) >= $limit ? (string) ($offset + $limit) : null];
    }
}

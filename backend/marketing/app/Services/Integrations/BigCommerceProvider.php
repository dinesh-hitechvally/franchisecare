<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** BigCommerce customers, through an API account (scope: Customers, read-only) and the store hash. */
class BigCommerceProvider extends ApiProvider
{
    public function key(): string
    {
        return 'bigcommerce';
    }

    public function label(): string
    {
        return 'BigCommerce';
    }

    public function description(): string
    {
        return 'Import the customers of your BigCommerce store.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'store_hash', 'label' => 'Store hash', 'type' => 'text', 'placeholder' => 'abc123xyz', 'help' => 'The code in your API path: https://api.bigcommerce.com/stores/THIS-PART/v3/.', 'secret' => false],
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => 'Access token', 'help' => 'Shown once when you create the API account (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the BigCommerce control panel open Settings → API → Store-level API accounts and choose Create API account → Create V2/V3 Token.',
            'Name it, and set Customers to read-only. Leave everything else on None.',
            'Save, then copy the Access Token (shown once). The API path on that page contains your store hash: https://api.bigcommerce.com/stores/<hash>/v3/.',
            'Paste the store hash and the access token here.',
        ];
    }

    public function consentNote(): string
    {
        return 'BigCommerce does not tell us who agreed to marketing, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have your customers’ permission.';
    }

    /** The same store is the same connection, whichever key it is reached with. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($this->option($integration, 'store_hash'));
    }

    protected function api(Integration $i): string
    {
        return 'https://api.bigcommerce.com/stores/'.$this->slug($i, 'store_hash', 'store hash');
    }

    protected function headers(Integration $i): array
    {
        return ['X-Auth-Token' => $this->secret($i, 'access_token')];
    }

    protected function refused(): string
    {
        return 'BigCommerce refused the token. Check the store hash and the token, and that the account can read Customers.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/v3/customers', ['limit' => 1]);
        $store = $this->get($integration, '/v2/store');

        return (string) ($store['name'] ?? 'BigCommerce store');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 250): array
    {
        $page = max(1, (int) $cursor);
        $body = $this->get($integration, '/v3/customers', array_filter(['limit' => $limit, 'page' => $page, 'date_modified:min' => $this->stamp($since)]));

        $customers = [];
        foreach ($body['data'] ?? [] as $c) {
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $c['first_name'] ?? null, $c['last_name'] ?? null, $c['phone'] ?? null, null);
        }
        $pages = (int) ($body['meta']['pagination']['total_pages'] ?? $page);

        return [$customers, $page < $pages ? (string) ($page + 1) : null];
    }
}

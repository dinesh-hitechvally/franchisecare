<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** WooCommerce (a WordPress shop), through its REST API with a consumer key and secret (Read permission). */
class WooCommerceProvider extends Provider
{
    public function key(): string
    {
        return 'woocommerce';
    }

    public function label(): string
    {
        return 'WooCommerce';
    }

    public function description(): string
    {
        return 'Import the customers of your WooCommerce shop and keep them up to date.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Site address', 'type' => 'text', 'placeholder' => 'https://your-shop.com', 'help' => 'The address of your WordPress site, over https.', 'secret' => false],
            ['key' => 'consumer_key', 'label' => 'Consumer key', 'type' => 'text', 'placeholder' => 'ck_…', 'help' => 'From WooCommerce → Settings → Advanced → REST API.', 'secret' => true],
            ['key' => 'consumer_secret', 'label' => 'Consumer secret', 'type' => 'password', 'placeholder' => 'cs_…', 'help' => 'Shown once next to the key.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In WordPress admin open WooCommerce → Settings → Advanced → REST API and choose Add key.',
            'Give it a description (for example “FranchiseCare Marketing”), pick a user, and set Permissions to Read.',
            'Generate the key, then copy the consumer key and consumer secret (the secret is shown only once).',
            'Paste your site address and the two values here. The site must use https.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'woocommerce', 'asks' => ['url']];
    }

    public function consentNote(): string
    {
        return 'WooCommerce does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have their permission. Customers who checked out as guests (without an account) are not listed by WooCommerce.';
    }

    protected function basic(Integration $i): array
    {
        return [$this->secret($i, 'consumer_key'), $this->secret($i, 'consumer_secret')];
    }

    protected function siteName(Integration $i): string
    {
        $root = $this->send($i, 'GET', rtrim($i->store_url, '/').'/wp-json/');
        if (! $root->successful()) {
            throw $this->failed($root, 'The site refused the request.', 'That address is not a WordPress site (nothing answers at /wp-json/). Check the address and that pretty permalinks are on.');
        }
        if (! in_array('wc/v3', (array) $root->json('namespaces'), true)) {
            throw new IntegrationException('WooCommerce is not available on this site (no WooCommerce REST API). Is the plugin active?');
        }

        return (string) ($root->json('name') ?: parse_url($i->store_url, PHP_URL_HOST));
    }

    protected function page(Integration $i, int $page, int $perPage): array
    {
        $response = $this->send($i, 'GET', rtrim($i->store_url, '/').'/wp-json/wc/v3/customers', [
            'basic' => $this->basic($i),
            'query' => ['per_page' => $perPage, 'page' => $page, 'orderby' => 'id', 'order' => 'asc', 'role' => 'customer'],
        ]);
        if (! $response->successful()) {
            throw $this->failed($response, 'WooCommerce refused the key. Check the consumer key and secret, and that the key has Read permission.', 'The WooCommerce customers address was not found. Is WooCommerce active?');
        }

        return [(array) $response->json(), (int) $response->header('X-WP-TotalPages')];
    }

    public function test(Integration $integration): string
    {
        $name = $this->siteName($integration);
        $this->page($integration, 1, 1);

        return $name;
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $perPage = 100): array
    {
        $page = max(1, (int) $cursor);
        [$rows, $totalPages] = $this->page($integration, $page, $perPage);

        $customers = [];
        foreach ($rows as $c) {
            $customers[] = $this->customer(
                $c['id'] ?? '', $c['email'] ?? null, $c['first_name'] ?? ($c['billing']['first_name'] ?? null), $c['last_name'] ?? ($c['billing']['last_name'] ?? null),
                $c['billing']['phone'] ?? null, null,
            );
        }
        $more = $totalPages ? $page < $totalPages : count($rows) === $perPage;

        return [$customers, $more ? (string) ($page + 1) : null];
    }
}

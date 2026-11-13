<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Shopify, through the Admin GraphQL API with a custom app's access token (permission needed: read_customers). */
class ShopifyProvider extends Provider
{
    public function key(): string
    {
        return 'shopify';
    }

    public function label(): string
    {
        return 'Shopify';
    }

    public function description(): string
    {
        return 'Import your Shopify customers, with their marketing permission, and keep them up to date.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Store address', 'type' => 'text', 'placeholder' => 'your-store.myshopify.com', 'help' => 'The .myshopify.com address of your store (not your own domain). Typing just the first part is fine.', 'secret' => false],
            ['key' => 'access_token', 'label' => 'Admin API access token', 'type' => 'password', 'placeholder' => 'shpat_…', 'help' => 'Shown once when you install the custom app (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Shopify admin open Settings → Apps and sales channels → Develop apps (turn on “Allow custom app development” if asked).',
            'Create an app, then Configure Admin API scopes and tick read_customers.',
            'Install the app, then reveal and copy the Admin API access token (it starts with shpat_ and is shown only once).',
            'Paste your store address and the token here.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => '', 'token' => '', 'scopes' => ['read_customers'], 'token_secret' => 'access_token', 'token_format' => 'json', 'asks' => ['url']];
    }

    protected function oauthAuthorizeBase(array $input): string
    {
        return $this->normalizeUrl((string) ($input['url'] ?? '')).'/admin/oauth/authorize';
    }

    protected function oauthTokenUrl(array $input): string
    {
        return $this->normalizeUrl((string) ($input['url'] ?? '')).'/admin/oauth/access_token';
    }

    protected function exchangeParams(string $code, string $redirect, ?string $verifier): array
    {
        return ['code' => $code];
    }

    /** Shopify signs the callback with the app secret: anything else did not come from Shopify. */
    public function verifyCallback(array $query): bool
    {
        $hmac = (string) ($query['hmac'] ?? '');
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = str_replace(['%', '&', '='], ['%25', '%26', '%3D'], (string) $k).'='.str_replace(['%', '&'], ['%25', '%26'], is_array($v) ? '' : (string) $v);
        }

        return $hmac !== '' && hash_equals(hash_hmac('sha256', implode('&', $pairs), (string) $this->oauthCredentials()[1]), $hmac);
    }

    public function oauthConnect(Integration $integration, array $tokens, array $input): void
    {
        parent::oauthConnect($integration, $tokens, $input);
        $integration->store_url = $this->normalizeUrl((string) ($input['url'] ?? ''));
    }

    public function consentNote(): string
    {
        return 'Shopify records each customer’s email marketing state (subscribed, not subscribed, unsubscribed). Customers who unsubscribed in Shopify are always unsubscribed here.';
    }

    public function normalizeUrl(string $input): string
    {
        $host = strtolower(trim(preg_replace('~^https?://~i', '', trim($input)), " \t\n\r\0\x0B/"));
        $host = explode('/', $host)[0];
        $sub = preg_replace('~\.myshopify\.com$~', '', $host);
        if (! preg_match('~^[a-z0-9][a-z0-9-]*$~', $sub)) {
            throw new IntegrationException('Use your store’s .myshopify.com address, for example your-store.myshopify.com.');
        }

        return 'https://'.$sub.'.myshopify.com';
    }

    protected function graphql(Integration $i, string $query, array $variables = []): array
    {
        $url = rtrim($i->store_url, '/').'/admin/api/'.config('services.integrations.shopify_version', '2026-04').'/graphql.json';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->send($i, 'POST', $url, [
                'headers' => ['X-Shopify-Access-Token' => $this->secret($i, 'access_token')],
                'json' => ['query' => $query] + ($variables ? ['variables' => $variables] : []),
            ]);
            if (! $response->successful()) {
                throw $this->failed($response, 'Shopify refused the access token. Check it was copied completely and the app is installed.', 'Shopify could not find that store. Check the .myshopify.com address.');
            }
            $body = $response->json() ?? [];
            if (empty($body['errors'])) {
                return $body['data'] ?? [];
            }

            $first = $body['errors'][0] ?? [];
            $code = $first['extensions']['code'] ?? '';
            if ($code === 'THROTTLED' && $attempt < 3) {
                sleep(2);

                continue;
            }
            $message = is_array($first) ? ($first['message'] ?? 'Shopify reported an error.') : (string) $first;
            throw new IntegrationException(match (true) {
                $code === 'ACCESS_DENIED' || str_contains($message, 'Access denied') => 'The access token cannot read customers. Give the custom app the read_customers permission and install it again.',
                $code === 'THROTTLED' => 'Shopify is limiting requests right now. Try again in a few minutes.',
                default => 'Shopify said: '.$message,
            });
        }

        return [];
    }

    public function test(Integration $integration): string
    {
        $shop = $this->graphql($integration, '{ shop { name } }')['shop'] ?? null;
        $this->customers($integration, null, null, 1); // proves the customer permission, not only the token

        return (string) ($shop['name'] ?? parse_url($integration->store_url, PHP_URL_HOST));
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $page = 250): array
    {
        $query = 'query($first: Int!, $after: String, $query: String) {
          customers(first: $first, after: $after, query: $query) {
            edges { node { id email firstName lastName phone tags emailMarketingConsent { marketingState } defaultAddress { phone } } }
            pageInfo { hasNextPage endCursor }
          }
        }';
        $data = $this->graphql($integration, $query, [
            'first' => $page, 'after' => $cursor,
            'query' => $since ? "updated_at:>='".$since->copy()->utc()->format('Y-m-d\TH:i:s\Z')."'" : null,
        ])['customers'] ?? ['edges' => [], 'pageInfo' => []];

        $customers = [];
        foreach ($data['edges'] ?? [] as $edge) {
            $c = $edge['node'] ?? [];
            $consent = match ($c['emailMarketingConsent']['marketingState'] ?? null) {
                'SUBSCRIBED' => 'subscribed',
                'UNSUBSCRIBED' => 'unsubscribed',
                null => null,
                default => 'none', // not subscribed, pending, redeemed…
            };
            $customers[] = $this->customer(
                basename((string) ($c['id'] ?? '')), $c['email'] ?? null, $c['firstName'] ?? null, $c['lastName'] ?? null,
                $c['phone'] ?? ($c['defaultAddress']['phone'] ?? null), $consent, (array) ($c['tags'] ?? []),
            );
        }

        $more = ! empty($data['pageInfo']['hasNextPage']) && ! empty($data['pageInfo']['endCursor']);

        return [$customers, $more ? $data['pageInfo']['endCursor'] : null];
    }
}

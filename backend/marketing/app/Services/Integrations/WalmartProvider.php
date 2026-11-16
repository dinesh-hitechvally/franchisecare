<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/** Walmart Marketplace (US): the buyers of the seller's orders, with the name and phone of the shipping address and the email Walmart gives. */
class WalmartProvider extends MarketplaceProvider
{
    public function key(): string
    {
        return 'walmart';
    }

    public function label(): string
    {
        return 'Walmart';
    }

    public function description(): string
    {
        return 'Import the buyers of your Walmart Marketplace orders (the last 180 days Walmart keeps).';
    }

    public function fields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'help' => 'From Seller Center → Settings → API Key Management (Walmart Marketplace API key).', 'secret' => false],
            ['key' => 'client_secret', 'label' => 'Client secret', 'type' => 'password', 'placeholder' => 'Secret key', 'help' => 'Shown with the client ID when the key is created.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Walmart Seller Center open Settings → API Key Management, and choose Walmart Marketplace (or create an API key for your own use).',
            'Copy the client ID and the client secret. Keep the secret private: it can read your orders.',
            'Paste both here. Walmart keeps orders for 180 days, so that is how far back the first import reaches.',
        ];
    }

    public function consentNote(): string
    {
        return 'Walmart shares a buyer’s details to fulfil the order and the email it gives is usually a masked address. It does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have the buyers’ own permission and Walmart’s rules allow it.';
    }

    protected function api(Integration $i): string
    {
        return 'https://marketplace.walmartapis.com/v3';
    }

    /** What every Walmart request must carry besides the token. */
    protected function service(): array
    {
        return ['WM_SVC.NAME' => 'FranchiseCare Marketing', 'WM_QOS.CORRELATION_ID' => (string) Str::uuid(), 'Accept' => 'application/json'];
    }

    protected function token(Integration $i): string
    {
        return $this->minted($i, $this->api($i).'/token', ['grant_type' => 'client_credentials'], ['basic' => [$this->option($i, 'client_id'), $this->secret($i, 'client_secret')], 'headers' => $this->service()]);
    }

    protected function headers(Integration $i): array
    {
        return ['WM_SEC.ACCESS_TOKEN' => $this->token($i)] + $this->service();
    }

    protected function refused(): string
    {
        return 'Walmart refused the credentials. Check the client ID and secret.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/orders', ['limit' => 1, 'createdStartDate' => now()->subDays(7)->utc()->format('Y-m-d\TH:i:s\Z')]);

        return 'Walmart seller account';
    }

    /** The cursor is the query string Walmart gives for the next page (its nextCursor). */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 200): array
    {
        $list = $cursor
            ? $this->get($integration, '/orders'.(str_starts_with($cursor, '?') ? $cursor : '?'.$cursor))['list'] ?? []
            : $this->get($integration, '/orders', ['limit' => $limit, 'createdStartDate' => ($since ?? now()->subDays(179))->copy()->utc()->format('Y-m-d\TH:i:s\Z')])['list'] ?? [];

        $customers = [];
        foreach ($list['elements']['order'] ?? [] as $o) {
            $ship = $o['shippingInfo'] ?? [];
            [$first, $last] = $this->split($ship['postalAddress']['name'] ?? null);
            $customers[] = $this->customer($o['customerOrderId'] ?? $o['purchaseOrderId'] ?? '', $o['customerEmailId'] ?? null, $first, $last, $ship['phone'] ?? null, null);
        }

        return [$customers, ! empty($list['meta']['nextCursor']) ? (string) $list['meta']['nextCursor'] : null];
    }
}

<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Magento / Adobe Commerce, through the REST API with an integration access token (resource: Customers). */
class MagentoProvider extends Provider
{
    public function key(): string
    {
        return 'magento';
    }

    public function label(): string
    {
        return 'Magento';
    }

    public function description(): string
    {
        return 'Import the customers of your Magento (Adobe Commerce) store, with their newsletter status.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Store address', 'type' => 'text', 'placeholder' => 'https://your-store.com', 'help' => 'The base address of your store, over https.', 'secret' => false],
            ['key' => 'access_token', 'label' => 'Integration access token', 'type' => 'password', 'placeholder' => 'Access Token', 'help' => 'From System → Extensions → Integrations (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Magento admin open System → Extensions → Integrations and choose Add New Integration.',
            'Name it (for example “FranchiseCare Marketing”), enter your admin password, then open the API tab.',
            'Under Resource Access choose Custom and tick Customers (and Stores). Save.',
            'Choose Activate, then Allow, and copy the Access Token (not the consumer key).',
            'Paste your store address and the access token here. The store must use https.',
        ];
    }

    public function consentNote(): string
    {
        return 'Magento records whether a customer subscribed to the newsletter. Customers who are subscribed there are subscribed here; the others are imported but not subscribed. Choose “Everyone” only if you have their permission.';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')];
    }

    protected function search(Integration $i, int $page, int $size, ?CarbonInterface $since = null)
    {
        $criteria = ['pageSize' => $size, 'currentPage' => $page, 'sortOrders' => [['field' => 'entity_id', 'direction' => 'ASC']]];
        if ($since) {
            $criteria['filterGroups'] = [['filters' => [['field' => 'updated_at', 'conditionType' => 'gteq', 'value' => $since->copy()->utc()->format('Y-m-d H:i:s')]]]];
        }
        $response = $this->send($i, 'GET', rtrim($i->store_url, '/').'/rest/V1/customers/search', ['headers' => $this->headers($i), 'query' => ['searchCriteria' => $criteria]]);
        if (! $response->successful()) {
            throw $this->failed($response, 'Magento refused the access token. Check it, and that the integration is activated with access to Customers.', 'The Magento REST API was not found at this address. Check it, and that the store uses its normal URL.');
        }

        return $response;
    }

    public function test(Integration $integration): string
    {
        $this->search($integration, 1, 1);

        return (string) parse_url($integration->store_url, PHP_URL_HOST);
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $size = 100): array
    {
        $page = max(1, (int) $cursor);
        $body = $this->search($integration, $page, $size, $since)->json() ?? [];

        $customers = [];
        foreach ($body['items'] ?? [] as $c) {
            $phone = collect($c['addresses'] ?? [])->pluck('telephone')->filter()->first();
            $subscribed = $c['extension_attributes']['is_subscribed'] ?? null;
            $customers[] = $this->customer(
                $c['id'] ?? '', $c['email'] ?? null, $c['firstname'] ?? null, $c['lastname'] ?? null, $phone,
                $subscribed === null ? null : ($subscribed ? 'subscribed' : 'none'),
            );
        }
        $more = $page * $size < (int) ($body['total_count'] ?? 0);

        return [$customers, $more ? (string) ($page + 1) : null];
    }
}

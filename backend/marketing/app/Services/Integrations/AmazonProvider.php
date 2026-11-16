<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/**
 * Amazon (Selling Partner API): the buyers of the seller's orders. Amazon only shares buyer details with apps approved for its restricted
 * "PII" roles, and the email it gives is usually a masked relay address; where it gives nothing, the buyer is skipped.
 */
class AmazonProvider extends MarketplaceProvider
{
    /** Where each region's API lives. */
    protected const REGIONS = [
        'na' => 'https://sellingpartnerapi-na.amazon.com',
        'eu' => 'https://sellingpartnerapi-eu.amazon.com',
        'fe' => 'https://sellingpartnerapi-fe.amazon.com',
    ];

    public function key(): string
    {
        return 'amazon';
    }

    public function label(): string
    {
        return 'Amazon';
    }

    public function description(): string
    {
        return 'Import the buyers of your Amazon seller orders. Amazon only shares buyer details with apps it has approved for them.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'LWA client ID', 'type' => 'text', 'placeholder' => 'amzn1.application-oa2-client.…', 'help' => 'From your app in Seller Central → Apps and Services → Develop Apps.', 'secret' => false],
            ['key' => 'client_secret', 'label' => 'LWA client secret', 'type' => 'password', 'placeholder' => 'amzn1.oa2-cs.v1.…', 'help' => 'From the same app (Login with Amazon credentials).', 'secret' => true],
            ['key' => 'refresh_token', 'label' => 'Refresh token', 'type' => 'password', 'placeholder' => 'Atzr|…', 'help' => 'Created when you authorize your own app (Seller Central → Develop Apps → Authorize).', 'secret' => true],
            ['key' => 'region', 'label' => 'Region', 'type' => 'text', 'placeholder' => 'na', 'help' => 'na (North America), eu (Europe, UK, India, Middle East) or fe (Far East: Australia, Japan, Singapore).', 'secret' => false],
            ['key' => 'marketplace_ids', 'card' => 'import', 'label' => 'Marketplace IDs', 'type' => 'text', 'placeholder' => 'ATVPDKIKX0DER', 'help' => 'The Amazon marketplaces to read, separated by commas (for example ATVPDKIKX0DER for amazon.com, A39IBJ37TRP1C6 for amazon.com.au).', 'secret' => false],
        ];
    }

    public function steps(): array
    {
        return [
            'In Seller Central open Apps and Services → Develop Apps and register a private (self-authorized) developer profile and app.',
            'Give the app the roles it needs: Direct-to-Consumer Delivery or Tax Invoicing (restricted roles), which are what let Amazon share buyer names and emails. Without them Amazon returns no buyer details.',
            'Authorize the app and copy its LWA client ID, client secret and the refresh token.',
            'Paste them here, with your region (na, eu or fe) and the marketplace IDs to read.',
        ];
    }

    public function consentNote(): string
    {
        return 'Amazon shares buyer details only for fulfilling orders and its policies do not allow marketing to buyers through Amazon-provided data. The email it gives is normally a masked relay address. Nobody is subscribed on “Only people who agreed”; choose “Everyone” only if you have the buyers’ own permission.';
    }

    protected function api(Integration $i): string
    {
        $region = strtolower($this->option($i, 'region', 'na'));
        if (! isset(self::REGIONS[$region])) {
            throw new IntegrationException('The region must be na, eu or fe.');
        }

        return self::REGIONS[$region];
    }

    protected function token(Integration $i): string
    {
        return $this->minted($i, 'https://api.amazon.com/auth/o2/token', [
            'grant_type' => 'refresh_token', 'refresh_token' => $this->secret($i, 'refresh_token'),
            'client_id' => $this->option($i, 'client_id'), 'client_secret' => $this->secret($i, 'client_secret'),
        ]);
    }

    protected function headers(Integration $i): array
    {
        return ['x-amz-access-token' => $this->token($i)];
    }

    protected function refused(): string
    {
        return 'Amazon refused the credentials. Check the client ID, secret and refresh token, the region, and that the app has the roles it needs.';
    }

    /** A token that lets the orders include the buyer's name and email (Amazon's "restricted data token"). */
    protected function restricted(Integration $i): string
    {
        $response = $this->send($i, 'POST', $this->api($i).'/tokens/2021-03-01/restrictedDataToken', [
            'headers' => $this->headers($i),
            'json' => ['restrictedResources' => [['method' => 'GET', 'path' => '/orders/v0/orders', 'dataElements' => ['buyerInfo', 'shippingAddress']]]],
        ]);
        if (! $response->successful() || empty($response->json('restrictedDataToken'))) {
            throw new IntegrationException('Amazon will not share buyer details with this app. It must be approved for a restricted role such as Direct-to-Consumer Delivery or Tax Invoicing, and the refresh token must be created after that.');
        }

        return (string) $response->json('restrictedDataToken');
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/sellers/v1/marketplaceParticipations');

        return 'Amazon seller account';
    }

    /** The cursor is Amazon's NextToken. The first import reads the last year of orders. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $query = $cursor
            ? ['NextToken' => $cursor]
            : array_filter([
                'MarketplaceIds' => preg_replace('/\s+/', '', $this->option($integration, 'marketplace_ids')),
                'CreatedAfter' => $since ? null : now()->subYear()->utc()->format('Y-m-d\TH:i:s\Z'),
                'LastUpdatedAfter' => $since ? $this->stamp($since) : null, 'MaxResultsPerPage' => min($limit, 100),
            ], fn ($v) => $v !== null && $v !== '');
        if (! $cursor && empty($query['MarketplaceIds'])) {
            throw new IntegrationException('Enter the marketplace IDs to read (for example ATVPDKIKX0DER).');
        }

        $body = $this->get($integration, '/orders/v0/orders', $query, ['x-amz-access-token' => $this->restricted($integration)])['payload'] ?? [];

        $customers = [];
        foreach ($body['Orders'] ?? [] as $o) {
            $email = $o['BuyerInfo']['BuyerEmail'] ?? null;
            $phone = $o['ShippingAddress']['Phone'] ?? null;
            [$first, $last] = $this->split($o['BuyerInfo']['BuyerName'] ?? $o['ShippingAddress']['Name'] ?? null);
            $customers[] = $this->customer($o['AmazonOrderId'] ?? '', $email, $first, $last, $phone, null);
        }

        return [$customers, $body['NextToken'] ?? null];
    }
}

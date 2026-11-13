<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** GoCardless customers (direct debit), through an access token with read access. */
class GoCardlessProvider extends ApiProvider
{
    public function key(): string
    {
        return 'gocardless';
    }

    public function label(): string
    {
        return 'GoCardless';
    }

    public function category(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return 'Import the customers of your GoCardless direct debit account.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Read-only access token', 'type' => 'password', 'placeholder' => 'live_…', 'help' => 'Created under Developers → Create → Access token (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In the GoCardless dashboard open Developers → Create → Access token.',
            'Choose “Read only”, name it, and create it. Copy the token (shown once).',
            'Paste it here. Tokens starting with sandbox_ work against the GoCardless sandbox.',
        ];
    }

    public function consentNote(): string
    {
        return 'GoCardless does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Customers have not necessarily agreed to marketing: choose “Everyone” only if you have permission.';
    }

    protected function api(Integration $i): string
    {
        return str_starts_with($this->secret($i, 'access_token'), 'sandbox_') ? 'https://api-sandbox.gocardless.com' : 'https://api.gocardless.com';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token'), 'GoCardless-Version' => '2015-07-06'];
    }

    protected function refused(): string
    {
        return 'GoCardless refused the token. Check it was copied completely and is still active.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['limit' => 1]);
        try {
            $creditors = $this->get($integration, '/creditors', ['limit' => 1]);
        } catch (IntegrationException) {
            $creditors = [];
        }

        return (string) ($creditors['creditors'][0]['name'] ?? 'GoCardless account');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 500): array
    {
        $body = $this->get($integration, '/customers', array_filter(['limit' => $limit, 'after' => $cursor, 'created_at[gte]' => $this->stamp($since)]));

        $customers = [];
        foreach ($body['customers'] ?? [] as $c) {
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $c['given_name'] ?? null, $c['family_name'] ?? null, $c['phone_number'] ?? null, null);
        }

        return [$customers, $body['meta']['cursors']['after'] ?? null];
    }
}

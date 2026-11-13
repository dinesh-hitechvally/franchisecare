<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Chargebee customers, through your site name and a read-only API key. */
class ChargebeeProvider extends ApiProvider
{
    public function key(): string
    {
        return 'chargebee';
    }

    public function label(): string
    {
        return 'Chargebee';
    }

    public function category(): string
    {
        return 'payments';
    }

    public function description(): string
    {
        return 'Import the customers of your Chargebee subscription billing site.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'site', 'label' => 'Site name', 'type' => 'text', 'placeholder' => 'your-company', 'help' => 'The first part of your Chargebee address: THIS-PART.chargebee.com.', 'secret' => false],
            ['key' => 'api_key', 'label' => 'Read-only API key', 'type' => 'password', 'placeholder' => 'live_…', 'help' => 'Created under Settings → Configure Chargebee → API Keys.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Chargebee open Settings → Configure Chargebee → API Keys and choose Add API Key.',
            'Choose Read-Only Key, name it, and create it. Copy the key.',
            'Your site name is the first part of your Chargebee address (your-company.chargebee.com).',
            'Paste the site name and the key here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Chargebee does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Billing customers have not necessarily agreed to marketing: choose “Everyone” only if you have permission.';
    }

    /** The same store is the same connection, whichever key it is reached with. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($this->option($integration, 'site'));
    }

    protected function api(Integration $i): string
    {
        return 'https://'.$this->slug($i, 'site', 'Chargebee site name').'.chargebee.com/api/v2';
    }

    protected function headers(Integration $i): array
    {
        return [];
    }

    protected function basic(Integration $i): ?array
    {
        return [$this->secret($i, 'api_key'), ''];
    }

    protected function refused(): string
    {
        return 'Chargebee refused the key. Check the site name and the API key.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/customers', ['limit' => 1]);

        return ucfirst($this->option($integration, 'site')).' (Chargebee)';
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $query = array_filter(['limit' => $limit, 'offset' => $cursor, 'sort_by[asc]' => 'created_at']);
        if ($since) {
            $query['updated_at[after]'] = $since->getTimestamp();
        }
        $body = $this->get($integration, '/customers', $query);

        $customers = [];
        foreach ($body['list'] ?? [] as $row) {
            $c = $row['customer'] ?? [];
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $c['first_name'] ?? null, $c['last_name'] ?? null, $c['phone'] ?? null, null);
        }

        return [$customers, $body['next_offset'] ?? null];
    }
}

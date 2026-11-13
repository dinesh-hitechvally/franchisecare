<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Close CRM contacts, through an API key. */
class CloseProvider extends ApiProvider
{
    public function key(): string
    {
        return 'close';
    }

    public function label(): string
    {
        return 'Close';
    }

    public function category(): string
    {
        return 'crm';
    }

    public function description(): string
    {
        return 'Import the contacts of your Close CRM.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'api_…', 'help' => 'Created under Settings → Developer → API Keys.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Close open Settings → Developer → API Keys and choose New API Key.',
            'Name it and copy the key (it starts with api_ and is shown once).',
            'Paste it here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Close does not record marketing permission, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have permission.';
    }

    protected function api(Integration $i): string
    {
        return 'https://api.close.com/api/v1';
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
        return 'Close refused the API key. Check it was copied completely.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/contact/', ['_limit' => 1]);
        try {
            $me = $this->get($integration, '/me/');
        } catch (IntegrationException) {
            $me = [];
        }

        return (string) ($me['organizations'][0]['name'] ?? 'Close');
    }

    /** Close has no “changed since” filter on contacts, so every sync reads all of them (people already here are only completed). */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $skip = max(0, (int) $cursor);
        $body = $this->get($integration, '/contact/', ['_skip' => $skip, '_limit' => $limit]);

        $customers = [];
        foreach ($body['data'] ?? [] as $c) {
            [$first, $last] = $this->split($c['name'] ?? null);
            $customers[] = $this->customer($c['id'] ?? '', $this->firstOf($c['emails'] ?? [], 'email'), $first, $last, $this->firstOf($c['phones'] ?? [], 'phone'), null);
        }

        return [$customers, ! empty($body['has_more']) ? (string) ($skip + $limit) : null];
    }
}

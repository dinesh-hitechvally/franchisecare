<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Webflow site members (the Memberships “Users”), through a site API token with the Users permission. */
class WebflowProvider extends ApiProvider
{
    public function key(): string
    {
        return 'webflow';
    }

    public function label(): string
    {
        return 'Webflow';
    }

    public function category(): string
    {
        return 'website';
    }

    public function description(): string
    {
        return 'Import the signed-up users (members) of your Webflow site.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'site_id', 'label' => 'Site ID', 'type' => 'text', 'placeholder' => '5f1a…', 'help' => 'Found in Site settings → General (scroll to the bottom), or in the API token page.', 'secret' => false],
            ['key' => 'api_token', 'label' => 'Site API token', 'type' => 'password', 'placeholder' => 'API token', 'help' => 'Created under Site settings → Apps & integrations → API access.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Webflow open Site settings → Apps & integrations → API access and choose Generate API token.',
            'Name it and give it read access to Users (and Sites). Generate and copy the token (shown once).',
            'Your Site ID is under Site settings → General. This works for sites that use Webflow Memberships (user accounts).',
            'Paste the site ID and the token here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Webflow does not record marketing permission for users, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have their permission.';
    }

    /** The same store is the same connection, whichever key it is reached with. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($this->option($integration, 'site_id'));
    }

    protected function api(Integration $i): string
    {
        return 'https://api.webflow.com/v2/sites/'.$this->slug($i, 'site_id', 'site ID');
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'api_token'), 'accept-version' => '2.0.0'];
    }

    protected function refused(): string
    {
        return 'Webflow refused the token. Check the site ID and token, and that the token can read Users.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/users', ['limit' => 1]);
        try {
            $site = $this->get($integration, '');
        } catch (IntegrationException) {
            $site = [];
        }

        return (string) ($site['displayName'] ?? $site['shortName'] ?? 'Webflow site');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = max(0, (int) $cursor);
        $body = $this->get($integration, '/users', ['limit' => $limit, 'offset' => $offset]);

        $customers = [];
        foreach ($body['users'] ?? [] as $u) {
            $d = $u['data'] ?? [];
            [$first, $last] = $this->split($d['name'] ?? null);
            $customers[] = $this->customer($u['id'] ?? '', $d['email'] ?? null, $first, $last, $d['phone'] ?? null, null);
        }
        $total = (int) ($body['pagination']['total'] ?? 0);

        return [$customers, $offset + $limit < $total ? (string) ($offset + $limit) : null];
    }
}

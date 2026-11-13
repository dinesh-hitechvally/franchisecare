<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Squarespace profiles (customers, subscribers, donors, account holders), through an API key with the Profiles permission. */
class SquarespaceProvider extends ApiProvider
{
    public function key(): string
    {
        return 'squarespace';
    }

    public function label(): string
    {
        return 'Squarespace';
    }

    public function category(): string
    {
        return 'website';
    }

    public function description(): string
    {
        return 'Import the customer and mailing-list profiles of your Squarespace site.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'API key', 'help' => 'Created under Settings → Developer tools → API keys (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Squarespace open Settings → Developer tools → API keys and choose Generate key. (The Profiles API needs a Commerce plan or higher.)',
            'Name it, give it the Profiles permission (read), and generate the key.',
            'Copy the key (shown once) and paste it here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Squarespace records whether each profile accepts marketing. Those profiles are subscribed here; the others are imported but not subscribed. Choose “Everyone” only if you have permission.';
    }

    protected function api(Integration $i): string
    {
        return 'https://api.squarespace.com/1.0';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'api_key')];
    }

    protected function refused(): string
    {
        return 'Squarespace refused the API key. Check it, and that it has the Profiles permission.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/profiles');
        try {
            $site = $this->get($integration, '/authorization/website');
        } catch (IntegrationException) {
            $site = [];
        }

        return (string) ($site['title'] ?? $site['url'] ?? 'Squarespace site');
    }

    /** Squarespace pages with an opaque cursor, which cannot be combined with other filters: every sync reads all profiles. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 50): array
    {
        $body = $this->get($integration, '/profiles', $cursor ? ['cursor' => $cursor] : []);

        $customers = [];
        foreach ($body['profiles'] ?? [] as $p) {
            $customers[] = $this->customer($p['id'] ?? '', $p['email'] ?? null, $p['firstName'] ?? null, $p['lastName'] ?? null, $p['address']['phone'] ?? null, ! empty($p['acceptsMarketing']) ? 'subscribed' : 'none');
        }
        $more = ! empty($body['pagination']['hasNextPage']) && ! empty($body['pagination']['nextPageCursor']);

        return [$customers, $more ? $body['pagination']['nextPageCursor'] : null];
    }
}

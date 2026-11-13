<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** ActiveCampaign contacts, through your account address and API key. */
class ActiveCampaignProvider extends ApiProvider
{
    public function key(): string
    {
        return 'activecampaign';
    }

    public function label(): string
    {
        return 'ActiveCampaign';
    }

    public function category(): string
    {
        return 'crm';
    }

    public function description(): string
    {
        return 'Import the contacts of your ActiveCampaign account.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'API URL', 'type' => 'text', 'placeholder' => 'https://youraccount.api-us1.com', 'help' => 'Shown under Settings → Developer, labelled “API Access URL”.', 'secret' => false],
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'API key', 'help' => 'Shown on the same page, labelled “Key”.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In ActiveCampaign open Settings → Developer.',
            'Copy the API Access URL (it looks like https://youraccount.api-us1.com) and the Key.',
            'Paste both here.',
        ];
    }

    public function consentNote(): string
    {
        return 'This import does not read each contact’s subscription status in ActiveCampaign, so contacts who unsubscribed there are imported too. Keep “Only people who agreed” (nobody is subscribed automatically) unless you have checked your list and have permission.';
    }

    protected function headers(Integration $i): array
    {
        return ['Api-Token' => $this->secret($i, 'api_key')];
    }

    protected function refused(): string
    {
        return 'ActiveCampaign refused the key. Check the API URL and key under Settings → Developer.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/api/3/contacts', ['limit' => 1]);

        return (string) (explode('.', (string) parse_url($integration->store_url, PHP_URL_HOST))[0] ?: 'ActiveCampaign');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = max(0, (int) $cursor);
        $body = $this->get($integration, '/api/3/contacts', array_filter([
            'limit' => $limit, 'offset' => $offset, 'orders' => ['id' => 'ASC'], 'filters' => $since ? ['updated_after' => $this->stamp($since, 'Y-m-d\TH:i:sP')] : null,
        ]));

        $customers = [];
        foreach ($body['contacts'] ?? [] as $c) {
            $customers[] = $this->customer($c['id'] ?? '', $c['email'] ?? null, $c['firstName'] ?? null, $c['lastName'] ?? null, $c['phone'] ?? null, null);
        }
        $total = (int) ($body['meta']['total'] ?? 0);

        return [$customers, $offset + $limit < $total ? (string) ($offset + $limit) : null];
    }
}

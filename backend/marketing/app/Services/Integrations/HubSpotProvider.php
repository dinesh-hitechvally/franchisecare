<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** HubSpot CRM contacts, through a private app's access token (scope: crm.objects.contacts.read). */
class HubSpotProvider extends Provider
{
    public function key(): string
    {
        return 'hubspot';
    }

    public function label(): string
    {
        return 'HubSpot';
    }

    public function category(): string
    {
        return 'crm';
    }

    public function description(): string
    {
        return 'Import the contacts of your HubSpot CRM. Contacts who opted out of HubSpot emails stay unsubscribed.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Private app access token', 'type' => 'password', 'placeholder' => 'pat-na1-…', 'help' => 'Shown when you create the private app (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In HubSpot open Settings (the gear) → Integrations → Private Apps and choose Create private app.',
            'Give it a name, then open the Scopes tab and tick crm.objects.contacts.read (read only).',
            'Create the app, then copy its Access token (it starts with pat-).',
            'Paste the token here. Contacts are imported from the HubSpot account the app belongs to.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://app.hubspot.com/oauth/authorize', 'token' => 'https://api.hubapi.com/oauth/v1/token', 'scopes' => ['crm.objects.contacts.read'], 'token_secret' => 'access_token'];
    }

    public function consentNote(): string
    {
        return 'HubSpot records contacts who unsubscribed from all email: they are always unsubscribed here. It does not record who agreed to marketing, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have permission.';
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://api.hubapi.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'access_token'));
    }

    protected function page(Integration $i, ?string $after, int $limit)
    {
        $response = $this->send($i, 'GET', 'https://api.hubapi.com/crm/v3/objects/contacts', [
            'headers' => ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')],
            'query' => array_filter(['limit' => $limit, 'after' => $after, 'properties' => 'email,firstname,lastname,phone,mobilephone,hs_email_optout'], fn ($v) => $v !== null),
        ]);
        if (! $response->successful()) {
            throw $this->failed($response, 'HubSpot refused the token. Check it, and that the private app has the crm.objects.contacts.read scope.', 'HubSpot could not find the contacts. Check the token belongs to a private app.');
        }

        return $response;
    }

    public function test(Integration $integration): string
    {
        $this->page($integration, null, 1);

        return 'HubSpot';
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $body = $this->page($integration, $cursor, $limit)->json() ?? [];

        $customers = [];
        foreach ($body['results'] ?? [] as $c) {
            $p = $c['properties'] ?? [];
            $customers[] = $this->customer(
                $c['id'] ?? '', $p['email'] ?? null, $p['firstname'] ?? null, $p['lastname'] ?? null, ($p['phone'] ?? '') ?: ($p['mobilephone'] ?? null),
                filter_var($p['hs_email_optout'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'unsubscribed' : null,
            );
        }
        $next = $body['paging']['next']['after'] ?? null;

        return [$customers, $next !== null && $next !== '' ? (string) $next : null];
    }
}

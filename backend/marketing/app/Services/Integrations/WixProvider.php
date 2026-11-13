<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Wix contacts (customers, subscribers, form submitters), through an API key with the Contacts permission. */
class WixProvider extends ApiProvider
{
    public function key(): string
    {
        return 'wix';
    }

    public function label(): string
    {
        return 'Wix';
    }

    public function category(): string
    {
        return 'website';
    }

    public function description(): string
    {
        return 'Import the contacts of your Wix site, with their email subscription status.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'site_id', 'label' => 'Site ID', 'type' => 'text', 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'help' => 'The id in your dashboard address: manage.wix.com/dashboard/THIS-PART.', 'secret' => false],
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'IST.…', 'help' => 'Created in the Wix API Keys manager (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'Open the Wix API Keys manager (account settings → API keys) and choose Generate API key.',
            'Name it and add the permission “Read Contacts” (under Contacts & Members). Generate and copy the key.',
            'Your Site ID is in the address of your site dashboard: manage.wix.com/dashboard/<site id>.',
            'Paste the site ID and the key here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Wix records each contact’s email subscription status. Subscribed contacts are subscribed here; unsubscribed contacts stay unsubscribed; the rest are imported but not subscribed.';
    }

    /** The same store is the same connection, whichever key it is reached with. */
    public function identity(Integration $integration): string
    {
        return mb_strtolower($this->option($integration, 'site_id'));
    }

    protected function api(Integration $i): string
    {
        return 'https://www.wixapis.com';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => $this->secret($i, 'api_key'), 'wix-site-id' => $this->slug($i, 'site_id', 'site ID')];
    }

    protected function refused(): string
    {
        return 'Wix refused the key. Check the site ID and key, and that the key can read Contacts.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/contacts/v4/contacts', ['paging.limit' => 1]);

        return 'Wix site';
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $offset = max(0, (int) $cursor);
        $body = $this->get($integration, '/contacts/v4/contacts', ['paging.limit' => $limit, 'paging.offset' => $offset]);

        $customers = [];
        foreach ($body['contacts'] ?? [] as $c) {
            $info = $c['info'] ?? [];
            $email = $c['primaryInfo']['email'] ?? $c['primaryEmail']['email'] ?? $this->firstOf($info['emails']['items'] ?? [], 'email');
            $status = strtoupper((string) ($c['primaryEmail']['subscriptionStatus'] ?? ''));
            $customers[] = $this->customer(
                $c['id'] ?? '', $email, $info['name']['first'] ?? null, $info['name']['last'] ?? null,
                $c['primaryInfo']['phone'] ?? $this->firstOf($info['phones']['items'] ?? [], 'phone'),
                match ($status) { 'SUBSCRIBED' => 'subscribed', 'UNSUBSCRIBED' => 'unsubscribed', '' => null, default => 'none' },
            );
        }
        $count = count($body['contacts'] ?? []);
        $total = (int) ($body['pagingMetadata']['total'] ?? 0);

        return [$customers, $count >= $limit && ($total === 0 || $offset + $limit < $total) ? (string) ($offset + $limit) : null];
    }
}

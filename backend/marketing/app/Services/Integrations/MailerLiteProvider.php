<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** MailerLite subscribers (the current MailerLite platform), through an API token. */
class MailerLiteProvider extends ApiProvider
{
    public function key(): string
    {
        return 'mailerlite';
    }

    public function label(): string
    {
        return 'MailerLite';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import your MailerLite subscribers. Unsubscribed, bounced and junk subscribers stay unsubscribed.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_token', 'label' => 'API token', 'type' => 'password', 'placeholder' => 'API token', 'help' => 'Created under Integrations → API (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In MailerLite open Integrations → MailerLite API and choose Generate new token.',
            'Name it and generate the token. Copy it (shown once).',
            'Paste it here.',
        ];
    }

    public function consentNote(): string
    {
        return 'MailerLite subscribers with the status “active” are subscribed here. Unsubscribed, bounced and junk subscribers are unsubscribed; unconfirmed ones are imported but not subscribed.';
    }

    protected function api(Integration $i): string
    {
        return 'https://connect.mailerlite.com/api';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'api_token')];
    }

    protected function refused(): string
    {
        return 'MailerLite refused the token. Generate a new one under Integrations → MailerLite API.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/subscribers', ['limit' => 1]);

        return 'MailerLite account';
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 100): array
    {
        $body = $this->get($integration, '/subscribers', array_filter(['limit' => $limit, 'cursor' => $cursor]));

        $customers = [];
        foreach ($body['data'] ?? [] as $s) {
            $f = $s['fields'] ?? [];
            $customers[] = $this->customer(
                $s['id'] ?? '', $s['email'] ?? null, $f['name'] ?? null, $f['last_name'] ?? null, $f['phone'] ?? null,
                match ($s['status'] ?? null) { 'active' => 'subscribed', 'unsubscribed', 'bounced', 'junk' => 'unsubscribed', 'unconfirmed' => 'none', default => null },
            );
        }

        return [$customers, $body['meta']['next_cursor'] ?? null];
    }
}

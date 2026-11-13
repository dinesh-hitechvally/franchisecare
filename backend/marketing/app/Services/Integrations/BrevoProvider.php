<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Brevo (formerly Sendinblue) contacts, through an API key. */
class BrevoProvider extends Provider
{
    public function key(): string
    {
        return 'brevo';
    }

    public function label(): string
    {
        return 'Brevo';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import your Brevo (Sendinblue) contacts. Contacts blocklisted for email stay unsubscribed.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'xkeysib-…', 'help' => 'Created under SMTP & API → API keys.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Brevo open your account menu → SMTP & API → API Keys & MCP and choose Generate a new API key.',
            'Name it (for example “FranchiseCare Marketing”) and copy the key (it starts with xkeysib- and is shown only once).',
            'Paste the key here.',
        ];
    }

    public function consentNote(): string
    {
        return 'Brevo marks contacts who unsubscribed as blocklisted: they are always unsubscribed here. It does not record who agreed to marketing, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have permission.';
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://api.brevo.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'api_key'));
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', 'https://api.brevo.com/v3'.$path, ['headers' => ['api-key' => $this->secret($i, 'api_key')], 'query' => $query]);
        if (! $response->successful()) {
            throw $this->failed($response, 'Brevo refused the API key. Check it was copied completely, and that it is still active.', 'Brevo could not find that.');
        }

        return $response;
    }

    public function test(Integration $integration): string
    {
        $account = $this->get($integration, '/account')->json() ?? [];

        return (string) ($account['companyName'] ?? $account['email'] ?? 'Brevo');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 500): array
    {
        $offset = max(0, (int) $cursor);
        $body = $this->get($integration, '/contacts', array_filter([
            'limit' => $limit, 'offset' => $offset, 'sort' => 'asc',
            'modifiedSince' => $since ? $since->copy()->utc()->format('Y-m-d\TH:i:s.000\Z') : null,
        ], fn ($v) => $v !== null))->json() ?? [];

        $customers = [];
        foreach ($body['contacts'] ?? [] as $c) {
            $a = $c['attributes'] ?? [];
            $customers[] = $this->customer(
                $c['id'] ?? '', $c['email'] ?? null, $a['FIRSTNAME'] ?? null, $a['LASTNAME'] ?? null, $a['SMS'] ?? ($a['MOBILE'] ?? null),
                ! empty($c['emailBlacklisted']) ? 'unsubscribed' : null,
            );
        }
        $more = $offset + $limit < (int) ($body['count'] ?? 0);

        return [$customers, $more ? (string) ($offset + $limit) : null];
    }
}

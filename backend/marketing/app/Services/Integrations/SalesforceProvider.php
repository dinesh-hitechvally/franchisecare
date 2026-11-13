<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Salesforce contacts, through your instance address and an access token (a Connected App, or a session id) that can read Contacts. */
class SalesforceProvider extends ApiProvider
{
    protected const VERSION = 'v60.0';

    public function key(): string
    {
        return 'salesforce';
    }

    public function label(): string
    {
        return 'Salesforce';
    }

    public function category(): string
    {
        return 'crm';
    }

    public function description(): string
    {
        return 'Import the contacts of your Salesforce org. People who opted out of email stay unsubscribed.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'url', 'label' => 'Instance address', 'type' => 'text', 'placeholder' => 'https://yourcompany.my.salesforce.com', 'help' => 'The address you log in to Salesforce with (your “My Domain”).', 'secret' => false],
            ['key' => 'access_token', 'label' => 'Access token', 'type' => 'password', 'placeholder' => 'Access token', 'help' => 'An OAuth access token that can read Contacts (see the steps).', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Salesforce Setup create an External Client App (or Connected App) with the OAuth scopes “Manage user data via APIs (api)” and “Perform requests at any time (refresh_token)”.',
            'Get an access token for a user who can read Contacts, using your preferred OAuth flow (for example the client-credentials flow for a service user).',
            'Paste your instance address and the access token here. Access tokens expire: when the connection shows “needs attention”, open Settings and paste a fresh one.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://login.salesforce.com/services/oauth2/authorize', 'token' => 'https://login.salesforce.com/services/oauth2/token', 'scopes' => ['api', 'refresh_token'], 'token_secret' => 'access_token'];
    }

    /** Salesforce answers with the address of the org the person chose. */
    public function oauthConnect(Integration $integration, array $tokens, array $input): void
    {
        parent::oauthConnect($integration, $tokens, $input);
        $instance = rtrim((string) ($tokens['raw']['instance_url'] ?? ''), '/');
        if (! preg_match('~^https://[a-z0-9.-]+\.(salesforce|force)\.com$~i', $instance)) {
            throw new IntegrationException('Salesforce did not say which org to use. Please try again.');
        }
        $integration->store_url = strtolower($instance);
    }

    public function consentNote(): string
    {
        return 'Salesforce records “Email Opt Out” per contact: those contacts stay unsubscribed here. It does not record who agreed to marketing, so on “Only people who agreed” nobody is subscribed. Choose “Everyone” only if you have permission.';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')];
    }

    protected function refused(): string
    {
        return 'Salesforce refused the token. It may have expired: get a fresh access token and check that it can read Contacts.';
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/services/data/'.self::VERSION.'/query', ['q' => 'SELECT Id FROM Contact LIMIT 1']);

        return (string) (parse_url($integration->store_url, PHP_URL_HOST) ?: 'Salesforce');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $limit = 200): array
    {
        if ($cursor) {
            if (! str_starts_with($cursor, '/services/data/')) {
                throw new IntegrationException('Salesforce returned an unexpected page link.');
            }
            $body = $this->get($integration, $cursor);
        } else {
            $soql = 'SELECT Id, FirstName, LastName, Email, Phone, MobilePhone, HasOptedOutOfEmail FROM Contact'
                .($since ? ' WHERE SystemModstamp >= '.$this->stamp($since) : '');
            $body = $this->get($integration, '/services/data/'.self::VERSION.'/query', ['q' => $soql], ['Sforce-Query-Options' => 'batchSize='.$limit]);
        }

        $customers = [];
        foreach ($body['records'] ?? [] as $c) {
            $customers[] = $this->customer($c['Id'] ?? '', $c['Email'] ?? null, $c['FirstName'] ?? null, $c['LastName'] ?? null, $c['MobilePhone'] ?? $c['Phone'] ?? null, ! empty($c['HasOptedOutOfEmail']) ? 'unsubscribed' : null);
        }

        return [$customers, empty($body['done']) ? ($body['nextRecordsUrl'] ?? null) : null];
    }
}

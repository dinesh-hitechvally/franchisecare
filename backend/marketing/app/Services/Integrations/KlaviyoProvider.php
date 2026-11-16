<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Person;
use Carbon\CarbonInterface;

/**
 * Klaviyo profiles, both ways: profiles are imported as People (all of them, or the members of one list), and People can be sent to Klaviyo
 * as profiles on a list, with their email consent. A private API key (or the one-click connection) needs Read access to Profiles, Subscriptions,
 * Accounts and Lists to import, and Write access to Profiles, Subscriptions and Lists to send.
 */
class KlaviyoProvider extends Provider
{
    /** Most profiles one subscribe or unsubscribe job takes. */
    protected const JOB_LIMIT = 1000;

    public function key(): string
    {
        return 'klaviyo';
    }

    public function label(): string
    {
        return 'Klaviyo';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import your Klaviyo profiles with their email marketing consent, and optionally send your People to a Klaviyo list.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'private_key', 'label' => 'Private API key', 'type' => 'password', 'placeholder' => 'pk_…', 'help' => 'Created under Settings → Account → API keys.', 'secret' => true],
            ['key' => 'export_audience_id', 'card' => 'export', 'export_required' => true, 'required' => false, 'label' => 'Send to list', 'type' => 'text', 'placeholder' => 'List ID', 'help' => 'The Klaviyo list your People are sent to (only used when this connection sends people). In Klaviyo open the list: its ID is the last part of the address, or under Settings.', 'secret' => false],
            ['key' => 'audience_id', 'card' => 'import', 'label' => 'Import from list (optional)', 'type' => 'text', 'placeholder' => 'Leave empty to import every profile', 'help' => 'To import the members of one list only, enter its List ID. Leave it empty to import every profile of the account.', 'secret' => false, 'required' => false],
        ];
    }

    public function steps(): array
    {
        return [
            'In Klaviyo open Settings → Account → API keys and choose Create Private API Key.',
            'Name it (for example “FranchiseCare Marketing”), choose Custom Key and give it Read access to Profiles, Subscriptions, Accounts and Lists. To send your People to Klaviyo, also give it Write access to Profiles, Subscriptions and Lists.',
            'Create the key and copy it (it starts with pk_ and is shown only once).',
            'Paste the key here. To send people, also enter the List ID they are sent to (and, if you like, one list to import from).',
        ];
    }

    public function autoConnect(): ?array
    {
        return [
            'mode' => 'oauth', 'authorize' => 'https://www.klaviyo.com/oauth/authorize', 'token' => 'https://a.klaviyo.com/oauth/token',
            'scopes' => ['accounts:read', 'lists:read', 'lists:write', 'profiles:read', 'profiles:write', 'subscriptions:read', 'subscriptions:write'],
            'token_secret' => 'private_key', 'client_auth' => 'basic', 'pkce' => true,
        ];
    }

    public function scopeNotes(): array
    {
        return [
            'accounts:read' => ['why' => 'Show the name of your Klaviyo account.', 'for' => ['import', 'send']],
            'lists:read' => ['why' => 'List your lists, so you can choose where to import from and send to.', 'for' => ['import', 'send']],
            'profiles:read' => ['why' => 'Read profiles to import them, and to find the ones to unsubscribe.', 'for' => ['import', 'send']],
            'subscriptions:read' => ['why' => 'Read each profile’s email marketing consent when importing.', 'for' => ['import']],
            'lists:write' => ['why' => 'Add people to the list they are sent to.', 'for' => ['send']],
            'profiles:write' => ['why' => 'Create or update a profile for each person sent.', 'for' => ['send']],
            'subscriptions:write' => ['why' => 'Subscribe or unsubscribe people on the list.', 'for' => ['send']],
        ];
    }

    public function consentNote(): string
    {
        return 'Klaviyo records each profile’s email marketing consent: profiles that are subscribed are subscribed here, profiles that unsubscribed stay unsubscribed, and profiles that never subscribed are imported but not subscribed.';
    }

    public function supportsExport(): bool
    {
        return true;
    }

    public function exportNote(): string
    {
        return 'Send adds your People to Klaviyo as profiles on the chosen list: only people who are subscribed here are subscribed to email marketing there, people who unsubscribed here are unsubscribed from that list (and are not added if Klaviyo does not know them), and nobody who unsubscribed in Klaviyo is re-subscribed. Klaviyo may ask a new subscriber to confirm if the list uses double opt-in.';
    }

    public function checksScopes(): bool
    {
        return true;
    }

    public function checkScopes(Integration $integration): array
    {
        $empty = new \stdClass;
        $probes = [
            'accounts:read' => ['GET', '/accounts/', null],
            'lists:read' => ['GET', '/lists/', null],
            'profiles:read' => ['GET', '/profiles/?page%5Bsize%5D=1', null],
            'subscriptions:read' => ['GET', '/profiles/?page%5Bsize%5D=1&additional-fields%5Bprofile%5D=subscriptions', null],
            // empty on purpose: refused as incomplete when the permission is there, so no list, profile or subscription is made
            'lists:write' => ['POST', '/lists/', ['data' => ['type' => 'list', 'attributes' => $empty]]],
            'profiles:write' => ['POST', '/profile-import/', ['data' => ['type' => 'profile', 'attributes' => $empty]]],
            'subscriptions:write' => ['POST', '/profile-subscription-bulk-create-jobs/', ['data' => ['type' => 'profile-subscription-bulk-create-job', 'attributes' => ['profiles' => ['data' => []]]]]],
        ];

        $result = [];
        foreach ($probes as $scope => [$method, $path, $body]) {
            $response = $this->send($integration, $method, 'https://a.klaviyo.com/api'.$path, array_filter([
                'headers' => $this->headers($integration) + ($body ? ['Content-Type' => 'application/vnd.api+json'] : []), 'json' => $body,
            ]));
            $result[$scope] = match (true) {
                $response->status() === 401 => throw new IntegrationException('Klaviyo no longer accepts this connection. Reconnect it.'),
                $response->status() === 403 => false,
                $response->serverError() => null,
                default => $method === 'GET' ? $response->successful() : ($response->successful() || in_array($response->status(), [400, 422], true)),
            };
        }

        return $result;
    }

    public function hasAudiences(): bool
    {
        return true;
    }

    public function audienceNoun(): string
    {
        return 'list';
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://a.klaviyo.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'private_key')).'|'.$this->option($integration, 'audience_id');
    }

    protected function headers(Integration $i): array
    {
        return [
            'Authorization' => ($this->secret($i, 'oauth') ? 'Bearer ' : 'Klaviyo-API-Key ').$this->secret($i, 'private_key'),
            'revision' => config('services.integrations.klaviyo.api_version', '2025-04-15'), 'Accept' => 'application/vnd.api+json',
        ];
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', 'https://a.klaviyo.com/api'.$path, ['headers' => $this->headers($i), 'query' => $query]);
        if (! $response->successful()) {
            throw $this->refusal($response) ?? $this->failed($response, 'Klaviyo refused the key. Check it, and that it can read Profiles, Subscriptions, Accounts and Lists.', 'Klaviyo could not find that. Check the key is a private API key (it starts with pk_) and the List ID is right.');
        }

        return $response;
    }

    /** When Klaviyo says a permission is missing, say which one (it names it), and what to do about it. */
    protected function refusal($response): ?IntegrationException
    {
        $detail = (string) $response->json('errors.0.detail');
        if ($response->status() !== 403 || ! str_contains($detail, 'scopes')) {
            return null;
        }

        return new IntegrationException('Klaviyo says: “'.rtrim($detail, '.').'”. Switch that permission on for the key or the one-click app in Klaviyo, then reconnect.');
    }

    /** A write (Klaviyo takes JSON:API bodies). */
    protected function write(Integration $i, string $path, array $body)
    {
        return $this->send($i, 'POST', 'https://a.klaviyo.com/api'.$path, ['headers' => $this->headers($i) + ['Content-Type' => 'application/vnd.api+json'], 'json' => $body]);
    }

    /** The cursor of the next page, which Klaviyo gives inside the address of the next page. */
    protected function nextCursor(array $body): ?string
    {
        if ($url = $body['links']['next'] ?? null) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

            return ! empty($q['page']['cursor']) ? (string) $q['page']['cursor'] : null;
        }

        return null;
    }

    /** The account's lists, to choose from: where to import from, and where to send people. */
    public function audienceChoices(Integration $integration): array
    {
        $lists = [];
        $cursor = null;
        for ($page = 0; $page < 50; $page++) { // Klaviyo gives ten lists a page
            $body = $this->get($integration, '/lists/', array_filter(['fields[list]' => 'name', 'page[cursor]' => $cursor]))->json() ?? [];
            foreach ($body['data'] ?? [] as $l) {
                $lists[] = ['id' => (string) $l['id'], 'name' => (string) ($l['attributes']['name'] ?? $l['id']), 'members' => null];
            }
            if (! ($cursor = $this->nextCursor($body))) {
                break;
            }
        }

        return $lists;
    }

    public function test(Integration $integration): string
    {
        $this->get($integration, '/profiles/', ['page[size]' => 1]);
        if ($list = $this->option($integration, 'audience_id')) {
            $this->get($integration, "/lists/$list/", ['fields[list]' => 'name']); // proves the list exists
        }

        try { // the account's name is a nicety: a key without the accounts scope still works
            $name = $this->get($integration, '/accounts/')->json('data.0.attributes.contact_information.organization_name');
        } catch (IntegrationException) {
            $name = null;
        }

        return (string) ($name ?: 'Klaviyo account');
    }

    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $size = 100): array
    {
        $list = $this->option($integration, 'audience_id');
        $query = array_filter([
            'page[size]' => $size, 'page[cursor]' => $cursor, 'additional-fields[profile]' => 'subscriptions',
            // a list's members cannot be filtered by date: they are all read each time, which is safe (people already here are only completed)
            'filter' => $since && ! $list ? 'greater-than(updated,'.$since->copy()->utc()->format('Y-m-d\TH:i:s\Z').')' : null,
        ], fn ($v) => $v !== null);
        $body = $this->get($integration, $list ? "/lists/$list/profiles/" : '/profiles/', $query)->json() ?? [];

        $customers = [];
        foreach ($body['data'] ?? [] as $p) {
            $a = $p['attributes'] ?? [];
            $consent = match ($a['subscriptions']['email']['marketing']['consent'] ?? null) {
                'SUBSCRIBED' => 'subscribed',
                'UNSUBSCRIBED' => 'unsubscribed',
                null => null,
                default => 'none', // never subscribed
            };
            $customers[] = $this->customer($p['id'] ?? '', $a['email'] ?? null, $a['first_name'] ?? null, $a['last_name'] ?? null, $a['phone_number'] ?? null, $consent);
        }

        return [$customers, $this->nextCursor($body)];
    }

    // ------------------------------------------------------------------ sending People

    /** A phone number Klaviyo accepts (international format), or null. */
    protected function phone(Person $person): ?string
    {
        $phone = preg_replace('/[\s().-]/', '', (string) $person->phone);

        return preg_match('/^\+[1-9]\d{6,14}$/', $phone) ? $phone : null;
    }

    /** What Klaviyo is told about a person: only what is filled in. */
    protected function attributes(Person $person, string $email): array
    {
        return array_filter(['email' => $email, 'first_name' => $person->first_name, 'last_name' => $person->last_name, 'phone_number' => $this->phone($person)], fn ($v) => filled($v));
    }

    protected function profileRows(array $emails, array $extra = []): array
    {
        return array_map(fn ($email) => ['type' => 'profile', 'attributes' => ['email' => $email] + $extra], array_values($emails));
    }

    protected function job(string $type, array $emails, string $list, array $extra = []): array
    {
        return ['data' => ['type' => $type, 'attributes' => ['profiles' => ['data' => $this->profileRows($emails, $extra)]], 'relationships' => ['list' => ['data' => ['type' => 'list', 'id' => $list]]]]];
    }

    /**
     * Sends a batch of People to the chosen list. A subscribed person is added or updated as a profile and subscribed to email marketing on the
     * list; an unsubscribed person is only unsubscribed from the list if Klaviyo already knows them (nobody is created for it). Whoever
     * unsubscribed in Klaviyo stays unsubscribed: Klaviyo does not take them back through the API.
     */
    public function export(Integration $integration, iterable $people): array
    {
        $list = $this->option($integration, 'export_audience_id') ?: $this->option($integration, 'audience_id');
        if ($list === '') {
            throw new IntegrationException('Choose the Klaviyo list to send people to (List ID) in this connection’s settings.');
        }
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'error' => null, 'results' => []];
        $denied = 'Klaviyo refused the key. Sending people needs Write access to Profiles, Subscriptions and Lists: use a key with those, or reconnect with one click.';
        $subscribe = []; // person id => [email, profile id]
        $unsubscribe = [];

        $record = function (int $id, string $status, ?string $external, string $message) use (&$stats) {
            $stats[$status]++; // sent | failed | skipped
            $stats['results'][$id] = ['status' => $status, 'external_id' => $external, 'message' => $message];
            if ($status === 'failed') {
                $stats['error'] ??= $message;
            }
        };
        $why = fn ($response, string $fallback) => (string) ($response->json('errors.0.detail') ?? $fallback);

        foreach ($people as $person) {
            $email = mb_strtolower(trim((string) $person->email));
            if ($email === '') {
                $record($person->id, 'skipped', null, 'No email address.');

                continue;
            }

            if ($person->is_subscribed) {
                $response = $this->write($integration, '/profile-import/', ['data' => ['type' => 'profile', 'attributes' => $this->attributes($person, $email)]]);
                if (in_array($response->status(), [401, 403], true)) {
                    throw $this->failed($response, $denied, '');
                }
                $response->successful()
                    ? $subscribe[$person->id] = [$email, (string) $response->json('data.id')]
                    : $record($person->id, 'failed', null, $why($response, 'Klaviyo refused this person.'));

                continue;
            }

            $found = $this->send($integration, 'GET', 'https://a.klaviyo.com/api/profiles/', ['headers' => $this->headers($integration), 'query' => ['filter' => 'equals(email,"'.str_replace('"', '', $email).'")', 'fields[profile]' => 'email']]);
            if (in_array($found->status(), [401, 403], true)) {
                throw $this->failed($found, $denied, '');
            }
            if (! $found->successful()) {
                $record($person->id, 'failed', null, $why($found, 'Klaviyo refused this person.'));
            } elseif ($id = $found->json('data.0.id')) {
                $unsubscribe[$person->id] = [$email, (string) $id];
            } else {
                $record($person->id, 'skipped', null, 'Unsubscribed here and not in Klaviyo.');
            }
        }

        foreach ([
            [$subscribe, 'profile-subscription-bulk-create-job', '/profile-subscription-bulk-create-jobs/', ['subscriptions' => ['email' => ['marketing' => ['consent' => 'SUBSCRIBED']]]], 'Sent as subscribed.'],
            [$unsubscribe, 'profile-subscription-bulk-delete-job', '/profile-subscription-bulk-delete-jobs/', [], 'Marked unsubscribed.'],
        ] as [$group, $type, $path, $extra, $done]) {
            foreach (array_chunk($group, self::JOB_LIMIT, true) as $chunk) {
                $response = $this->write($integration, $path, $this->job($type, array_column($chunk, 0), $list, $extra));
                if (in_array($response->status(), [401, 403], true)) {
                    throw $this->failed($response, $denied, '');
                }
                foreach ($chunk as $id => [$email, $profile]) {
                    $response->successful()
                        ? $record($id, 'sent', $profile, $done)
                        : $record($id, 'failed', null, $why($response, 'Klaviyo refused to change this person’s subscription.'));
                }
            }
        }

        return $stats;
    }
}

<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Carbon\CarbonInterface;

/** Mailchimp audiences: every member of one audience, or of all of them. */
class MailchimpProvider extends Provider
{
    public function key(): string
    {
        return 'mailchimp';
    }

    public function label(): string
    {
        return 'Mailchimp';
    }

    public function category(): string
    {
        return 'email';
    }

    public function description(): string
    {
        return 'Import the subscribers of your Mailchimp audiences, with their subscription status.';
    }

    public function fields(): array
    {
        return [
            ['key' => 'api_key', 'label' => 'API key', 'type' => 'password', 'placeholder' => 'xxxxxxxxxxxxxxxx-us21', 'help' => 'Ends with a dash and a data centre such as -us21. Created under Account → Extras → API keys.', 'secret' => true],
            ['key' => 'audience_id', 'export_required' => true, 'label' => 'Audience ID (optional)', 'type' => 'text', 'placeholder' => 'Leave empty to import every audience', 'help' => 'To import one audience only: in Mailchimp open the audience → Settings → Audience name and defaults, and copy the Audience ID. Connect the same account again with another ID to import another audience separately.', 'secret' => false, 'required' => false],
        ];
    }

    public function steps(): array
    {
        return [
            'In Mailchimp open your profile (bottom left) → Profile → Extras → API keys and choose Create A Key.',
            'Give it a name (for example “FranchiseCare Marketing”) and copy the key. It ends with a dash and a data centre such as -us21.',
            'Optional: to import a single audience, copy its Audience ID from the audience’s settings. Leave it empty for all audiences.',
            'Paste the key (and the ID) here.',
        ];
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://login.mailchimp.com/oauth2/authorize', 'token' => 'https://login.mailchimp.com/oauth2/token', 'token_secret' => 'api_key'];
    }

    /** Mailchimp's OAuth token works as an API key once the data centre of the account is appended to it. */
    public function oauthConnect(Integration $integration, array $tokens, array $input): void
    {
        $meta = \Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->withHeaders(['Authorization' => 'OAuth '.$tokens['access_token']])->get('https://login.mailchimp.com/oauth2/metadata')->json() ?? [];
        $dc = (string) ($meta['dc'] ?? '');
        if (! preg_match('~^[a-z]{2}\d{1,3}$~i', $dc)) {
            throw new IntegrationException('Mailchimp did not say which data centre your account is on. Please try again.');
        }
        $integration->secrets = array_filter(['api_key' => $tokens['access_token'].'-'.strtolower($dc), 'oauth' => true, 'account' => isset($meta['login']['login_id']) ? (string) $meta['login']['login_id'] : null]);
    }

    public function consentNote(): string
    {
        return 'Mailchimp records each member’s status. Members who are subscribed are subscribed here; unsubscribed and cleaned (bounced) members are unsubscribed; pending and transactional members are imported but not subscribed.';
    }

    protected function dataCentre(Integration $i): string
    {
        $key = $this->secret($i, 'api_key');
        $dc = str_contains($key, '-') ? substr($key, strrpos($key, '-') + 1) : '';
        if (! preg_match('~^[a-z]{2}\d{1,3}$~i', $dc)) {
            throw new IntegrationException('That does not look like a Mailchimp API key. It ends with a dash and a data centre, for example -us21.');
        }

        return strtolower($dc);
    }

    public function baseUrl(Integration $integration): string
    {
        return 'https://'.$this->dataCentre($integration).'.api.mailchimp.com';
    }

    public function identity(Integration $integration): string
    {
        return sha1($this->secret($integration, 'api_key')).'|'.$this->option($integration, 'audience_id');
    }

    protected function get(Integration $i, string $path, array $query = [])
    {
        $response = $this->send($i, 'GET', $this->baseUrl($i).'/3.0'.$path, ['basic' => ['anystring', $this->secret($i, 'api_key')], 'query' => $query]);
        if (! $response->successful()) {
            throw $this->failed($response, 'Mailchimp refused the API key. Check it was copied completely.', 'Mailchimp could not find that audience. Check the Audience ID.');
        }

        return $response;
    }

    /** The audiences to read: the chosen one, or all of them. */
    protected function audiences(Integration $i): array
    {
        if ($id = $this->option($i, 'audience_id')) {
            return [$id];
        }

        return array_column((array) ($this->get($i, '/lists', ['count' => 100, 'fields' => 'lists.id'])->json('lists') ?? []), 'id');
    }

    public function supportsExport(): bool
    {
        return true;
    }

    /**
     * Adds or updates each person in the chosen audience. A subscriber's status is only set when they are new to Mailchimp, so someone who
     * unsubscribed there stays unsubscribed. A person who is unsubscribed here is marked unsubscribed in Mailchimp if they are already in it.
     */
    public function export(Integration $integration, iterable $people): array
    {
        $audience = $this->option($integration, 'audience_id');
        if ($audience === '') {
            throw new IntegrationException('Choose the Mailchimp audience to send people to (Audience ID) in this connection’s settings.');
        }
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'error' => null];

        foreach ($people as $person) {
            $email = mb_strtolower(trim((string) $person->email));
            if ($email === '') {
                $stats['skipped']++;

                continue;
            }
            $url = $this->baseUrl($integration)."/3.0/lists/$audience/members/".md5($email);
            $merge = array_filter(['FNAME' => $person->first_name, 'LNAME' => $person->last_name, 'PHONE' => $person->phone], fn ($v) => filled($v));
            $subscribed = (bool) $person->is_subscribed;

            $response = $this->send($integration, $subscribed ? 'PUT' : 'PATCH', $url, [
                'basic' => ['anystring', $this->secret($integration, 'api_key')],
                'json' => $subscribed ? ['email_address' => $email, 'status_if_new' => 'subscribed', 'merge_fields' => (object) $merge] : ['status' => 'unsubscribed'],
            ]);

            if ($response->successful()) {
                $stats['sent']++;
            } elseif (in_array($response->status(), [401, 403], true)) {
                throw $this->failed($response, 'Mailchimp refused the key. Reconnect it in this connection’s settings.', '');
            } elseif ($response->status() === 404 && ! $subscribed) {
                $stats['skipped']++; // unsubscribed here and not in Mailchimp: nothing to do
            } elseif (($response->json('title') ?? '') === 'Member In Compliance State' || ($response->json('title') ?? '') === 'Forgotten Email Not Subscribed') {
                $stats['skipped']++; // Mailchimp will not take them back: they unsubscribed or asked to be forgotten there
            } else {
                $stats['failed']++;
                $stats['error'] ??= (string) ($response->json('detail') ?? 'Mailchimp refused '.$email);
            }
        }

        return $stats;
    }

    public function test(Integration $integration): string
    {
        $account = $this->get($integration, '/', ['fields' => 'account_name'])->json('account_name');
        if ($id = $this->option($integration, 'audience_id')) {
            $this->get($integration, "/lists/$id", ['fields' => 'id']); // proves the audience exists
        }

        return (string) ($account ?: 'Mailchimp');
    }

    /** The cursor is "audience number:offset": the audiences are read one after the other, a page at a time. */
    public function customers(Integration $integration, ?string $cursor, ?CarbonInterface $since, int $count = 500): array
    {
        [$index, $offset] = array_map('intval', array_pad(explode(':', (string) $cursor), 2, 0));
        $audiences = $this->audiences($integration);
        if (! isset($audiences[$index])) {
            return [[], null];
        }

        $body = $this->get($integration, "/lists/{$audiences[$index]}/members", array_filter([
            'count' => $count, 'offset' => $offset, 'fields' => 'members.id,members.email_address,members.status,members.merge_fields,total_items',
            'since_last_changed' => $since ? $since->copy()->utc()->format('Y-m-d\TH:i:sP') : null,
        ], fn ($v) => $v !== null))->json() ?? [];

        $customers = [];
        foreach ($body['members'] ?? [] as $m) {
            $merge = $m['merge_fields'] ?? [];
            $customers[] = $this->customer($m['id'] ?? '', $m['email_address'] ?? null, $merge['FNAME'] ?? null, $merge['LNAME'] ?? null, $merge['PHONE'] ?? null, match ($m['status'] ?? null) {
                'subscribed' => 'subscribed',
                'unsubscribed', 'cleaned' => 'unsubscribed',
                default => 'none', // pending, transactional, archived
            });
        }

        if ($offset + $count < (int) ($body['total_items'] ?? 0)) {
            return [$customers, $index.':'.($offset + $count)];
        }

        return [$customers, isset($audiences[$index + 1]) ? ($index + 1).':0' : null];
    }
}

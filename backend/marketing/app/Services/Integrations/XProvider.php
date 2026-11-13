<?php

namespace App\Services\Integrations;

use App\Models\Integration;

/** X (Twitter) direct messages, sent from one X account. */
class XProvider extends MessagingProvider
{
    public function key(): string
    {
        return 'x';
    }

    public function channel(): string
    {
        return 'x';
    }

    public function label(): string
    {
        return 'X (Twitter) messages';
    }

    public function description(): string
    {
        return 'Send campaigns as direct messages from your X account. Each connection is one X account.';
    }

    public function maxLength(): int
    {
        return 10000;
    }

    public function handleLabel(): string
    {
        return 'X user ID';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'User access token', 'type' => 'password', 'placeholder' => '…', 'help' => 'An OAuth 2.0 user token with dm.write, users.read and tweet.read. These expire after 2 hours, so Connect with X is the better way.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'Choose Connect with X and approve access. The app stays signed in by renewing its token.',
            'Or create an app in the X Developer Portal (paid API access is needed to send messages), enable OAuth 2.0 and paste a user token that includes dm.write.',
            'Each person needs their numeric X user ID on their profile.',
        ];
    }

    protected function rule(): string
    {
        return 'X only delivers a direct message to people who follow you or have allowed messages from anyone. Others are refused by X and show as failed. Sending needs a paid X API plan.';
    }

    public function autoConnect(): ?array
    {
        return ['mode' => 'oauth', 'authorize' => 'https://x.com/i/oauth2/authorize', 'token' => 'https://api.x.com/2/oauth2/token', 'scopes' => ['dm.write', 'dm.read', 'tweet.read', 'users.read', 'offline.access'], 'token_secret' => 'access_token', 'client_auth' => 'basic', 'pkce' => true];
    }

    protected function api(Integration $i): string
    {
        return 'https://api.x.com/2';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')];
    }

    protected function refused(): string
    {
        return 'X refused the token. It needs the dm.write permission and may have expired: connect with X again.';
    }

    public function test(Integration $integration): string
    {
        $me = $this->get($integration, '/users/me');

        return isset($me['data']['username']) ? '@'.$me['data']['username'] : 'X account';
    }

    public function deliver(Integration $integration, string $to, string $text, array $options = []): string
    {
        if (! preg_match('/^[0-9]{1,25}$/', $to)) {
            throw new IntegrationException('The X user ID must be a number.');
        }
        $r = $this->send($integration, 'POST', $this->api($integration).'/dm_conversations/with/'.$to.'/messages', ['headers' => $this->headers($integration), 'json' => ['text' => $text]]);
        if (! $r->successful()) {
            throw $this->reject($r);
        }

        return (string) ($r->json('data.dm_event_id') ?? '');
    }
}

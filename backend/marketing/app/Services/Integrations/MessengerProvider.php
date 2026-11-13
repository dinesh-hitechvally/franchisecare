<?php

namespace App\Services\Integrations;

use App\Models\Integration;

/** Facebook Messenger: one Facebook Page, through the Send API. */
class MessengerProvider extends MessagingProvider
{
    public function key(): string
    {
        return 'messenger';
    }

    public function channel(): string
    {
        return 'messenger';
    }

    public function label(): string
    {
        return 'Facebook Messenger';
    }

    public function description(): string
    {
        return 'Send campaigns as messages from your Facebook Page to people who have messaged it. Each connection is one Page.';
    }

    public function maxLength(): int
    {
        return 2000;
    }

    public function handleLabel(): string
    {
        return 'Messenger ID (PSID)';
    }

    public function fields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Page access token', 'type' => 'password', 'placeholder' => 'EAA…', 'help' => 'A long-lived access token of the Page, with pages_messaging permission.', 'secret' => true],
        ];
    }

    public function steps(): array
    {
        return [
            'In Meta for Developers create a Business app and add the Messenger product.',
            'Connect your Facebook Page to the app and generate a Page access token with pages_messaging, then extend it to a long-lived token.',
            'Paste the token here. The Page name is read from Facebook.',
            'Each person needs their Messenger ID (PSID) on their profile. Facebook gives it when someone first messages your Page.',
        ];
    }

    protected function rule(): string
    {
        return 'Facebook only lets a Page message people who messaged it first, and normally within 24 hours of their last message. Messages outside that window are refused by Facebook and show as failed.';
    }

    protected function api(Integration $i): string
    {
        return 'https://graph.facebook.com/v21.0';
    }

    protected function headers(Integration $i): array
    {
        return ['Authorization' => 'Bearer '.$this->secret($i, 'access_token')];
    }

    protected function refused(): string
    {
        return 'Facebook refused the token. Use a Page access token with the pages_messaging permission.';
    }

    public function test(Integration $integration): string
    {
        $p = $this->get($integration, '/me', ['fields' => 'id,name']);

        return (string) ($p['name'] ?? 'Facebook Page');
    }

    public function deliver(Integration $integration, string $to, string $text, array $options = []): string
    {
        $r = $this->send($integration, 'POST', $this->api($integration).'/me/messages', ['headers' => $this->headers($integration), 'json' => [
            'recipient' => ['id' => $to], 'messaging_type' => 'UPDATE', 'message' => ['text' => $text],
        ]]);
        if (! $r->successful()) {
            throw $this->reject($r);
        }

        return (string) ($r->json('message_id') ?? '');
    }
}

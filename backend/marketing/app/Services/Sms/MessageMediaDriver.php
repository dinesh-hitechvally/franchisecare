<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** MessageMedia (Sinch MessageMedia): REST API v1, signed in with the API key and secret. */
class MessageMediaDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $message = ['content' => $body, 'destination_number' => $to, 'format' => 'SMS'];
        if (! empty($this->c['from'])) {
            $message['source_number'] = $this->c['from']; // without one, MessageMedia sends from its own shared number
        }

        $res = Http::withBasicAuth($this->c['api_key'] ?? '', $this->c['api_secret'] ?? '')->timeout(15)
            ->post('https://api.messagemedia.com/v1/messages', ['messages' => [$message]]);
        if ($res->failed()) {
            throw new RuntimeException($res->json('details.0') ?? $res->json('message') ?? 'MessageMedia error '.$res->status());
        }

        return (string) ($res->json('messages.0.message_id') ?? '');
    }
}

<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MessageBirdDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $res = Http::withHeaders(['Authorization' => 'AccessKey '.($this->c['access_key'] ?? '')])->asForm()->timeout(15)
            ->post('https://rest.messagebird.com/messages', ['originator' => $this->c['from'] ?? '', 'recipients' => ltrim($to, '+'), 'body' => $body]);
        if ($res->failed()) {
            throw new RuntimeException($res->json('errors.0.description') ?? 'MessageBird error '.$res->status());
        }

        return (string) ($res->json('id') ?? '');
    }
}

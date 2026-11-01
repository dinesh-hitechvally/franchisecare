<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelnyxDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $res = Http::withToken($this->c['api_key'] ?? '')->timeout(15)
            ->post('https://api.telnyx.com/v2/messages', ['from' => $this->c['from'] ?? '', 'to' => $to, 'text' => $body]);
        if ($res->failed()) {
            throw new RuntimeException($res->json('errors.0.detail') ?? 'Telnyx error '.$res->status());
        }

        return (string) ($res->json('data.id') ?? '');
    }
}

<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VonageDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $res = Http::asForm()->timeout(15)->post('https://rest.nexmo.com/sms/json', [
            'api_key' => $this->c['api_key'] ?? '', 'api_secret' => $this->c['api_secret'] ?? '',
            'from' => $this->c['from'] ?? '', 'to' => ltrim($to, '+'), 'text' => $body,
        ]);
        $m = $res->json('messages.0') ?? [];
        if ($res->failed() || ($m['status'] ?? '1') !== '0') {
            throw new RuntimeException($m['error-text'] ?? 'Vonage error '.$res->status());
        }

        return (string) ($m['message-id'] ?? '');
    }
}

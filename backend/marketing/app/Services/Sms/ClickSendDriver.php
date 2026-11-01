<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClickSendDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $res = Http::withBasicAuth($this->c['username'] ?? '', $this->c['api_key'] ?? '')->timeout(15)
            ->post('https://rest.clicksend.com/v3/sms/send', ['messages' => [['source' => 'marketing', 'from' => $this->c['from'] ?? '', 'to' => $to, 'body' => $body]]]);
        $m = $res->json('data.messages.0') ?? [];
        if ($res->failed() || ($m['status'] ?? 'FAILED') !== 'SUCCESS') {
            throw new RuntimeException($m['status'] ?? $res->json('response_msg') ?? 'ClickSend error '.$res->status());
        }

        return (string) ($m['message_id'] ?? '');
    }
}

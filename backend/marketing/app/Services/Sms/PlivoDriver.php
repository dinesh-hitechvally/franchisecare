<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlivoDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $id = $this->c['auth_id'] ?? '';
        $res = Http::withBasicAuth($id, $this->c['auth_token'] ?? '')->timeout(15)
            ->post("https://api.plivo.com/v1/Account/{$id}/Message/", ['src' => $this->c['from'] ?? '', 'dst' => ltrim($to, '+'), 'text' => $body]);
        if ($res->failed()) {
            throw new RuntimeException($res->json('error') ?? 'Plivo error '.$res->status());
        }

        return (string) ($res->json('message_uuid.0') ?? '');
    }
}

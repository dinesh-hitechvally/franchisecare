<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SinchDriver implements SmsDriver
{
    public function __construct(protected array $c) {}

    public function send(string $to, string $body): string
    {
        $region = preg_match('/^(us|eu|au|br|ca)$/', (string) ($this->c['region'] ?? '')) ? $this->c['region'] : 'us';
        $plan = rawurlencode($this->c['service_plan_id'] ?? '');
        $res = Http::withToken($this->c['api_token'] ?? '')->timeout(15)
            ->post("https://{$region}.sms.api.sinch.com/xms/v1/{$plan}/batches", ['from' => $this->c['from'] ?? '', 'to' => [$to], 'body' => $body]);
        if ($res->failed()) {
            throw new RuntimeException($res->json('text') ?? 'Sinch error '.$res->status());
        }

        return (string) ($res->json('id') ?? '');
    }
}

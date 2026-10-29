<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioDriver implements SmsDriver
{
    public function __construct(
        protected string $sid,
        protected string $token,
        protected string $from,
    ) {}

    public function send(string $to, string $body): string
    {
        $res = Http::asForm()->withBasicAuth($this->sid, $this->token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                'To' => $to,
                'From' => $this->from,
                'Body' => $body,
            ]);

        if ($res->failed()) {
            throw new RuntimeException($res->json('message') ?? 'Twilio error '.$res->status());
        }

        return $res->json('sid');
    }
}

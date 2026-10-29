<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Dev driver: writes the SMS to the Laravel log instead of sending. */
class LogDriver implements SmsDriver
{
    public function send(string $to, string $body): string
    {
        Log::info("[SMS to {$to}] {$body}");

        return 'log-'.Str::random(10);
    }
}

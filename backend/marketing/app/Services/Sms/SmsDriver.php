<?php

namespace App\Services\Sms;

interface SmsDriver
{
    /** @return string provider message id; throws on failure */
    public function send(string $to, string $body): string;
}

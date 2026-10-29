<?php

namespace App\Services\Sms;

use App\Models\Business;
use RuntimeException;

class SmsManager
{
    public function forBusiness(Business $business): SmsDriver
    {
        return match ($business->setting('sms.provider', 'log')) {
            'twilio' => new TwilioDriver(
                $business->setting('sms.twilio_sid') ?? throw new RuntimeException('Twilio SID not configured'),
                $business->setting('sms.twilio_token') ?? throw new RuntimeException('Twilio token not configured'),
                $business->setting('sms.from') ?? throw new RuntimeException('SMS sender not configured'),
            ),
            default => new LogDriver,
        };
    }
}

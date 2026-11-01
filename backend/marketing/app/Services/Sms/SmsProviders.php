<?php

namespace App\Services\Sms;

/** The SMS services a company can connect: what each needs to log in, and the driver that talks to it. */
class SmsProviders
{
    /** key => name + the login fields (secret fields are never sent back to the browser) */
    public const SPECS = [
        'twilio' => ['name' => 'Twilio', 'fields' => [
            ['key' => 'account_sid', 'label' => 'Account SID'],
            ['key' => 'auth_token', 'label' => 'Auth token', 'secret' => true],
            ['key' => 'from', 'label' => 'Sender number / ID', 'placeholder' => '+15551234567'],
        ]],
        'vonage' => ['name' => 'Vonage', 'fields' => [
            ['key' => 'api_key', 'label' => 'API key'],
            ['key' => 'api_secret', 'label' => 'API secret', 'secret' => true],
            ['key' => 'from', 'label' => 'Sender ID or number', 'placeholder' => 'MyBrand'],
        ]],
        'plivo' => ['name' => 'Plivo', 'fields' => [
            ['key' => 'auth_id', 'label' => 'Auth ID'],
            ['key' => 'auth_token', 'label' => 'Auth token', 'secret' => true],
            ['key' => 'from', 'label' => 'Sender number / ID', 'placeholder' => '+15551234567'],
        ]],
        'telnyx' => ['name' => 'Telnyx', 'fields' => [
            ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
            ['key' => 'from', 'label' => 'Sender number', 'placeholder' => '+15551234567'],
        ]],
        'clicksend' => ['name' => 'ClickSend', 'fields' => [
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'api_key', 'label' => 'API key', 'secret' => true],
            ['key' => 'from', 'label' => 'Sender ID or number', 'placeholder' => 'MyBrand'],
        ]],
        'messagebird' => ['name' => 'MessageBird', 'fields' => [
            ['key' => 'access_key', 'label' => 'Access key', 'secret' => true],
            ['key' => 'from', 'label' => 'Originator (sender)', 'placeholder' => 'MyBrand'],
        ]],
        'sinch' => ['name' => 'Sinch', 'fields' => [
            ['key' => 'service_plan_id', 'label' => 'Service plan ID'],
            ['key' => 'api_token', 'label' => 'API token', 'secret' => true],
            ['key' => 'region', 'label' => 'Region', 'placeholder' => 'us, eu, au, br or ca'],
            ['key' => 'from', 'label' => 'Sender number / ID', 'placeholder' => '+15551234567'],
        ]],
    ];

    public static function keys(): array
    {
        return array_keys(self::SPECS);
    }

    public static function secretFields(string $key): array
    {
        return array_column(array_filter(self::SPECS[$key]['fields'], fn ($f) => ! empty($f['secret'])), 'key');
    }

    /** @param  array<string,mixed>  $c  the saved login of that service */
    public static function driver(string $key, array $c): SmsDriver
    {
        return match ($key) {
            'twilio' => new TwilioDriver($c['account_sid'] ?? '', $c['auth_token'] ?? '', $c['from'] ?? ''),
            'vonage' => new VonageDriver($c),
            'plivo' => new PlivoDriver($c),
            'telnyx' => new TelnyxDriver($c),
            'clicksend' => new ClickSendDriver($c),
            'messagebird' => new MessageBirdDriver($c),
            'sinch' => new SinchDriver($c),
        };
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CyberSource Payment Gateway
    |--------------------------------------------------------------------------
    */
    'cybersource' => [
        'merchant_id' => env('CYBERSOURCE_MERCHANT_ID'),
        'api_key_id' => env('CYBERSOURCE_API_KEY_ID'),
        'secret_key' => env('CYBERSOURCE_SECRET_KEY'),
        'sandbox' => env('CYBERSOURCE_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Xero Accounting Integration
    |--------------------------------------------------------------------------
    */
    'xero' => [
        'client_id' => env('XERO_CLIENT_ID', '0EE15180E2A64AC2878878C6147A6358'),
        'client_secret' => env('XERO_CLIENT_SECRET', 'wcfnkoGlRz7OWUv4zP73ugwdX4bo9qcjX07kuQ72m2UQF35x'),
        'redirect_uri' => env('XERO_REDIRECT_URI','https://127.0.0.1:3000/integration/xero/callback'),
        'scopes'=> [
            'openid',
            'profile',
            'email',
            'accounting.settings',
            'accounting.contacts',
            'accounting.transactions',
            'offline_access'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | MessageMedia SMS Gateway
    |--------------------------------------------------------------------------
    | Get your API credentials from https://developers.messagemedia.com/
    */
    'messagemedia' => [
        'api_key' => env('MESSAGEMEDIA_API_KEY'),
        'api_secret' => env('MESSAGEMEDIA_API_SECRET'),
        'base_url' => env('MESSAGEMEDIA_BASE_URL', 'https://api.messagemedia.com'),
        'source_number' => env('MESSAGEMEDIA_SOURCE_NUMBER'),  // Your dedicated number or null for shared
        'source_name' => env('MESSAGEMEDIA_SOURCE_NAME'),      // Alphanumeric sender ID (max 11 chars)
    ],

];

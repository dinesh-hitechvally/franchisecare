<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // set OUTBOUND_ALLOW_PRIVATE=true only on a dev machine, to let webhooks / SMTP target localhost
    'outbound' => ['allow_private' => (bool) env('OUTBOUND_ALLOW_PRIVATE', false)],

    // "Connect your Google / Microsoft account" for sending email. Register one OAuth app per provider and put its
    // client id/secret in .env; redirect URI = APP_URL/api/oauth/mail/{google|microsoft}/callback
    'mail_oauth' => [
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
        'google' => [
            'client_id' => env('GOOGLE_MAIL_CLIENT_ID'), 
            'client_secret' => env('GOOGLE_MAIL_CLIENT_SECRET')
        ],
        'microsoft' => [
            'client_id' => env('MICROSOFT_MAIL_CLIENT_ID'), 
            'client_secret' => env('MICROSOFT_MAIL_CLIENT_SECRET'), 
            'tenant' => env('MICROSOFT_MAIL_TENANT', 'common')
        ],
    ],

    'images' => [
        'unsplash' => env('UNSPLASH_ACCESS_KEY'),
        'pexels' => env('PEXELS_API_KEY'),
        'pixabay' => env('PIXABAY_API_KEY'),
    ],

];

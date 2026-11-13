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

    // Online platforms whose customers can be imported (Integrations menu). Shopify retires old API versions: set a newer one here when needed.
    'integrations' => ['shopify_version' => env('SHOPIFY_API_VERSION', '2026-04'), 'klaviyo_revision' => env('KLAVIYO_API_REVISION', '2025-04-15')],

    // Platform-owner OAuth apps for "Connect with …" in Integrations. Register one app per platform (redirect URI = APP_URL/api/oauth/integrations/callback)
    // and put its client id/secret in .env. A platform without them keeps the paste-your-key form.
    'integration_oauth' => [
        'shopify' => [
            'client_id' => env('SHOPIFY_CLIENT_ID'), 
            'client_secret' => env('SHOPIFY_CLIENT_SECRET')
        ],
        'hubspot' => [
            'client_id' => env('HUBSPOT_CLIENT_ID'), 
            'client_secret' => env('HUBSPOT_CLIENT_SECRET')
        ],
        'mailchimp' => [
            'client_id' => env('MAILCHIMP_CLIENT_ID'), 'client_secret' => env('MAILCHIMP_CLIENT_SECRET')
        ],
        'x' => ['client_id' => env('X_CLIENT_ID'), 'client_secret' => env('X_CLIENT_SECRET')],
        'klaviyo' => [
            'client_id' => env('KLAVIYO_CLIENT_ID'), 
            'client_secret' => env('KLAVIYO_CLIENT_SECRET')
        ],
        'kit' => [
            'client_id' => env('KIT_CLIENT_ID'), 
            'client_secret' => env('KIT_CLIENT_SECRET')
        ],
        'pipedrive' => [
            'client_id' => env('PIPEDRIVE_CLIENT_ID'), 
            'client_secret' => env('PIPEDRIVE_CLIENT_SECRET')
        ],
        'salesforce' => [
            'client_id' => env('SALESFORCE_CLIENT_ID'), 
            'client_secret' => env('SALESFORCE_CLIENT_SECRET')
        ],
        'square' => [
            'client_id' => env('SQUARE_CLIENT_ID'), 
            'client_secret' => env('SQUARE_CLIENT_SECRET')
        ],
        'stripe' => [
            'client_id' => env('STRIPE_CONNECT_CLIENT_ID'), 
            'client_secret' => env('STRIPE_CONNECT_SECRET_KEY')
        ],
    ],

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

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

    // Everything the Integrations menu needs from the platform owner, one entry per platform. `auth_type` says how the platform is connected, and platforms of the
    // same type are kept together:
    //   oauth   = "Connect with …": client_id and client_secret of the app registered with the platform (redirect URI = APP_URL/api/oauth/integrations/callback).
    //             A platform without them keeps the paste-your-key form.
    //   api_key = the person pastes a key: test keys for development are kept here, the connect form still asks for what the platform needs.
    // `api_version` is the platform's API version, for the ones that retire old versions: set a newer one in .env when needed.
    'integrations' => [
        // ================= auth_type: oauth =================

        'shopify' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('SHOPIFY_CLIENT_ID'),
            'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
            'api_version'   => env('SHOPIFY_API_VERSION', '2026-04'),
        ],

        'hubspot' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('HUBSPOT_CLIENT_ID'),
            'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
        ],

        'mailchimp' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('MAILCHIMP_CLIENT_ID'),
            'client_secret' => env('MAILCHIMP_CLIENT_SECRET'),
        ],

        'klaviyo' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('KLAVIYO_CLIENT_ID'),
            'client_secret' => env('KLAVIYO_CLIENT_SECRET'),
            'api_version'   => env('KLAVIYO_API_REVISION', '2026-10-15'),
        ],

        'kit' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('KIT_CLIENT_ID'),
            'client_secret' => env('KIT_CLIENT_SECRET'),
        ],

        'pipedrive' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('PIPEDRIVE_CLIENT_ID'),
            'client_secret' => env('PIPEDRIVE_CLIENT_SECRET'),
        ],

        'salesforce' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('SALESFORCE_CLIENT_ID'),
            'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
        ],

        'square' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('SQUARE_CLIENT_ID'),
            'client_secret' => env('SQUARE_CLIENT_SECRET'),
        ],

        'stripe' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('STRIPE_CONNECT_CLIENT_ID'),
            'client_secret' => env('STRIPE_CONNECT_SECRET_KEY'),
        ],

        // Etsy: client_id is the app's keystring and client_secret its shared secret (Etsy asks for both on every request).
        'etsy' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('ETSY_CLIENT_ID'),
            'client_secret' => env('ETSY_CLIENT_SECRET'),
        ],

        // eBay: the redirect address is not sent as such but as a "RuName" registered for the app (its "auth accepted URL" is the callback).
        'ebay' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('EBAY_CLIENT_ID'),
            'client_secret' => env('EBAY_CLIENT_SECRET'),
            'ru_name'       => env('EBAY_RUNAME'),
        ],

        'x' => [
            'auth_type'     => 'oauth',
            'client_id'     => env('X_CLIENT_ID'),
            'client_secret' => env('X_CLIENT_SECRET'),
        ],

        // ================= auth_type: api_key =================

        // Wix also needs a site id, which the connect form asks for.
        'wix' => [
            'auth_type'  => 'api_key',
            'api_key'    => env('WIX_API_KEY'),
            'account_id' => env('WIX_ACCOUNT_ID'),
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

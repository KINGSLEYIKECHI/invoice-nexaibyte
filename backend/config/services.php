<?php

return [ 'whatsapp' => ['enabled' => env('WHATSAPP_ENABLED', false), 'token' => env('WHATSAPP_ACCESS_TOKEN'), 'phone_id' => env('WHATSAPP_PHONE_NUMBER_ID'), 'version' => env('WHATSAPP_API_VERSION', 'v23.0'), 'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com')],

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
        'token' => env('POSTMARK_TOKEN'),
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

];

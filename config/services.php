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

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    'broadcast' => [
        // available drivers: 'fonnte', 'custom', 'local'
        'driver' => env('BROADCAST_DRIVER', 'fonnte'),
        // 'local' driver is a dev-only mock that records sends as successful
        'custom' => [
            // POST URL for custom provider
            'url' => env('BROADCAST_CUSTOM_URL'),
            // optional token for header-based auth
            'token' => env('BROADCAST_CUSTOM_TOKEN'),
            // header name to put the token in (defaults to Authorization)
            'token_header' => env('BROADCAST_CUSTOM_TOKEN_HEADER', 'Authorization'),
            // body keys expected by the custom provider (target and message)
            'target_key' => env('BROADCAST_CUSTOM_TARGET_KEY', 'target'),
            'message_key' => env('BROADCAST_CUSTOM_MESSAGE_KEY', 'message'),
        ],
    ],

];

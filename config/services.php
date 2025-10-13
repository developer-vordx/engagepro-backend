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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URL'),
    ],

    'twitter' => [
        'api_key' => env('TWITTER_API_KEY', ''),
        'api_secret' => env('TWITTER_API_SECRET', ''),
        'client_id' => env('TWITTER_CLIENT_ID', ''),
        'client_secret' => env('TWITTER_CLIENT_SECRET', ''),
        'redirect_uri' => env('TWITTER_REDIRECT_URI', ''),
        'bearer_token' => env('TWITTER_BEARER_TOKEN', ''),
        'access_token' => env('TWITTER_ACCESS_TOKEN', ''),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET', ''),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'redirect_uri' => env('META_REDIRECT_URI', ''),
        'access_token' => env('META_ACCESS_TOKEN', ''),
        'graph_version' => env('META_GRAPH_VERSION', ''),
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID', ''),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', ''),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', ''),
        'access_token' => env('YOUTUBE_ACCESS_TOKEN', ''),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN', ''),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
    ],
    'tiktok' => [
        'client_id' => env('TIKTOK_CLIENT_ID'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URL', env('APP_URL') . '/auth/tiktok/callback'),
    ],

];

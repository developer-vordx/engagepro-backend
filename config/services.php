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

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID', ''),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET', ''),
        'redirect_uri' => env('LINKEDIN_REDIRECT_URI', ''),
    ],

    'pinterest' => [
        'client_id' => env('PINTEREST_CLIENT_ID', ''),
        'client_secret' => env('PINTEREST_CLIENT_SECRET', ''),
        'redirect_uri' => env('PINTEREST_REDIRECT_URI', ''),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    ],

    'threads' => [
        'redirect_uri' => env('THREADS_REDIRECT_URI', ''),
        // Uses Meta app_id and app_secret
    ],

    'snapchat' => [
        'client_id' => env('SNAPCHAT_CLIENT_ID', ''),
        'client_secret' => env('SNAPCHAT_CLIENT_SECRET', ''),
        'redirect_uri' => env('SNAPCHAT_REDIRECT_URI', ''),
    ],

    // Placeholders for additional platforms
    'twitch' => [
        'client_id' => env('TWITCH_CLIENT_ID', ''),
        'client_secret' => env('TWITCH_CLIENT_SECRET', ''),
        'redirect_uri' => env('TWITCH_REDIRECT_URI', ''),
    ],
    'vimeo' => [
        'client_id' => env('VIMEO_CLIENT_ID', ''),
        'client_secret' => env('VIMEO_CLIENT_SECRET', ''),
        'redirect_uri' => env('VIMEO_REDIRECT_URI', ''),
    ],
    'reddit' => [
        'client_id' => env('REDDIT_CLIENT_ID', ''),
        'client_secret' => env('REDDIT_CLIENT_SECRET', ''),
        'redirect_uri' => env('REDDIT_REDIRECT_URI', ''),
    ],
    'tumblr' => [
        'client_id' => env('TUMBLR_CLIENT_ID', ''),
        'client_secret' => env('TUMBLR_CLIENT_SECRET', ''),
        'redirect_uri' => env('TUMBLR_REDIRECT_URI', ''),
    ],
    'dailymotion' => [
        'client_id' => env('DAILYMOTION_CLIENT_ID', ''),
        'client_secret' => env('DAILYMOTION_CLIENT_SECRET', ''),
        'redirect_uri' => env('DAILYMOTION_REDIRECT_URI', ''),
    ],
    'odysee' => [
        'api_key' => env('ODYSEE_API_KEY', ''),
    ],
    'truthsocial' => [
        'client_id' => env('TRUTHSOCIAL_CLIENT_ID', ''),
        'client_secret' => env('TRUTHSOCIAL_CLIENT_SECRET', ''),
        'redirect_uri' => env('TRUTHSOCIAL_REDIRECT_URI', ''),
    ],
    'minds' => [
        'client_id' => env('MINDS_CLIENT_ID', ''),
        'client_secret' => env('MINDS_CLIENT_SECRET', ''),
        'redirect_uri' => env('MINDS_REDIRECT_URI', ''),
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

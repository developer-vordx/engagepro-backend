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
        'api_key' => env('TWITTER_API_KEY', 'xg9OiSiOdTWu826i7hszUYdqt'),
        'api_secret' => env('TWITTER_API_SECRET', 'INJP9PyCzmQgOPHN6pWQ18PT23mWwldAJ0PDJvZFPUbvYJUDF0'),
        'client_id' => env('TWITTER_CLIENT_ID', 'RDNFclNJZjZKT3ZabTd2S0NpZWE6MTpjaQ'),
        'client_secret' => env('TWITTER_CLIENT_SECRET', '2HHHzBU1m5ekvBYAYAvlMGNUHe8BcGkK5Q-nn9qd1AgsYCuWyj'),
        'redirect_uri' => env('TWITTER_REDIRECT_URI', 'https://gym-apps-advertisement-traditions.trycloudflare.com/social/twitter/callback'),
        'bearer_token' => env('TWITTER_BEARER_TOKEN', 'AAAAAAAAAAAAAAAAAAAAAB3L3wEAAAAAVpdpA5zAOBeurU9HiChB5R3j%2BO8%3DjX5W1FYErkh9iyktkFY0kGqLGGzEuX8fFZNtCMQZCqdo848'),
        'access_token' => env('TWITTER_ACCESS_TOKEN', '1918546730347319296-0ywXy05oDC0swTqKfExjSI4d5osuLh'),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET', 'PCfKEzwXH6vLFpHdloGHpYUQf6kf3xmNi6Mg9TYKNv7Xf'),
    ],
    
    'meta' => [
        'app_id' => env('META_APP_ID', '1054627326872636'),
        'app_secret' => env('META_APP_SECRET', '6890c66c9f2b23b1fe82a9a9c4b08e35'),
        'redirect_uri' => env('META_REDIRECT_URI', 'https://gym-apps-advertisement-traditions.trycloudflare.com/social/meta/callback'),
        'access_token' => env('META_ACCESS_TOKEN', ''),
        'graph_version' => env('META_GRAPH_VERSION', 'v18.0'),
    ],
    
    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID', '996801631878-av2f0qk8tkofm4ddkvc4sh929sdhdeh7.apps.googleusercontent.com'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', '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'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', 'https://gym-apps-advertisement-traditions.trycloudflare.com/social/youtube/callback'),
        'access_token' => env('YOUTUBE_ACCESS_TOKEN', 'ya29.A0AS3H6NyJanuUJM2LrjCQ5u-WpTv2sCMVyjoqK6vKhQqtoIJSYB58dfdL71H-X0xm51hP-U5TFZGLDnNup802YuhLhdchgiKM2ASxBniDyDr3O5vOYuASqv2nPt-dcVO3koOzFnKhhmYkPCwtjxJshZOo9FWcMZAvAf1DbrA6j5yK0A918f2u-BSZd7WfB5eYFhu5lI4aCgYKAQUSARQSFQHGX2MijdN0G65U5NtAMH-_iCTp3g0206'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN', '1//03jI-mf8Pq4CoCgYIARAAGAMSNwF-L9IrP-y1ra202ukVDslIVLBiG3gfNkN4UFHPmVhgmDQu6k6DOibWcsLoBpYD4hcqPcBKWgc'),
        'api_key' => env('YOUTUBE_API_KEY', ''),
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

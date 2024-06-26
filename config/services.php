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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'rss' => [
        'mentas' => env('RSS_MENTAS', 'https://menthas.com/programming/rss'),
        'hatena_hotentry' => env('RSS_HATENA_HOTENTRY', 'https://b.hatena.ne.jp/hotentry/it.rss'),
        'qiita' => env('RSS_QIITA', 'https://qiita.com/popular-items/feed.atom'),
        'zenn' => env('RSS_ZENN', 'https://zenn.dev/feed'),
    ],

    'lambda' => [
        'endpoint' => env('LAMBDA_ENDPOINT'),
        'authorization' => env('LAMBDA_AUTHORIZATION'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
            'alert_channel' => env('SLACK_BOT_USER_ALERT_CHANNEL'),
        ],
    ],

];

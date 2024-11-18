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
    'cloudflare' => [
        'aws_access_key' => env('CLOUDFLARE_AWS_ACCESS_KEY', 'cbb3e2fea7c7f3e7af09b67eeec7d62c'),
        'aws_secret_key' => env('CLOUDFLARE_AWS_SECRET_KEY', 'b52dcdbea046cc2cc13a5b767a1c71ea8acbe96422b3e45525d3678ce2b5ed3e'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID', '453d5dc9390394015b582d09c1e82365'),
        'bucket' => env('CLOUDFLARE_BUCKET', 'artapp'),
        'cdn_url' => env('CLOUDFLARE_CDN_URL', 'https://artapp.promptme.info'),
    ],
];

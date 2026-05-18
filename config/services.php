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
        'key' => env('RESEND_API_KEY', env('RESEND_KEY')),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    'pesapal' => [
        'base_url' => env('PESAPAL_BASE_URL', 'https://pay.pesapal.com/v3'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'notification_id' => env('PESAPAL_NOTIFICATION_ID'),
        'webhook_secret' => env('PESAPAL_WEBHOOK_SECRET'),
        'callback_url' => env('PESAPAL_CALLBACK_URL', env('APP_URL').'/api/callbacks/pesapal'),
        'non_production_mock_payment_status' => env('PESAPAL_NON_PRODUCTION_MOCK_PAYMENT_STATUS'),
        'non_production_min_subscription_amount_ugx' => env('PESAPAL_NON_PRODUCTION_MIN_SUBSCRIPTION_AMOUNT_UGX', 500),
        'publish_fee_amount_ugx' => env('PESAPAL_PUBLISH_FEE_AMOUNT_UGX', 7500),
        'non_production_min_publish_fee_amount_ugx' => env('PESAPAL_NON_PRODUCTION_MIN_PUBLISH_FEE_AMOUNT_UGX', 500),
        'subscription_grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),
    ],

    'mobile_money' => [
        'provider' => env('MOBILE_MONEY_PROVIDER', 'stub'),
        'webhook_secret' => env('MOBILE_MONEY_WEBHOOK_SECRET'),
    ],

];

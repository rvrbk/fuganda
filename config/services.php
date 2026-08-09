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

    'mobile_money' => [
        'default_provider' => env('MOBILE_MONEY_DEFAULT_PROVIDER', 'mtn'),
        'webhook_secret' => env('MOBILE_MONEY_WEBHOOK_SECRET'),
    ],

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'production_base_url' => env('MTN_MOMO_PRODUCTION_BASE_URL', 'https://momodeveloper.mtn.com'),
        'api_user' => env('MTN_MOMO_API_USER', 'placeholder_api_user'),
        'api_key' => env('MTN_MOMO_API_KEY', 'placeholder_api_key'),
        'merchant_code' => env('MTN_MOMO_MERCHANT_CODE', 'placeholder_merchant_code'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY', 'placeholder_subscription_key'),
        'callback_url' => env('MTN_MOMO_CALLBACK_URL', env('APP_URL').'/api/callbacks/mtn-momo'),
        'webhook_secret' => env('MTN_MOMO_WEBHOOK_SECRET'),
    ],

    'airtel_money' => [
        'base_url' => env('AIRTEL_MONEY_BASE_URL', 'https://openapi.airtel.africa'),
        'client_id' => env('AIRTEL_MONEY_CLIENT_ID', 'placeholder_client_id'),
        'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET', 'placeholder_client_secret'),
        'merchant_code' => env('AIRTEL_MONEY_MERCHANT_CODE', 'placeholder_merchant_code'),
        'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL', env('APP_URL').'/api/callbacks/airtel-money'),
        'webhook_secret' => env('AIRTEL_MONEY_WEBHOOK_SECRET'),
    ],

];

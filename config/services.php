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
    ],

    // Store pages the referral link sends phones to (see ReferralInviteController).
    // Set APP_STORE_URL in .env once the iOS listing exists; until then iPhones
    // fall back to the web registration page.
    'mobile_app' => [
        'play_store' => env('PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=com.app.hamqadam'),
        'app_store' => env('APP_STORE_URL'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('APP_URL').'/social-login/google/callback',
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('APP_URL').'/social-login/facebook/callback',
    ],

    'twitter' => [
        'client_id'     => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect'      => env('APP_URL').'/social-login/twitter/callback',
    ],
    
    'paytm-wallet' => [
        'env' => env('PAYTM_ENVIRONMENT'),
        'merchant_id' => env('PAYTM_MERCHANT_ID'),
        'merchant_key' => env('PAYTM_MERCHANT_KEY'),
        'merchant_website' => env('PAYTM_MERCHANT_WEBSITE'),
        'channel' => env('PAYTM_CHANNEL'),
        'industry_type' => env('PAYTM_INDUSTRY_TYPE'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH', storage_path('app/firebase-service-account.json')),
    ],

    'matchmaking' => [
        'base_url' => rtrim((string) env('MATCHMAKING_BASE_URL', 'https://matchmaking.hamqadam.com'), '/'),
        'timeout'  => (int) env('MATCHMAKING_TIMEOUT', 30),
        'api_key'  => (string) env('MATCHMAKING_API_KEY', ''),
    ],


    'matching' => [
        'scores_valid_after' => env('MATCH_SCORES_VALID_AFTER', '2026-10-27 12:00:00'),
    ],

];

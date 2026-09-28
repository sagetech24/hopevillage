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

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),

        // Alternative auth (recommended): API Key (SK...) + Secret
        'api_key_sid' => env('TWILIO_API_KEY_SID'),
        'api_key_secret' => env('TWILIO_API_KEY_SECRET'),

        // For sandbox, this is usually: whatsapp:+14155238886
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),

        // SMS sender number in E.164 format (e.g. +14155551234) — password reset SMS only
        'sms_from' => env('TWILIO_SMS_FROM'),

        // Optional: if user enters 91234567, we can prepend +65 (set to 65 for Singapore).
        'default_country_code' => env('TWILIO_DEFAULT_COUNTRY_CODE'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL'),
        'api_key' => env('INFOBIP_API_KEY'),
        'sender' => env('INFOBIP_SENDER'),
    ],

    'singpass' => [
        'client_id' => env('SINGPASS_CLIENT_ID'),
        'client_secret' => env('SINGPASS_CLIENT_SECRET'),
        'redirect' => env('SINGPASS_REDIRECT_URI'),
        'authorization_endpoint' => env('SINGPASS_AUTHORIZATION_ENDPOINT', 'https://stg-id.singpass.gov.sg/auth'),
        'token_endpoint' => env('SINGPASS_TOKEN_ENDPOINT', 'https://stg-id.singpass.gov.sg/token'),
        'jwks_uri' => env('SINGPASS_JWKS_URI', 'https://stg-id.singpass.gov.sg/.well-known/keys'),
        'environment' => env('SINGPASS_ENVIRONMENT', 'sandbox'), // sandbox or production
        // Client JWKS: public keys for Singpass to reach (PX-E0101). Set via SINGPASS_CLIENT_JWKS_JSON or run php artisan singpass:generate-jwks
        'client_jwks_json' => env('SINGPASS_CLIENT_JWKS_JSON'),
    ],

];

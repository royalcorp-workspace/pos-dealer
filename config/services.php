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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    ],

    'expeditions' => [
        'sap' => [
            'base_url' => env('SAP_EXPEDITION_BASE_URL'),
            'token' => env('SAP_EXPEDITION_TOKEN'),
        ],
        'jne' => [
            'base_url' => env('JNE_EXPEDITION_BASE_URL'),
            'token' => env('JNE_EXPEDITION_TOKEN'),
        ],
        'jt' => [
            'base_url' => env('JT_EXPEDITION_BASE_URL'),
            'token' => env('JT_EXPEDITION_TOKEN'),
        ],
        'sicepat' => [
            'base_url' => env('SICEPAT_EXPEDITION_BASE_URL'),
            'token' => env('SICEPAT_EXPEDITION_TOKEN'),
        ],
        'anteraja' => [
            'base_url' => env('ANTERAJA_EXPEDITION_BASE_URL'),
            'token' => env('ANTERAJA_EXPEDITION_TOKEN'),
        ],
    ],

    'biteship' => [
        'api_key' => env('BITESHIP_API_KEY'),
        'base_url' => env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
        'origin_area_id' => env('BITESHIP_ORIGIN_AREA_ID'),
        'origin_postal_code' => env('BITESHIP_ORIGIN_POSTAL_CODE', '40552'),
        'origin_latitude' => env('BITESHIP_ORIGIN_LATITUDE'),
        'origin_longitude' => env('BITESHIP_ORIGIN_LONGITUDE'),
        'origin_address' => env('BITESHIP_ORIGIN_ADDRESS', 'Jl. Raya Barat, Cimareme, Kec. Ngamprah, Kabupaten Bandung Barat'),
        'origin_contact_name' => env('BITESHIP_ORIGIN_CONTACT_NAME', 'Admin Gudang IMG'),
        'origin_contact_phone' => env('BITESHIP_ORIGIN_CONTACT_PHONE', '081112345678'),
    ],

];

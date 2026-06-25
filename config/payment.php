<?php

return [
    'methods' => [
        'bank_transfer' => [
            'bca' => ['name' => 'BCA', 'code' => 'bca', 'icon' => 'bank'],
            'mandiri' => ['name' => 'Mandiri', 'code' => 'mandiri', 'icon' => 'bank'],
            'bni' => ['name' => 'BNI', 'code' => 'bni', 'icon' => 'bank'],
            'bri' => ['name' => 'BRI', 'code' => 'bri', 'icon' => 'bank'],
        ],
        'ewallet' => [
            'gopay' => ['name' => 'GoPay', 'code' => 'gopay', 'icon' => 'wallet'],
            'ovo' => ['name' => 'OVO', 'code' => 'ovo', 'icon' => 'wallet'],
            'dana' => ['name' => 'DANA', 'code' => 'dana', 'icon' => 'wallet'],
            'shopeepay' => ['name' => 'ShopeePay', 'code' => 'shopeepay', 'icon' => 'wallet'],
        ],
    ],
    
    'gateway' => [
        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        ],
        'duitku' => [
            'merchant_code' => env('DUITKU_MERCHANT_CODE'),
            'api_key' => env('DUITKU_API_KEY'),
            'is_production' => env('DUITKU_IS_PRODUCTION', false),
        ],
    ],
];
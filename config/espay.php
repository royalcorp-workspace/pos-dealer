<?php

return [
    'base_url' => env('ESPAY_BASE_URL', 'https://sandbox-api.espay.id/rest/merchant/'),
    'js_url' => env('ESPAY_JS_URL', 'https://sandbox-kit.espay.id/public/signature/js'),
    'merchant_key' => env('ESPAY_MERCHANT_KEY', ''),
    'signature_key' => env('ESPAY_SIGNATURE_KEY', ''),
];

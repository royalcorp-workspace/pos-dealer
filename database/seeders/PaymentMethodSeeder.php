<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'bca_va',
                'name' => 'BCA Virtual Account',
                'type' => 2,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-bca.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 4,
                'minimum_amount' => 10000,
                'sort_order' => 1,
            ],
            [
                'code' => 'mandiri_bill',
                'name' => 'Mandiri Bill',
                'type' => 1,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-mandiri.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 4,
                'minimum_amount' => 10000,
                'sort_order' => 2,
            ],
            [
                'code' => 'gopay',
                'name' => 'GoPay',
                'type' => 3,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-gopay.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 2,
                'minimum_amount' => 1000,
                'sort_order' => 3,
            ],
            [
                'code' => 'ovo',
                'name' => 'OVO',
                'type' => 3,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-ovo.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 2,
                'minimum_amount' => 1000,
                'sort_order' => 4,
            ],
            [
                'code' => 'dana',
                'name' => 'DANA',
                'type' => 3,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-dana.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 2,
                'minimum_amount' => 1000,
                'sort_order' => 5,
            ],
            [
                'code' => 'qris',
                'name' => 'QRIS',
                'type' => 4,
                'provider' => 'Midtrans',
                'image' => 'https://midtrans.com/assets/images/logo-qris.svg',
                'has_charge' => true,
                'charge_type' => 1,
                'charge_value' => 0.75,
                'minimum_amount' => 1000,
                'sort_order' => 6,
            ],
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'type' => 7,
                'provider' => 'Manual',
                'has_charge' => false,
                'minimum_amount' => 0,
                'sort_order' => 10,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
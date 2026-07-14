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
                'code' => 'transfer_manual',
                'name' => 'Transfer Bank Manual',
                'type' => 1,
                'provider' => 'Manual',
                'image' => 'https://img.icons8.com/color/48/bank-transfer.png',
                'has_charge' => false,
                'minimum_amount' => 0,
                'sort_order' => 0,
                'status' => 1,
                'deleted' => false,
                'bank_info' => [
                    [
                        'bank_name' => 'BCA',
                        'account_number' => '123-456-7890',
                        'account_holder' => 'PT POS Dealer Indonesia',
                    ],
                    [
                        'bank_name' => 'Mandiri',
                        'account_number' => '987-654-3210',
                        'account_holder' => 'PT POS Dealer Indonesia',
                    ]
                ],
            ],
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
                'status' => 1,
                'deleted' => false,
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
                'status' => 1,
                'deleted' => false,
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
                'status' => 1,
                'deleted' => false,
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
                'status' => 1,
                'deleted' => false,
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
                'status' => 1,
                'deleted' => false,
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
                'status' => 1,
                'deleted' => false,
            ],
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'type' => 7,
                'provider' => 'Manual',
                'has_charge' => false,
                'minimum_amount' => 0,
                'sort_order' => 10,
                'status' => 1,
                'deleted' => false,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['code' => $method['code']], $method);
        }
    }
}

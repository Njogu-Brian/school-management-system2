<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'code' => 'CASH',
                'requires_reference' => false,
                'is_online' => false,
                'is_active' => true,
                'display_order' => 1,
                'description' => 'Cash payment',
            ],
            [
                'name' => 'M-Pesa',
                'code' => 'MPESA',
                'requires_reference' => true,
                'is_online' => true,
                'is_active' => true,
                'display_order' => 2,
                'description' => 'M-Pesa mobile money payment',
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'BANK_TRANSFER',
                'requires_reference' => true,
                'is_online' => false,
                'is_active' => true,
                'display_order' => 3,
                'description' => 'Bank transfer / EFT',
            ],
            [
                'name' => 'Cheque',
                'code' => 'CHEQUE',
                'requires_reference' => true,
                'is_online' => false,
                'is_active' => true,
                'display_order' => 4,
                'description' => 'Cheque payment',
            ],
            [
                'name' => 'Bank Slip',
                'code' => 'BANK_SLIP',
                'requires_reference' => true,
                'is_online' => false,
                'is_active' => true,
                'display_order' => 5,
                'description' => 'Bank deposit slip',
            ],
            [
                'name' => 'Stripe',
                'code' => 'STRIPE',
                'requires_reference' => false,
                'is_online' => true,
                'is_active' => true,
                'display_order' => 6,
                'description' => 'Stripe online payment',
            ],
            [
                'name' => 'PayPal',
                'code' => 'PAYPAL',
                'requires_reference' => false,
                'is_online' => true,
                'is_active' => true,
                'display_order' => 7,
                'description' => 'PayPal online payment',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}


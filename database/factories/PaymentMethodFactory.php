<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Cash', 'M-Pesa', 'Bank Transfer', 'Cheque', 'Card']),
            'type' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money', 'card']),
            'is_active' => true,
            'settings' => [],
        ];
    }
}


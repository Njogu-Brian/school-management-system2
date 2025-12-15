<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Student;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'payment_method_id' => PaymentMethod::factory(),
            'reference' => 'REF-' . fake()->unique()->numerify('#######'),
            'payer_name' => fake()->name(),
            'payer_type' => fake()->randomElement(['parent', 'sponsor', 'student', 'other']),
            'narration' => fake()->sentence(),
            'allocated_amount' => 0,
            'unallocated_amount' => 0,
        ];
    }
}


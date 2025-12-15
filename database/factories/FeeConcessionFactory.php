<?php

namespace Database\Factories;

use App\Models\FeeConcession;
use App\Models\Student;
use App\Models\Votehead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeConcessionFactory extends Factory
{
    protected $model = FeeConcession::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'votehead_id' => Votehead::factory(),
            'type' => fake()->randomElement(['percentage', 'fixed_amount']),
            'discount_type' => fake()->randomElement(['sibling', 'referral', 'early_repayment', 'transport', 'manual', 'other']),
            'frequency' => fake()->randomElement(['termly', 'yearly', 'once', 'manual']),
            'scope' => fake()->randomElement(['votehead', 'invoice', 'student', 'family']),
            'value' => fake()->randomFloat(2, 5, 50),
            'reason' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}


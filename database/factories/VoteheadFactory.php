<?php

namespace Database\Factories;

use App\Models\Votehead;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoteheadFactory extends Factory
{
    protected $model = Votehead::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('VH###')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Tuition', 'Boarding', 'Transport', 'Library', 'Sports', 'Other']),
            'is_mandatory' => fake()->boolean(80),
            'charge_type' => fake()->randomElement(['per_student', 'once', 'once_annually', 'per_family']),
            'default_amount' => fake()->randomFloat(2, 1000, 10000),
            'is_active' => true,
        ];
    }
}


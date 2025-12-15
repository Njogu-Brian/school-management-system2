<?php

namespace Database\Factories;

use App\Models\Academics\Stream;
use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamFactory extends Factory
{
    protected $model = Stream::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['East', 'West', 'North', 'South', 'A', 'B', 'C', 'D']),
            'classroom_id' => Classroom::factory(),
            'capacity' => fake()->numberBetween(15, 30),
            'is_active' => true,
        ];
    }
}


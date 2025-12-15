<?php

namespace Database\Factories;

use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8']),
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(20, 50),
            'is_active' => true,
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'admission_number' => 'ADM-' . fake()->unique()->numerify('######'),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'classroom_id' => Classroom::factory(),
            'stream_id' => Stream::factory(),
            'family_id' => Family::factory(),
            'status' => 'active',
            'admission_date' => fake()->dateTimeBetween('-5 years', 'now'),
        ];
    }
}


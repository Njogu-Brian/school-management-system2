<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'family_name' => fake()->lastName() . ' Family',
            'primary_guardian_name' => fake()->name(),
            'primary_guardian_phone' => fake()->phoneNumber(),
            'primary_guardian_email' => fake()->email(),
            'address' => fake()->address(),
        ];
    }
}


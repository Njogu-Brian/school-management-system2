<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name' => 'Route ' . $this->faker->unique()->randomLetter . strtoupper($this->faker->randomLetter),
            'area' => $this->faker->city,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'vehicle_number' => strtoupper('K' . $this->faker->bothify('??###')),
            'driver_name' => $this->faker->name,
        ];
    }
}


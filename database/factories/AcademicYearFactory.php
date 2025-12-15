<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2030);
        return [
            'name' => "{$year}/" . ($year + 1),
            'year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => ($year + 1) . "-12-31",
            'is_active' => fake()->boolean(50),
        ];
    }
}


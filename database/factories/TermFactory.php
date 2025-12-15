<?php

namespace Database\Factories;

use App\Models\Term;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();
        $termNumber = fake()->numberBetween(1, 3);

        return [
            'academic_year_id' => $year->id,
            'name' => "Term {$termNumber}",
            'term_number' => $termNumber,
            'start_date' => fake()->dateTimeBetween("{$year->year}-01-01", "{$year->year}-12-31"),
            'end_date' => fake()->dateTimeBetween("{$year->year}-01-01", "{$year->year}-12-31"),
            'is_active' => true,
        ];
    }
}


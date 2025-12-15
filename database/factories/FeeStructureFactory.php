<?php

namespace Database\Factories;

use App\Models\FeeStructure;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();
        $term = Term::factory()->create(['academic_year_id' => $year->id]);

        return [
            'classroom_id' => Classroom::factory(),
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'year' => $year->year,
            'name' => fake()->words(3, true) . ' Fee Structure',
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by_user_id' => User::factory(),
        ];
    }
}


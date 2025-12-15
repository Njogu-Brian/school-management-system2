<?php

namespace Database\Factories;

use App\Models\FeePostingRun;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeePostingRunFactory extends Factory
{
    protected $model = FeePostingRun::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();
        $term = Term::factory()->create(['academic_year_id' => $year->id]);

        return [
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'run_type' => 'commit',
            'status' => 'completed',
            'posted_by' => User::factory(),
            'posted_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'items_posted_count' => fake()->numberBetween(1, 100),
            'total_amount_posted' => fake()->randomFloat(2, 100000, 1000000),
            'total_students_affected' => fake()->numberBetween(1, 50),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}


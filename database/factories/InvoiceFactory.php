<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();
        $term = Term::factory()->create(['academic_year_id' => $year->id]);

        return [
            'student_id' => Student::factory(),
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'year' => $year->year,
            'term' => 1,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('#######'),
            'total' => fake()->randomFloat(2, 10000, 100000),
            'paid_amount' => 0,
            'balance' => 0,
            'status' => 'unpaid',
            'issued_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}


<?php

namespace Database\Seeders;

use App\Models\Academics\Exam;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
{
    $this->call([
        // GradingSchemeSeeder::class,
        // SubjectGroupSeeder::class,
        // SubjectSeeder::class,
        // DemoExamSeeder::class,
        // DemoAcademicsSeeder::class,
        // ExamGradeSeeder::class,
        BehaviourSeeder::class,
    ]);
}


}

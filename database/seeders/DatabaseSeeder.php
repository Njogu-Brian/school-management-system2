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
        // BehaviourSeeder::class,
        TeacherPermissionsSeeder::class,
        // Comprehensive CBC curriculum seeder (includes Learning Areas, Strands, Substrands, Competencies)
        CBCComprehensiveSeeder::class,
        // Alternative: Use individual seeders if needed
        // CBCLearningAreasSeeder::class,
        // CBCStrandsSeeder::class,
        // CBCSubstrandSeeder::class,
        // CompetencySeeder::class,
    ]);
}


}

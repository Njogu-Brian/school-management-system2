<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
{
    $this->call([
        GradingSchemeSeeder::class,
        SubjectGroupSeeder::class,
        SubjectSeeder::class,
        DemoExamSeeder::class,
        DemoAcademicsSeeder::class,
    ]);
}


}

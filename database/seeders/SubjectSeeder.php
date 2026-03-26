<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language'],
            ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language'],
            ['code' => 'MAT', 'name' => 'Mathematics', 'learning_area' => 'Mathematical'],
            ['code' => 'SCI', 'name' => 'Science & Technology', 'learning_area' => 'Science'],
            ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religion'],
            ['code' => 'SST', 'name' => 'Social Studies', 'learning_area' => 'Social'],
            ['code' => 'ART', 'name' => 'Creative Arts', 'learning_area' => 'Arts'],
            ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical'],
        ];

        foreach ($subjects as $sub) {
            Subject::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'name' => $sub['name'],
                    'learning_area' => $sub['learning_area'],
                    'level' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}

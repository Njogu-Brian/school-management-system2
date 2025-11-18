<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\LearningArea;
use Illuminate\Support\Facades\DB;

class LearningAreaSeeder extends Seeder
{
    public function run(): void
    {
        $learningAreas = [
            // Pre-Primary
            [
                'code' => 'ENG',
                'name' => 'English Language Activities',
                'description' => 'English Language Activities for Pre-Primary and Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 1,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'KIS',
                'name' => 'Kiswahili Language Activities',
                'description' => 'Kiswahili Language Activities for Pre-Primary and Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 2,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'MATH',
                'name' => 'Mathematics',
                'description' => 'Mathematics for Pre-Primary and Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 3,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'ENV',
                'name' => 'Environmental Activities',
                'description' => 'Environmental Activities for Pre-Primary and Lower Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 4,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'CRE',
                'name' => 'Christian Religious Education',
                'description' => 'Christian Religious Education',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 5,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'PE',
                'name' => 'Physical Education',
                'description' => 'Physical Education and Sports',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 6,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'ART',
                'name' => 'Art and Craft',
                'description' => 'Art and Craft for Pre-Primary and Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 7,
                'is_active' => true,
                'is_core' => true,
            ],
            // Upper Primary
            [
                'code' => 'SCI',
                'name' => 'Science and Technology',
                'description' => 'Science and Technology for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 8,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'SS',
                'name' => 'Social Studies',
                'description' => 'Social Studies for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 9,
                'is_active' => true,
                'is_core' => true,
            ],
            // Junior Secondary
            [
                'code' => 'JS-ENG',
                'name' => 'English',
                'description' => 'English for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 10,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-KIS',
                'name' => 'Kiswahili',
                'description' => 'Kiswahili for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 11,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-MATH',
                'name' => 'Mathematics',
                'description' => 'Mathematics for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 12,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-SCI',
                'name' => 'Integrated Science',
                'description' => 'Integrated Science for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 13,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-SS',
                'name' => 'Social Studies',
                'description' => 'Social Studies (History and Government, Geography) for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 14,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-CRE',
                'name' => 'Christian Religious Education',
                'description' => 'Christian Religious Education for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 15,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-PE',
                'name' => 'Physical Education and Sports',
                'description' => 'Physical Education and Sports for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 16,
                'is_active' => true,
                'is_core' => true,
            ],
            [
                'code' => 'JS-LIFE',
                'name' => 'Life Skills Education',
                'description' => 'Life Skills Education for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 17,
                'is_active' => true,
                'is_core' => true,
            ],
        ];

        foreach ($learningAreas as $area) {
            LearningArea::updateOrCreate(
                ['code' => $area['code']],
                $area
            );
        }

        $this->command->info('Learning Areas seeded successfully.');
    }
}


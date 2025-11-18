<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\LearningArea;
use Illuminate\Support\Facades\DB;

class CBCLearningAreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $learningAreas = [
            // Lower Primary (Grade 1-3)
            [
                'code' => 'ENG',
                'name' => 'English Language Activities',
                'description' => 'English Language Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 1,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'KIS',
                'name' => 'Kiswahili Language Activities',
                'description' => 'Kiswahili Language Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 2,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MATH',
                'name' => 'Mathematics Activities',
                'description' => 'Mathematics Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 3,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ENV',
                'name' => 'Environmental Activities',
                'description' => 'Environmental Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 4,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'HRE',
                'name' => 'Hygiene and Nutrition Activities',
                'description' => 'Hygiene and Nutrition Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 5,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CRE',
                'name' => 'Religious Education Activities',
                'description' => 'Religious Education Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 6,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MSC',
                'name' => 'Movement and Creative Activities',
                'description' => 'Movement and Creative Activities for Lower Primary',
                'level_category' => 'Lower Primary',
                'levels' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'display_order' => 7,
                'is_core' => true,
                'is_active' => true,
            ],

            // Upper Primary (Grade 4-6)
            [
                'code' => 'ENG',
                'name' => 'English',
                'description' => 'English Language for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 1,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'KIS',
                'name' => 'Kiswahili',
                'description' => 'Kiswahili Language for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 2,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MATH',
                'name' => 'Mathematics',
                'description' => 'Mathematics for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 3,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SCI',
                'name' => 'Science and Technology',
                'description' => 'Science and Technology for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 4,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SS',
                'name' => 'Social Studies',
                'description' => 'Social Studies for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 5,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CRE',
                'name' => 'Christian Religious Education',
                'description' => 'Christian Religious Education for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 6,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'IRE',
                'name' => 'Islamic Religious Education',
                'description' => 'Islamic Religious Education for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 7,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'HPE',
                'name' => 'Home Science',
                'description' => 'Home Science for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 8,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ART',
                'name' => 'Art and Craft',
                'description' => 'Art and Craft for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 9,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MSC',
                'name' => 'Music',
                'description' => 'Music for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 10,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PE',
                'name' => 'Physical and Health Education',
                'description' => 'Physical and Health Education for Upper Primary',
                'level_category' => 'Upper Primary',
                'levels' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'display_order' => 11,
                'is_core' => true,
                'is_active' => true,
            ],
        ];

        foreach ($learningAreas as $area) {
            LearningArea::updateOrCreate(
                ['code' => $area['code'], 'level_category' => $area['level_category']],
                $area
            );
        }
    }
}


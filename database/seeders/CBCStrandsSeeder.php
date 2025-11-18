<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\LearningArea;
use Illuminate\Support\Facades\DB;

class CBCStrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // English Language Activities - Lower Primary
        $englishLA = LearningArea::where('code', 'ENG')->where('level_category', 'Lower Primary')->first();
        if ($englishLA) {
            $strands = [
                [
                    'code' => 'ENG.L1.S1',
                    'name' => 'Listening and Speaking',
                    'description' => 'Listening and Speaking Strand for English Language Activities',
                    'learning_area' => 'English Language Activities',
                    'learning_area_id' => $englishLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 1,
                    'is_active' => true,
                ],
                [
                    'code' => 'ENG.L1.S2',
                    'name' => 'Reading',
                    'description' => 'Reading Strand for English Language Activities',
                    'learning_area' => 'English Language Activities',
                    'learning_area_id' => $englishLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'code' => 'ENG.L1.S3',
                    'name' => 'Writing',
                    'description' => 'Writing Strand for English Language Activities',
                    'learning_area' => 'English Language Activities',
                    'learning_area_id' => $englishLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ];

            foreach ($strands as $strand) {
                CBCStrand::updateOrCreate(
                    ['code' => $strand['code']],
                    $strand
                );
            }
        }

        // Mathematics Activities - Lower Primary
        $mathLA = LearningArea::where('code', 'MATH')->where('level_category', 'Lower Primary')->first();
        if ($mathLA) {
            $strands = [
                [
                    'code' => 'MATH.L1.S1',
                    'name' => 'Numbers',
                    'description' => 'Numbers Strand for Mathematics Activities',
                    'learning_area' => 'Mathematics Activities',
                    'learning_area_id' => $mathLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 1,
                    'is_active' => true,
                ],
                [
                    'code' => 'MATH.L1.S2',
                    'name' => 'Measurement',
                    'description' => 'Measurement Strand for Mathematics Activities',
                    'learning_area' => 'Mathematics Activities',
                    'learning_area_id' => $mathLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 2,
                    'is_active' => true,
                ],
                [
                    'code' => 'MATH.L1.S3',
                    'name' => 'Geometry',
                    'description' => 'Geometry Strand for Mathematics Activities',
                    'learning_area' => 'Mathematics Activities',
                    'learning_area_id' => $mathLA->id,
                    'level' => 'Grade 1',
                    'display_order' => 3,
                    'is_active' => true,
                ],
            ];

            foreach ($strands as $strand) {
                CBCStrand::updateOrCreate(
                    ['code' => $strand['code']],
                    $strand
                );
            }
        }

        // Add more strands for other learning areas and levels as needed
    }
}


<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\CBCStrand;
use Illuminate\Support\Facades\DB;

class CBCStrandSeeder extends Seeder
{
    public function run(): void
    {
        $strands = [
            // Pre-Primary (PP1, PP2)
            ['code' => 'LA1', 'name' => 'Listening and Speaking', 'learning_area' => 'Language', 'level' => 'PP1', 'display_order' => 1],
            ['code' => 'LA2', 'name' => 'Reading', 'learning_area' => 'Language', 'level' => 'PP1', 'display_order' => 2],
            ['code' => 'LA3', 'name' => 'Writing', 'learning_area' => 'Language', 'level' => 'PP1', 'display_order' => 3],
            ['code' => 'MA1', 'name' => 'Numbers', 'learning_area' => 'Mathematics', 'level' => 'PP1', 'display_order' => 1],
            ['code' => 'MA2', 'name' => 'Measurement', 'learning_area' => 'Mathematics', 'level' => 'PP1', 'display_order' => 2],
            ['code' => 'ENV1', 'name' => 'Environmental Awareness', 'learning_area' => 'Environmental', 'level' => 'PP1', 'display_order' => 1],
            ['code' => 'CRE1', 'name' => 'Religious Activities', 'learning_area' => 'Religious', 'level' => 'PP1', 'display_order' => 1],
            ['code' => 'PE1', 'name' => 'Psychomotor Activities', 'learning_area' => 'Physical', 'level' => 'PP1', 'display_order' => 1],

            // Lower Primary (Grade 1-3)
            ['code' => 'ENG1', 'name' => 'Listening and Speaking', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'ENG2', 'name' => 'Reading', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 2],
            ['code' => 'ENG3', 'name' => 'Writing', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 3],
            ['code' => 'KIS1', 'name' => 'Kusikiliza na Kuzungumza', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'KIS2', 'name' => 'Kusoma', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 2],
            ['code' => 'KIS3', 'name' => 'Kuandika', 'learning_area' => 'Language', 'level' => 'Grade 1', 'display_order' => 3],
            ['code' => 'MATH1', 'name' => 'Numbers', 'learning_area' => 'Mathematics', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'MATH2', 'name' => 'Measurement', 'learning_area' => 'Mathematics', 'level' => 'Grade 1', 'display_order' => 2],
            ['code' => 'MATH3', 'name' => 'Geometry', 'learning_area' => 'Mathematics', 'level' => 'Grade 1', 'display_order' => 3],
            ['code' => 'ENV1', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'CRE1', 'name' => 'CRE', 'learning_area' => 'Religious', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'PE1', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'level' => 'Grade 1', 'display_order' => 1],
            ['code' => 'ART1', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'level' => 'Grade 1', 'display_order' => 1],

            // Upper Primary (Grade 4-6) - Same strands, different levels
            ['code' => 'ENG1', 'name' => 'Listening and Speaking', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'ENG2', 'name' => 'Reading', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 2],
            ['code' => 'ENG3', 'name' => 'Writing', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 3],
            ['code' => 'KIS1', 'name' => 'Kusikiliza na Kuzungumza', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'KIS2', 'name' => 'Kusoma', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 2],
            ['code' => 'KIS3', 'name' => 'Kuandika', 'learning_area' => 'Language', 'level' => 'Grade 4', 'display_order' => 3],
            ['code' => 'MATH1', 'name' => 'Numbers', 'learning_area' => 'Mathematics', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'MATH2', 'name' => 'Measurement', 'learning_area' => 'Mathematics', 'level' => 'Grade 4', 'display_order' => 2],
            ['code' => 'MATH3', 'name' => 'Geometry', 'learning_area' => 'Mathematics', 'level' => 'Grade 4', 'display_order' => 3],
            ['code' => 'SCI1', 'name' => 'Living Things', 'learning_area' => 'Science', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'SCI2', 'name' => 'Matter', 'learning_area' => 'Science', 'level' => 'Grade 4', 'display_order' => 2],
            ['code' => 'SCI3', 'name' => 'Energy', 'learning_area' => 'Science', 'level' => 'Grade 4', 'display_order' => 3],
            ['code' => 'SS1', 'name' => 'Social Studies', 'learning_area' => 'Social', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'CRE1', 'name' => 'CRE', 'learning_area' => 'Religious', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'PE1', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'level' => 'Grade 4', 'display_order' => 1],
            ['code' => 'ART1', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'level' => 'Grade 4', 'display_order' => 1],

            // Junior Secondary (Grade 7-9)
            ['code' => 'ENG1', 'name' => 'Listening and Speaking', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'ENG2', 'name' => 'Reading', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 2],
            ['code' => 'ENG3', 'name' => 'Writing', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 3],
            ['code' => 'ENG4', 'name' => 'Grammar', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 4],
            ['code' => 'KIS1', 'name' => 'Kusikiliza na Kuzungumza', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'KIS2', 'name' => 'Kusoma', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 2],
            ['code' => 'KIS3', 'name' => 'Kuandika', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 3],
            ['code' => 'KIS4', 'name' => 'Sarufi', 'learning_area' => 'Language', 'level' => 'Grade 7', 'display_order' => 4],
            ['code' => 'MATH1', 'name' => 'Numbers', 'learning_area' => 'Mathematics', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'MATH2', 'name' => 'Algebra', 'learning_area' => 'Mathematics', 'level' => 'Grade 7', 'display_order' => 2],
            ['code' => 'MATH3', 'name' => 'Geometry', 'learning_area' => 'Mathematics', 'level' => 'Grade 7', 'display_order' => 3],
            ['code' => 'MATH4', 'name' => 'Statistics', 'learning_area' => 'Mathematics', 'level' => 'Grade 7', 'display_order' => 4],
            ['code' => 'INTSCI1', 'name' => 'Living Things', 'learning_area' => 'Science', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'INTSCI2', 'name' => 'Matter', 'learning_area' => 'Science', 'level' => 'Grade 7', 'display_order' => 2],
            ['code' => 'INTSCI3', 'name' => 'Energy', 'learning_area' => 'Science', 'level' => 'Grade 7', 'display_order' => 3],
            ['code' => 'SS1', 'name' => 'History and Government', 'learning_area' => 'Social', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'SS2', 'name' => 'Geography', 'learning_area' => 'Social', 'level' => 'Grade 7', 'display_order' => 2],
            ['code' => 'CRE1', 'name' => 'CRE', 'learning_area' => 'Religious', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'PE1', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'level' => 'Grade 7', 'display_order' => 1],
            ['code' => 'LIFE1', 'name' => 'Life Skills', 'learning_area' => 'Life Skills', 'level' => 'Grade 7', 'display_order' => 1],
        ];

        foreach ($strands as $strand) {
            // Make code unique per level by appending level if needed
            $uniqueCode = $strand['code'] . '_' . str_replace(' ', '_', $strand['level']);
            
            CBCStrand::updateOrCreate(
                ['code' => $uniqueCode, 'level' => $strand['level']],
                array_merge($strand, [
                    'code' => $uniqueCode,
                    'description' => "CBC Strand: {$strand['name']} for {$strand['level']}",
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('CBC Strands seeded successfully.');
    }
}

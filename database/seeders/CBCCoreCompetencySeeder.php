<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CBCCoreCompetencySeeder extends Seeder
{
    public function run(): void
    {
        $competencies = [
            [
                'code' => 'CC',
                'name' => 'Communication and Collaboration',
                'description' => 'Ability to communicate effectively and work collaboratively with others.',
                'learning_area' => null,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'CTPS',
                'name' => 'Critical Thinking and Problem Solving',
                'description' => 'Ability to think critically, analyze situations, and solve problems effectively.',
                'learning_area' => null,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'CI',
                'name' => 'Creativity and Imagination',
                'description' => 'Ability to think creatively, innovate, and use imagination in learning and problem-solving.',
                'learning_area' => null,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'CIT',
                'name' => 'Citizenship',
                'description' => 'Understanding of rights, responsibilities, and active participation in community and national development.',
                'learning_area' => null,
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'DL',
                'name' => 'Digital Literacy',
                'description' => 'Ability to use digital tools and technologies effectively for learning and communication.',
                'learning_area' => null,
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'L2L',
                'name' => 'Learning to Learn',
                'description' => 'Ability to acquire knowledge independently, reflect on learning, and develop effective learning strategies.',
                'learning_area' => null,
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'code' => 'SE',
                'name' => 'Self-Efficacy',
                'description' => 'Belief in one\'s ability to succeed and confidence in taking on challenges.',
                'learning_area' => null,
                'display_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($competencies as $competency) {
            DB::table('cbc_core_competencies')->updateOrInsert(
                ['code' => $competency['code']],
                $competency
            );
        }
    }
}

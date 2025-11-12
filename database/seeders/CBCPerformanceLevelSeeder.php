<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CBCPerformanceLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'code' => 'E',
                'name' => 'Exceeding',
                'min_percentage' => 80.00,
                'max_percentage' => 100.00,
                'description' => 'Learner demonstrates competencies beyond the expected level. Shows exceptional understanding and application of knowledge and skills.',
                'color_code' => '#28a745',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'M',
                'name' => 'Meeting',
                'min_percentage' => 60.00,
                'max_percentage' => 79.99,
                'description' => 'Learner demonstrates competencies at the expected level. Shows good understanding and application of knowledge and skills.',
                'color_code' => '#17a2b8',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'A',
                'name' => 'Approaching',
                'min_percentage' => 40.00,
                'max_percentage' => 59.99,
                'description' => 'Learner demonstrates competencies approaching the expected level. Shows basic understanding but needs more practice.',
                'color_code' => '#ffc107',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'B',
                'name' => 'Below',
                'min_percentage' => 0.00,
                'max_percentage' => 39.99,
                'description' => 'Learner demonstrates competencies below the expected level. Requires additional support and intervention.',
                'color_code' => '#dc3545',
                'display_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $level) {
            DB::table('cbc_performance_levels')->updateOrInsert(
                ['code' => $level['code']],
                $level
            );
        }
    }
}

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
                'code' => 'EE',
                'name' => 'Exceeding Expectation',
                'min_percentage' => 80.00,
                'max_percentage' => 100.00,
                'description' => 'Learner demonstrates competencies beyond the expected level.',
                'color_code' => '#28a745',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ME',
                'name' => 'Meeting Expectation',
                'min_percentage' => 60.00,
                'max_percentage' => 79.99,
                'description' => 'Learner demonstrates competencies at the expected level.',
                'color_code' => '#17a2b8',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'AE',
                'name' => 'Approaching Expectation',
                'min_percentage' => 30.00,
                'max_percentage' => 59.99,
                'description' => 'Learner demonstrates competencies approaching the expected level.',
                'color_code' => '#ffc107',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'BE',
                'name' => 'Below Expectation',
                'min_percentage' => 0.00,
                'max_percentage' => 29.99,
                'description' => 'Learner demonstrates competencies below the expected level.',
                'color_code' => '#dc3545',
                'display_order' => 4,
                'is_active' => true,
            ],
        ];

        $legacyCodes = ['E' => 'EE', 'M' => 'ME', 'A' => 'AE', 'B' => 'BE'];

        foreach ($levels as $level) {
            $legacyCode = array_search($level['code'], $legacyCodes, true);
            if ($legacyCode !== false) {
                $existing = DB::table('cbc_performance_levels')->where('code', $legacyCode)->first();
                if ($existing) {
                    DB::table('cbc_performance_levels')->where('id', $existing->id)->update($level);

                    continue;
                }
            }

            DB::table('cbc_performance_levels')->updateOrInsert(
                ['code' => $level['code']],
                $level
            );
        }

        DB::table('cbc_performance_levels')
            ->whereIn('code', array_keys($legacyCodes))
            ->update(['is_active' => false]);
    }
}

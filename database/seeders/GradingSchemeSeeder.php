<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\GradingBand;

class GradingSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $cbc = GradingScheme::updateOrCreate(
            ['type' => 'cbc_pl', 'name' => 'CBC Performance Levels'],
            ['is_default' => true]
        );

        $cbcBands = [
            ['min' => 0, 'max' => 29, 'label' => 'BE', 'descriptor' => 'Below Expectation', 'rank' => 1],
            ['min' => 30, 'max' => 59, 'label' => 'AE', 'descriptor' => 'Approaching Expectation', 'rank' => 2],
            ['min' => 60, 'max' => 79, 'label' => 'ME', 'descriptor' => 'Meeting Expectation', 'rank' => 3],
            ['min' => 80, 'max' => 100, 'label' => 'EE', 'descriptor' => 'Exceeding Expectation', 'rank' => 4],
        ];

        foreach ($cbcBands as $band) {
            GradingBand::updateOrCreate(
                [
                    'grading_scheme_id' => $cbc->id,
                    'label' => $band['label'],
                ],
                $band
            );
        }

        $legacyLabels = ['PL1' => 'BE', 'PL2' => 'AE', 'PL3' => 'ME', 'PL4' => 'EE'];
        foreach ($legacyLabels as $old => $new) {
            GradingBand::query()
                ->where('grading_scheme_id', $cbc->id)
                ->where('label', $old)
                ->update(['label' => $new]);
        }

        $letters = GradingScheme::updateOrCreate(
            ['type' => 'numeric_letter', 'name' => 'Letters A–E'],
            ['is_default' => false]
        );

        $letterBands = [
            ['min' => 80, 'max' => 100, 'label' => 'A', 'descriptor' => 'Excellent', 'rank' => 1],
            ['min' => 70, 'max' => 79, 'label' => 'B', 'descriptor' => 'Very Good', 'rank' => 2],
            ['min' => 60, 'max' => 69, 'label' => 'C', 'descriptor' => 'Good', 'rank' => 3],
            ['min' => 50, 'max' => 59, 'label' => 'D', 'descriptor' => 'Fair', 'rank' => 4],
            ['min' => 0, 'max' => 49, 'label' => 'E', 'descriptor' => 'Needs Improvement', 'rank' => 5],
        ];

        foreach ($letterBands as $band) {
            GradingBand::updateOrCreate(
                [
                    'grading_scheme_id' => $letters->id,
                    'label' => $band['label'],
                ],
                $band
            );
        }
    }
}

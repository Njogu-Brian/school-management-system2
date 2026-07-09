<?php

namespace Database\Seeders;

use App\Models\StatutoryRuleset;
use Illuminate\Database\Seeder;

class StatutoryRulesetSeeder extends Seeder
{
    public function run(): void
    {
        // Default Kenya ruleset approximated from Finance Act 2023 bands
        // (bands + relief are configurable; adjust here as law changes).
        StatutoryRuleset::updateOrCreate(
            ['name' => 'Kenya PAYE/SHIF/NSSF/Housing (Finance Act 2023)'],
            [
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_default' => true,
                'params' => [
                    'personal_relief_monthly' => 2400.0,
                    'paye_bands' => [
                        ['min' => 0.0, 'max' => 24000.0, 'rate' => 0.10],
                        ['min' => 24000.0, 'max' => 24000.0 + 8333.0, 'rate' => 0.25],
                        ['min' => 24000.0 + 8333.0, 'max' => 24000.0 + 8333.0 + 467667.0, 'rate' => 0.30],
                        ['min' => 24000.0 + 8333.0 + 467667.0, 'max' => 800000.0, 'rate' => 0.325],
                        ['min' => 800000.0, 'max' => null, 'rate' => 0.35],
                    ],
                    'nssf' => [
                        'tier1_max' => 6000.0,
                        'tier2_max' => 18000.0,
                        'rate' => 0.06,
                    ],
                    'shif' => [
                        'rate' => 0.0275,
                        'min' => 300.0,
                    ],
                    'housing_levy' => [
                        'rate' => 0.015,
                        'min' => 0.0,
                    ],
                    'taxable_income' => [
                        'subtract_nssf' => true,
                        'subtract_shif' => true,
                    ],
                ],
            ],
        );
    }
}


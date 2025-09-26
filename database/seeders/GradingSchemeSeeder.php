<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\GradingBand;

class GradingSchemeSeeder extends Seeder
{
    public function run(): void
    {
        // CBC Performance Levels (PL1–PL4)
        $cbc = GradingScheme::create([
            'name' => 'CBC Performance Levels',
            'type' => 'cbc_pl',
            'is_default' => true,
        ]);

        $cbcBands = [
            ['min'=>0,'max'=>29,'label'=>'PL1','descriptor'=>'Below Expectation','rank'=>1],
            ['min'=>30,'max'=>49,'label'=>'PL2','descriptor'=>'Approaching Expectation','rank'=>2],
            ['min'=>50,'max'=>69,'label'=>'PL3','descriptor'=>'Meets Expectation','rank'=>3],
            ['min'=>70,'max'=>100,'label'=>'PL4','descriptor'=>'Exceeds Expectation','rank'=>4],
        ];

        foreach($cbcBands as $band){
            GradingBand::create(array_merge($band, ['grading_scheme_id' => $cbc->id]));
        }

        // Traditional A–E scheme (optional)
        $letters = GradingScheme::create([
            'name' => 'Letters A–E',
            'type' => 'numeric_letter',
            'is_default' => false,
        ]);

        $letterBands = [
            ['min'=>80,'max'=>100,'label'=>'A','descriptor'=>'Excellent','rank'=>1],
            ['min'=>70,'max'=>79,'label'=>'B','descriptor'=>'Very Good','rank'=>2],
            ['min'=>60,'max'=>69,'label'=>'C','descriptor'=>'Good','rank'=>3],
            ['min'=>50,'max'=>59,'label'=>'D','descriptor'=>'Fair','rank'=>4],
            ['min'=>0,'max'=>49,'label'=>'E','descriptor'=>'Needs Improvement','rank'=>5],
        ];

        foreach($letterBands as $band){
            GradingBand::create(array_merge($band, ['grading_scheme_id' => $letters->id]));
        }
    }
}

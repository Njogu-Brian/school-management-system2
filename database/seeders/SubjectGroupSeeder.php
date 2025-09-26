<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\SubjectGroup;

class SubjectGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Languages','code'=>'LANG','display_order'=>1],
            ['name' => 'Sciences','code'=>'SCI','display_order'=>2],
            ['name' => 'Mathematics','code'=>'MATH','display_order'=>3],
            ['name' => 'Social Studies & CRE','code'=>'SOC','display_order'=>4],
            ['name' => 'Arts & Sports','code'=>'ART','display_order'=>5],
        ];

        foreach ($groups as $group) {
            SubjectGroup::create($group);
        }
    }
}

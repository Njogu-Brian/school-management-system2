<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Subject;
use App\Models\Academics\SubjectGroup;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code'=>'ENG','name'=>'English','group'=>'Languages','learning_area'=>'Language'],
            ['code'=>'KIS','name'=>'Kiswahili','group'=>'Languages','learning_area'=>'Language'],
            ['code'=>'MAT','name'=>'Mathematics','group'=>'Mathematics','learning_area'=>'Mathematical'],
            ['code'=>'SCI','name'=>'Science & Technology','group'=>'Sciences','learning_area'=>'Science'],
            ['code'=>'CRE','name'=>'CRE','group'=>'Social Studies & CRE','learning_area'=>'Religion'],
            ['code'=>'SST','name'=>'Social Studies','group'=>'Social Studies & CRE','learning_area'=>'Social'],
            ['code'=>'ART','name'=>'Creative Arts','group'=>'Arts & Sports','learning_area'=>'Arts'],
            ['code'=>'PE','name'=>'Physical Education','group'=>'Arts & Sports','learning_area'=>'Physical'],
        ];

        foreach ($subjects as $sub) {
            $group = SubjectGroup::where('name',$sub['group'])->first();
            if ($group) {
                Subject::updateOrCreate(
                    ['code' => $sub['code']],
                    [
                    'subject_group_id' => $group->id,
                    'name' => $sub['name'],
                    'learning_area' => $sub['learning_area'],
                    'level' => null,
                    'is_active' => true,
                    ]
                );
            }
        }
    }
}

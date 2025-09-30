<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\ExamGrade;

class ExamGradeSeeder extends Seeder
{
    public function run(): void
    {
        // Map: EE > ME > AE > BE
        $rows = [
            ['exam_type'=>'TERM',    'grade_name'=>'EE','percent_from'=>80,'percent_upto'=>100,'grade_point'=>4.0,'description'=>'Exceeding Expectation'],
            ['exam_type'=>'TERM',    'grade_name'=>'ME','percent_from'=>60,'percent_upto'=>79.99,'grade_point'=>3.0,'description'=>'Meeting Expectation'],
            ['exam_type'=>'TERM',    'grade_name'=>'AE','percent_from'=>30,'percent_upto'=>59.99,'grade_point'=>2.0,'description'=>'Above Expectation'],
            ['exam_type'=>'TERM',    'grade_name'=>'BE','percent_from'=>0, 'percent_upto'=>29.99,'grade_point'=>1.0,'description'=>'Below Expectation'],

            ['exam_type'=>'MIDTERM', 'grade_name'=>'EE','percent_from'=>80,'percent_upto'=>100,'grade_point'=>4.0,'description'=>'Exceeding Expectation'],
            ['exam_type'=>'MIDTERM', 'grade_name'=>'ME','percent_from'=>60,'percent_upto'=>79.99,'grade_point'=>3.0,'description'=>'Meeting Expectation'],
            ['exam_type'=>'MIDTERM', 'grade_name'=>'AE','percent_from'=>30,'percent_upto'=>59.99,'grade_point'=>2.0,'description'=>'Above Expectation'],
            ['exam_type'=>'MIDTERM', 'grade_name'=>'BE','percent_from'=>0, 'percent_upto'=>29.99,'grade_point'=>1.0,'description'=>'Below Expectation'],

            ['exam_type'=>'OPENER',  'grade_name'=>'EE','percent_from'=>80,'percent_upto'=>100,'grade_point'=>4.0,'description'=>'Exceeding Expectation'],
            ['exam_type'=>'OPENER',  'grade_name'=>'ME','percent_from'=>60,'percent_upto'=>79.99,'grade_point'=>3.0,'description'=>'Meeting Expectation'],
            ['exam_type'=>'OPENER',  'grade_name'=>'AE','percent_from'=>30,'percent_upto'=>59.99,'grade_point'=>2.0,'description'=>'Above Expectation'],
            ['exam_type'=>'OPENER',  'grade_name'=>'BE','percent_from'=>0, 'percent_upto'=>29.99,'grade_point'=>1.0,'description'=>'Below Expectation'],
        ];

        foreach ($rows as $r) {
            ExamGrade::updateOrCreate(
                ['exam_type'=>$r['exam_type'], 'grade_name'=>$r['grade_name']],
                $r
            );
        }
    }
}

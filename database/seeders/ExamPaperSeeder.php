<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\ExamPaper;

class ExamPaperSeeder extends Seeder
{
    public function run()
    {
        // Pick first available records
        $exam = Exam::first();
        $subject = Subject::first();
        $classroom = Classroom::first();

        // Only seed if related records exist
        if ($exam && $subject && $classroom) {
            ExamPaper::create([
                'exam_id' => $exam->id,
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'exam_date' => now()->addDays(3)->format('Y-m-d'),
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'max_marks' => 100,
            ]);

            ExamPaper::create([
                'exam_id' => $exam->id,
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'exam_date' => now()->addDays(4)->format('Y-m-d'),
                'start_time' => '12:00:00',
                'end_time' => '14:00:00',
                'max_marks' => 100,
            ]);
        }
    }
}

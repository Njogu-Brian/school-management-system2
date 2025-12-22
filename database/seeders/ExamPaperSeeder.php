<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use Illuminate\Support\Facades\DB;

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
            // Table was renamed from exam_papers to exam_schedules
            DB::table('exam_schedules')->insertOrIgnore([
                [
                    'exam_id' => $exam->id,
                    'subject_id' => $subject->id,
                    'classroom_id' => $classroom->id,
                    'exam_date' => now()->addDays(3)->format('Y-m-d'),
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'max_marks' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'exam_id' => $exam->id,
                    'subject_id' => $subject->id,
                    'classroom_id' => $classroom->id,
                    'exam_date' => now()->addDays(4)->format('Y-m-d'),
                    'start_time' => '12:00:00',
                    'end_time' => '14:00:00',
                    'max_marks' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;

class DemoExamSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure required dependencies exist
        $academicYear = AcademicYear::first();
        $term = Term::first();
        $classroom = Classroom::first();
        $teacher = Staff::first();
        $subject = Subject::where('code', 'MAT')->first(); // Mathematics

        if (!$academicYear || !$term || !$classroom || !$teacher || !$subject) {
            $this->command->warn('DemoExamSeeder skipped: missing AcademicYear/Term/Classroom/Staff/Subject');
            return;
        }

        // Create demo exam
        $exam = Exam::create([
            'name' => 'CAT 1 - Mathematics',
            'type' => 'cat',
            'modality' => 'physical',
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'starts_on' => Carbon::today()->subDays(7),
            'ends_on' => Carbon::today(),
            'max_marks' => 100,
            'weight' => 30,
            'status' => 'marking',
        ]);

        // Assign marks for first 10 students
        $students = Student::take(10)->get();
        $scores = [78, 65, 89, 55, 90, 42, 73, 81, 67, 58];

        foreach ($students as $index => $student) {
            ExamMark::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'score_raw' => $scores[$index] ?? rand(40, 95),
                'remark' => 'Good effort',
                'status' => 'submitted',
            ]);
        }

        $this->command->info('Demo Exam & Marks seeded successfully.');
    }
}

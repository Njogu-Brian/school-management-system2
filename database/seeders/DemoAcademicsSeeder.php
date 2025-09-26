<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ReportCard;
use App\Models\Academics\Homework;
use App\Models\Academics\Diary;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;

class DemoAcademicsSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::first();
        $term = Term::first();
        $classroom = Classroom::first();
        $teacher = Staff::first();
        $subject = Subject::where('code', 'MAT')->first(); // Mathematics

        if (!$academicYear || !$term || !$classroom || !$teacher || !$subject) {
            $this->command->warn('DemoAcademicsSeeder skipped: missing AcademicYear/Term/Classroom/Staff/Subject');
            return;
        }

        // --- DEMO EXAM + MARKS ---
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

        $this->command->info('Demo exam & marks created.');

        // --- DEMO REPORT CARDS ---
        foreach ($students as $student) {
            ReportCard::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $term->id,
                'classroom_id' => $classroom->id,
                'stream_id' => null,
                'pdf_path' => 'storage/reports/demo_' . $student->id . '.pdf',
                'published_at' => now(),
                'published_by' => $teacher->id,
                'locked_at' => now(),
                'summary' => [
                    'total_marks' => rand(300, 500),
                    'average' => rand(50, 80),
                    'position' => rand(1, 10),
                    'remarks' => 'Keep working hard!',
                ],
            ]);
        }

        $this->command->info('Demo report cards created.');

        // --- DEMO HOMEWORK ---
        Homework::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Mathematics Homework 1',
            'instructions' => 'Complete exercises 1–10 on fractions in your workbook.',
            'due_date' => Carbon::today()->addDays(3),
            'file_path' => null,
        ]);

        $this->command->info('Demo homework created.');

        // --- DEMO DIARY ---
        Diary::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'teacher_id' => $teacher->id,
            'week_start' => Carbon::now()->startOfWeek(),
            'entries' => [
                'Monday' => ['topic'=>'Fractions', 'activities'=>'Group exercises', 'notes'=>'Revise previous work'],
                'Tuesday' => ['topic'=>'Decimals', 'activities'=>'Class discussion', 'notes'=>'Homework due'],
                'Wednesday' => ['topic'=>'Measurement', 'activities'=>'Practical examples', 'notes'=>'Bring ruler'],
                'Thursday' => ['topic'=>'Geometry', 'activities'=>'Drawing shapes', 'notes'=>'Prepare graph book'],
                'Friday' => ['topic'=>'Revision', 'activities'=>'Quiz', 'notes'=>'Prepare for CAT'],
            ],
        ]);

        $this->command->info('Demo diary created.');
    }
}

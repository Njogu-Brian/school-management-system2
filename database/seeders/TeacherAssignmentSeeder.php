<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use App\Models\AcademicYear;
use App\Models\Term;

class TeacherAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $staff = Staff::all();
        $classrooms = Classroom::all();
        $subjects = Subject::all();
        $academicYear = AcademicYear::orderByDesc('year')->first();
        $term = Term::orderBy('name')->first();

        if ($staff->isEmpty() || $classrooms->isEmpty() || $subjects->isEmpty() || !$academicYear || !$term) {
            $this->command->warn('TeacherAssignmentSeeder skipped: missing required data');
            return;
        }

        $assignments = 0;

        // Assign teachers to classrooms and subjects
        foreach ($classrooms as $classroom) {
            // Get 3-5 subjects per classroom
            $classroomSubjects = $subjects->random(min(5, $subjects->count()));
            
            foreach ($classroomSubjects as $subject) {
                // Assign a random teacher
                $teacher = $staff->random();
                
                ClassroomSubject::updateOrCreate(
                    [
                        'classroom_id' => $classroom->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $academicYear->id,
                        'term_id' => $term->id,
                    ],
                    [
                        'staff_id' => $teacher->id,
                        'is_compulsory' => !$subject->is_optional,
                    ]
                );
                $assignments++;
            }
        }

        $this->command->info("Created {$assignments} teacher assignments.");
    }
}

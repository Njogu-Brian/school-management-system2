<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Database\Factories\AttendanceFactory;

class AttendanceTableSeeder extends Seeder
{
    public function run()
    {
        $students = Student::all();

        // Create attendance records for each student
        foreach ($students as $student) {
            AttendanceFactory::new()
                ->count(30) // Create 30 attendance records per student
                ->make()
                ->each(function ($attendance) use ($student) {
                    $attendance->student_id = $student->id;
                    $attendance->save();
                });
        }
    }
}
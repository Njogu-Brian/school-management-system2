<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Academics\Timetable;
use App\Models\Staff;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiLessonPlanSupervisionTest extends TestCase
{
    public function test_teacher_can_create_and_submit_lesson_plan_from_timetable(): void
    {
        if (config('database.default') === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: current migration set has known FK/table conflicts in tests.');
        }

        $teacher = $this->createTeacher();
        $teacherStaffId = (int) $teacher->staff->id;

        $year = AcademicYear::factory()->create();
        $term = Term::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);

        DB::table('classroom_subjects')->insert([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'staff_id' => $teacherStaffId,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'is_compulsory' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tt = Timetable::create([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'day' => now()->dayName,
            'period' => 1,
            'start_time' => '08:00',
            'end_time' => '08:40',
            'subject_id' => $subject->id,
            'staff_id' => $teacherStaffId,
            'room' => 'A1',
            'is_break' => false,
            'meta' => null,
        ]);

        Sanctum::actingAs($teacher);

        $res = $this->postJson('/api/lesson-plans', [
            'timetable_id' => $tt->id,
            'planned_date' => now()->toDateString(),
            'title' => 'Equivalent fractions',
            'learning_objectives' => ['Identify equivalent fractions'],
            'activities' => ['Board examples', 'Group work'],
            'learning_resources' => ['Textbook'],
        ]);

        $res->assertStatus(201);
        $res->assertJsonPath('success', true);
        $id = (int) ($res->json('data.id'));

        $submit = $this->postJson("/api/lesson-plans/{$id}/submit");
        $submit->assertStatus(200);
        $submit->assertJsonPath('data.submission_status', 'submitted');
    }

    public function test_academic_admin_can_approve_submitted_lesson_plan(): void
    {
        if (config('database.default') === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: current migration set has known FK/table conflicts in tests.');
        }

        Role::firstOrCreate(['name' => 'Academic Administrator']);

        $teacher = $this->createTeacher();
        $teacherStaffId = (int) $teacher->staff->id;

        $year = AcademicYear::factory()->create();
        $term = Term::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG', 'is_active' => true]);

        DB::table('classroom_subjects')->insert([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'staff_id' => $teacherStaffId,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'is_compulsory' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tt = Timetable::create([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'day' => now()->dayName,
            'period' => 2,
            'start_time' => '09:00',
            'end_time' => '09:40',
            'subject_id' => $subject->id,
            'staff_id' => $teacherStaffId,
            'room' => 'B2',
            'is_break' => false,
            'meta' => null,
        ]);

        Sanctum::actingAs($teacher);
        $create = $this->postJson('/api/lesson-plans', [
            'timetable_id' => $tt->id,
            'planned_date' => now()->toDateString(),
            'title' => 'Reading comprehension',
        ])->assertStatus(201);

        $lpId = (int) $create->json('data.id');
        $this->postJson("/api/lesson-plans/{$lpId}/submit")->assertStatus(200);

        $admin = $this->createUser([], 'Academic Administrator');
        Staff::factory()->create(['user_id' => $admin->id]);
        Sanctum::actingAs($admin);

        $approve = $this->postJson("/api/lesson-plans/{$lpId}/approve", [
            'approval_notes' => 'OK',
        ]);
        $approve->assertStatus(200);
        $approve->assertJsonPath('data.submission_status', 'approved');
    }
}


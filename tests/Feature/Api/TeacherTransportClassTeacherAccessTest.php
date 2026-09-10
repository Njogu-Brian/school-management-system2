<?php

namespace Tests\Feature\Api;

use App\Models\ClassTeacherAssignment;
use App\Models\Staff;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherTransportClassTeacherAccessTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        Notification::fake();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher']);
    }

    public function test_class_teacher_can_list_and_mark_homeroom_transport_roster(): void
    {
        $classroom = $this->createClassroom();
        $teacher = $this->createTeacher();
        $staff = Staff::where('user_id', $teacher->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $staff->id,
        ]);

        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        Sanctum::actingAs($teacher);

        $list = $this->getJson('/api/teacher/transport/students');
        $list->assertOk()->assertJsonPath('success', true);
        $ids = collect($list->json('data.students') ?? [])->pluck('id')->all();
        $this->assertContains($student->id, $ids);

        $mark = $this->postJson('/api/teacher/transport/pickups', [
            'student_id' => $student->id,
            'direction' => 'evening',
            'picked_up_by' => 'Parent',
            'notes' => 'Collected early',
        ]);
        $mark->assertOk()->assertJsonPath('success', true);
    }

    public function test_unrelated_teacher_cannot_mark_transport_for_other_class(): void
    {
        $classroom = $this->createClassroom();
        $otherClassroom = $this->createClassroom();

        $classTeacher = $this->createTeacher();
        $staff = Staff::where('user_id', $classTeacher->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $staff->id,
        ]);

        $outsider = $this->createTeacher();
        $outsiderStaff = Staff::where('user_id', $outsider->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $otherClassroom->id,
            'stream_id' => null,
            'staff_id' => $outsiderStaff->id,
        ]);

        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        Sanctum::actingAs($outsider);
        $mark = $this->postJson('/api/teacher/transport/pickups', [
            'student_id' => $student->id,
            'direction' => 'evening',
        ]);
        $mark->assertStatus(403);
    }
}

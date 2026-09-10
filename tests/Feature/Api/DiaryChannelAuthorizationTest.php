<?php

namespace Tests\Feature\Api;

use App\Models\Academics\StudentDiary;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 7G — diary channel authorization.
 */
class DiaryChannelAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Parent']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);
    }

    public function test_teacher_cannot_open_admin_parent_channel(): void
    {
        $classroom = $this->createClassroom();
        $student = $this->createStudent(['classroom_id' => $classroom->id, 'archive' => 0]);
        $teacher = $this->createTeacher();
        $teacher->classrooms()->syncWithoutDetaching([$classroom->id]);

        StudentDiary::query()->firstOrCreate(
            ['student_id' => $student->id, 'channel' => StudentDiary::CHANNEL_ADMIN_PARENT],
            ['channel' => StudentDiary::CHANNEL_ADMIN_PARENT]
        );

        Sanctum::actingAs($teacher);
        $this->getJson("/api/diaries/students/{$student->id}?channel=admin_parent")
            ->assertForbidden();
    }

    public function test_parent_a_cannot_open_parent_b_child_diary(): void
    {
        $parentA = ParentInfo::factory()->create();
        $parentB = ParentInfo::factory()->create();
        $childB = $this->createStudent(['parent_id' => $parentB->id, 'archive' => 0]);
        $userA = User::factory()->create(['parent_id' => $parentA->id]);
        $userA->assignRole('Parent');

        Sanctum::actingAs($userA);
        $this->getJson("/api/diaries/students/{$childB->id}?channel=teacher_parent")
            ->assertForbidden();
    }

    public function test_admin_can_open_teacher_parent_channel_for_oversight(): void
    {
        $classroom = $this->createClassroom();
        $student = $this->createStudent(['classroom_id' => $classroom->id, 'archive' => 0]);
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);
        $this->getJson("/api/diaries/students/{$student->id}?channel=teacher_parent")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_teacher_cannot_list_admin_parent_channel(): void
    {
        $classroom = $this->createClassroom();
        $this->createStudent(['classroom_id' => $classroom->id, 'archive' => 0]);
        $teacher = $this->createTeacher();
        $teacher->classrooms()->syncWithoutDetaching([$classroom->id]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/diaries?channel=admin_parent')
            ->assertForbidden();
    }

    public function test_dual_role_teacher_parent_can_open_admin_parent_in_home_mode(): void
    {
        $parent = ParentInfo::factory()->create();
        $student = $this->createStudent(['parent_id' => $parent->id, 'archive' => 0]);
        $teacher = $this->createTeacher();
        $teacher->parent_id = $parent->id;
        $teacher->save();
        $teacher->assignRole('Parent');

        StudentDiary::query()->firstOrCreate(
            ['student_id' => $student->id, 'channel' => StudentDiary::CHANNEL_ADMIN_PARENT],
            ['channel' => StudentDiary::CHANNEL_ADMIN_PARENT]
        );

        Sanctum::actingAs($teacher);
        $this->withHeader('X-App-Mode', 'home')
            ->getJson("/api/diaries/students/{$student->id}?channel=admin_parent")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}

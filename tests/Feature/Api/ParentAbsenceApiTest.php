<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\ClassTeacherAssignment;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentAbsenceService;
use App\Services\StudentAttendanceCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ParentAbsenceApiTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        Notification::fake();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Senior Teacher']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretary']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Director']);
    }

    protected function makeLinkedParentAndChild(array $studentAttrs = []): array
    {
        $parentInfo = ParentInfo::factory()->create();
        $classroom = $this->createClassroom();
        $student = $this->createStudent(array_merge([
            'parent_id' => $parentInfo->id,
            'classroom_id' => $classroom->id,
            'admission_date' => now()->subYear()->toDateString(),
            'archive' => 0,
        ], $studentAttrs));

        $parentUser = User::factory()->create(['parent_id' => $parentInfo->id]);
        $parentUser->assignRole('Parent');

        return compact('parentInfo', 'classroom', 'student', 'parentUser');
    }

    protected function stubCalendarAllows(): void
    {
        $calendar = Mockery::mock(StudentAttendanceCalendarService::class)->makePartial();
        $calendar->shouldReceive('isValidSchoolDay')->andReturn(true);
        $calendar->shouldReceive('effectiveEnrolmentDate')->andReturnUsing(
            fn (Student $s) => Carbon::parse($s->admission_date ?? now()->subYear())->startOfDay()
        );
        $calendar->shouldReceive('wasArchivedOnDate')->andReturn(false);
        $this->app->instance(StudentAttendanceCalendarService::class, $calendar);
    }

    public function test_parent_may_report_own_child_one_day(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent, 'classroom' => $classroom] = $this->makeLinkedParentAndChild();

        $teacherUser = $this->createTeacher();
        $staff = Staff::where('user_id', $teacherUser->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $staff->id,
        ]);

        Sanctum::actingAs($parent);
        $day = now()->toDateString();

        $res = $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'Family emergency',
        ]);

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'status' => 'absent',
            'is_excused' => 1,
            'marked_by' => $parent->id,
        ]);
    }

    public function test_parent_may_report_multi_day_absence(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent] = $this->makeLinkedParentAndChild();
        Sanctum::actingAs($parent);

        $start = now()->toDateString();
        $end = now()->addDays(2)->toDateString();

        $res = $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Medical recovery at home',
        ]);

        $res->assertOk();
        $this->assertGreaterThanOrEqual(1, (int) $res->json('data.school_days'));
        $this->assertEquals(
            (int) $res->json('data.school_days'),
            Attendance::where('student_id', $student->id)->where('status', 'absent')->count()
        );
    }

    public function test_parent_may_not_report_unrelated_child(): void
    {
        $this->stubCalendarAllows();
        ['student' => $otherChild] = $this->makeLinkedParentAndChild();
        ['parentUser' => $stranger] = $this->makeLinkedParentAndChild();

        Sanctum::actingAs($stranger);
        $day = now()->toDateString();

        $this->postJson("/api/students/{$otherChild->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'Trying to spoof',
        ])->assertForbidden();
    }

    public function test_invalid_date_range_rejected(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent] = $this->makeLinkedParentAndChild();
        Sanctum::actingAs($parent);

        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Backwards range',
        ])->assertStatus(422);
    }

    public function test_missing_reason_rejected(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent] = $this->makeLinkedParentAndChild();
        Sanctum::actingAs($parent);
        $day = now()->toDateString();

        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'ab',
        ])->assertStatus(422);
    }

    public function test_unauthenticated_access_denied(): void
    {
        ['student' => $student] = $this->makeLinkedParentAndChild();
        $day = now()->toDateString();

        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'No auth',
        ])->assertUnauthorized();
    }

    public function test_notification_recipients_are_relationship_scoped(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent, 'classroom' => $classroom] = $this->makeLinkedParentAndChild();

        $classTeacher = $this->createTeacher();
        $classStaff = Staff::where('user_id', $classTeacher->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $classStaff->id,
        ]);

        $unrelatedSenior = $this->createUser([], 'Senior Teacher');
        $officeAdmin = $this->createAdmin();

        /** @var ParentAbsenceService $service */
        $service = app(ParentAbsenceService::class);
        $recipients = $service->resolveStaffRecipients($student);
        $ids = $recipients->pluck('id')->all();

        $this->assertContains($classTeacher->id, $ids);
        $this->assertContains($officeAdmin->id, $ids);
        $this->assertNotContains($unrelatedSenior->id, $ids, 'Unrelated Senior Teacher must not receive parent absence alerts');
    }

    public function test_duplicate_same_day_updates_excused_absence(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent] = $this->makeLinkedParentAndChild();
        Sanctum::actingAs($parent);
        $day = now()->toDateString();

        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'First report of illness',
        ])->assertOk();

        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'Updated reason for illness',
        ])->assertOk();

        $this->assertEquals(1, Attendance::where('student_id', $student->id)->whereDate('date', $day)->count());
        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'excuse_notes' => 'Updated reason for illness',
        ]);
    }

    public function test_cannot_overwrite_present_mark(): void
    {
        $this->stubCalendarAllows();
        ['student' => $student, 'parentUser' => $parent] = $this->makeLinkedParentAndChild();
        $day = now()->toDateString();
        Attendance::create([
            'student_id' => $student->id,
            'date' => $day,
            'status' => 'present',
            'marked_by' => $this->createTeacher()->id,
            'marked_at' => now(),
        ]);

        Sanctum::actingAs($parent);
        $this->postJson("/api/students/{$student->id}/attendance-absence", [
            'start_date' => $day,
            'end_date' => $day,
            'reason' => 'Should fail',
        ])->assertStatus(422);
    }
}

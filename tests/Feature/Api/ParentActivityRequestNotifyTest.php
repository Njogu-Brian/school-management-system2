<?php

namespace Tests\Feature\Api;

use App\Models\ClassTeacherAssignment;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Models\Votehead;
use App\Services\ParentAbsenceService;
use App\Services\ParentCoCurricularService;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 7K — activity-change staff notifications must be relationship-scoped.
 */
class ParentActivityRequestNotifyTest extends TestCase
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
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Parent']);
    }

    public function test_activity_change_recipients_exclude_unrelated_senior_teacher(): void
    {
        $parentInfo = ParentInfo::factory()->create();
        $classroom = $this->createClassroom();
        $student = $this->createStudent([
            'parent_id' => $parentInfo->id,
            'classroom_id' => $classroom->id,
            'archive' => 0,
        ]);

        $classTeacher = $this->createTeacher();
        $staff = Staff::where('user_id', $classTeacher->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $staff->id,
        ]);

        $unrelatedSenior = $this->createUser([], 'Senior Teacher');
        $officeAdmin = $this->createAdmin();

        $recipients = app(ParentAbsenceService::class)->resolveStaffRecipients($student);
        $ids = $recipients->pluck('id')->all();

        $this->assertContains($classTeacher->id, $ids);
        $this->assertContains($officeAdmin->id, $ids);
        $this->assertNotContains(
            $unrelatedSenior->id,
            $ids,
            'Unrelated Senior Teacher must not receive parent activity-change alerts'
        );
    }

    public function test_parent_b_does_not_receive_parent_a_activity_staff_alert(): void
    {
        $parentA = ParentInfo::factory()->create();
        $parentB = ParentInfo::factory()->create();
        $classroom = $this->createClassroom();
        $studentA = $this->createStudent([
            'parent_id' => $parentA->id,
            'classroom_id' => $classroom->id,
            'archive' => 0,
        ]);
        $userB = User::factory()->create(['parent_id' => $parentB->id]);
        $userB->assignRole('Parent');

        $recipients = app(ParentAbsenceService::class)->resolveStaffRecipients($studentA);
        $this->assertFalse($recipients->contains('id', $userB->id));
    }
}

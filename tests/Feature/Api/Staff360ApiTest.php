<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveBalance;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Staff360ApiTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_admin_can_fetch_leave_balances_for_staff(): void
    {
        $admin = $this->createAdmin();
        $year = $this->createAcademicYear(['is_active' => true, 'name' => '2026']);

        $staffUser = User::factory()->create(['email' => 'staff360@example.com']);
        $staff = Staff::create([
            'user_id' => $staffUser->id,
            'staff_id' => 'STAFF3601',
            'first_name' => 'Sam',
            'last_name' => 'Staffer',
            'work_email' => 'staff360@example.com',
            'phone_number' => '+254700000001',
            'id_number' => '36000001',
            'status' => 'active',
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'code' => 'ANNUAL360',
            'max_days' => 21,
            'is_paid' => true,
            'requires_approval' => true,
            'is_active' => true,
        ]);

        StaffLeaveBalance::create([
            'staff_id' => $staff->id,
            'leave_type_id' => $leaveType->id,
            'academic_year_id' => $year->id,
            'entitlement_days' => 21,
            'used_days' => 5,
            'remaining_days' => 16,
            'carried_forward' => 0,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/staff/{$staff->id}/leave-balances");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.staff_id', $staff->id)
            ->assertJsonPath('data.balances.0.leave_type_name', 'Annual Leave')
            ->assertJsonPath('data.balances.0.remaining_days', 16);
    }

    public function test_admin_can_fetch_attendance_history_with_summary(): void
    {
        $admin = $this->createAdmin();

        $staffUser = User::factory()->create(['email' => 'attend360@example.com']);
        $staff = Staff::create([
            'user_id' => $staffUser->id,
            'staff_id' => 'STAFF3602',
            'first_name' => 'Alex',
            'last_name' => 'Attendee',
            'work_email' => 'attend360@example.com',
            'phone_number' => '+254700000002',
            'id_number' => '36000002',
            'status' => 'active',
        ]);

        StaffAttendance::create([
            'staff_id' => $staff->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'check_in_time' => now()->setTime(8, 0),
            'check_out_time' => now()->setTime(16, 0),
        ]);

        Sanctum::actingAs($admin);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->getJson(
            "/api/staff/{$staff->id}/attendance-history?start_date={$start}&end_date={$end}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonStructure([
                'data' => [
                    'staff',
                    'start_date',
                    'end_date',
                    'summary' => ['total', 'present', 'absent', 'late', 'half_day'],
                    'history' => ['data', 'current_page', 'total'],
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_view_other_staff_leave_balances(): void
    {
        $teacher = $this->createTeacher();
        $otherStaff = Staff::create([
            'staff_id' => 'STAFF3603',
            'first_name' => 'Other',
            'last_name' => 'Person',
            'work_email' => 'other360@example.com',
            'phone_number' => '+254700000003',
            'id_number' => '36000003',
            'status' => 'active',
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson("/api/staff/{$otherStaff->id}/leave-balances")
            ->assertForbidden();
    }
}

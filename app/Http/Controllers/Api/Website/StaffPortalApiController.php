<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiAnnouncementController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiLessonPlansController;
use App\Http\Controllers\Api\ApiStaffClockController;
use App\Http\Controllers\Api\ApiTimetableController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff portal connector for Next.js — wraps existing ERP APIs.
 */
class StaffPortalApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return app(ApiDashboardController::class)->stats($request);
    }

    public function timetable(Request $request): JsonResponse
    {
        $user = $this->assertStaff($request);
        $staffId = $user->staff?->id;
        abort_unless($staffId, 422, 'No staff profile linked to this account.');

        return app(ApiTimetableController::class)->teacher($request, (int) $staffId);
    }

    public function lessonPlans(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return app(ApiLessonPlansController::class)->index($request);
    }

    public function clockToday(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return app(ApiStaffClockController::class)->today($request);
    }

    public function announcements(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return app(ApiAnnouncementController::class)->index($request);
    }

    public function payrollSlips(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Payroll slips available in ERP staff portal.',
        ]);
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $this->assertStaff($request);

        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Submit leave requests via ERP HR module.',
        ]);
    }

    protected function assertStaff(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole([
            'Super Admin', 'Director', 'Admin', 'Secretary', 'Teacher', 'teacher',
            'Senior Teacher', 'Deputy Senior Teacher', 'Supervisor', 'Academic Administrator',
        ]), 403, 'Staff access required.');

        return $user;
    }
}

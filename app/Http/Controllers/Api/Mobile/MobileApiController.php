<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiAnnouncementController;
use App\Http\Controllers\Api\ApiAttendanceController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiDeviceTokenController;
use App\Http\Controllers\Api\ApiHomeworkController;
use App\Http\Controllers\Api\ApiNotificationPreferencesController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiStudentStatementController;
use App\Services\Website\ParentHomeworkService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unified mobile API v1 — aggregates existing Sanctum endpoints.
 */
class MobileApiController extends Controller
{
    public function parentDashboard(Request $request): JsonResponse
    {
        return app(ApiDashboardController::class)->stats($request);
    }

    public function parentChildren(Request $request): JsonResponse
    {
        return app(ApiStudentController::class)->index($request);
    }

    public function parentChild(Request $request, int $student): JsonResponse
    {
        return app(ApiStudentController::class)->show($request, $student);
    }

    public function parentStatement(Request $request, int $student): JsonResponse
    {
        return app(ApiStudentStatementController::class)->show($request, $student);
    }

    public function teacherDashboard(Request $request): JsonResponse
    {
        return app(ApiDashboardController::class)->stats($request);
    }

    public function studentProfile(Request $request, int $student): JsonResponse
    {
        return app(ApiStudentController::class)->show($request, $student);
    }

    public function notifications(Request $request): JsonResponse
    {
        return app(ApiAnnouncementController::class)->index($request);
    }

    public function financePayments(Request $request): JsonResponse
    {
        return app(ApiPaymentController::class)->index($request);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        return app(ApiDeviceTokenController::class)->store($request);
    }

    public function revokeDevice(Request $request): JsonResponse
    {
        return app(ApiDeviceTokenController::class)->destroy($request);
    }

    public function notificationPreferences(Request $request): JsonResponse
    {
        return app(ApiNotificationPreferencesController::class)->show($request);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        return app(ApiNotificationPreferencesController::class)->update($request);
    }

    public function parentHomework(Request $request, int $student, ParentHomeworkService $homework): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $homework->forStudent($student),
        ]);
    }

    public function teacherHomework(Request $request): JsonResponse
    {
        return app(ApiHomeworkController::class)->index($request);
    }

    public function classAttendance(Request $request): JsonResponse
    {
        return app(ApiAttendanceController::class)->classAttendance($request);
    }

    public function markAttendance(Request $request): JsonResponse
    {
        return app(ApiAttendanceController::class)->mark($request);
    }

    public function studentAttendanceCalendar(Request $request, int $student): JsonResponse
    {
        return app(ApiStudentController::class)->attendanceCalendar($request, $student);
    }
}

<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiAnnouncementController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiDeviceTokenController;
use App\Http\Controllers\Api\ApiNotificationPreferencesController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiStudentStatementController;
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
}

<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiStudentStatementController;
use App\Http\Controllers\Api\ApiReportCardController;
use App\Http\Controllers\Api\ApiAnnouncementController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aggregated parent portal endpoints for the Next.js website.
 * Wraps existing Sanctum-scoped ERP APIs — no duplicate business logic.
 */
class ParentPortalApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiDashboardController::class)->stats($request);
    }

    public function children(Request $request): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiStudentController::class)->index($request);
    }

    public function child(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);
        abort_unless($request->user()->canAccessStudent($student), 403);

        return app(ApiStudentController::class)->show($request, $student);
    }

    public function statement(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiStudentStatementController::class)->show($request, $student);
    }

    public function attendance(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiStudentController::class)->attendanceCalendar($request, $student);
    }

    public function reportCards(Request $request): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiReportCardController::class)->index($request);
    }

    public function announcements(Request $request): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiAnnouncementController::class)->index($request);
    }

    protected function assertParent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['Parent', 'Guardian', 'parent']), 403, 'Parent access required.');

        return $user;
    }
}

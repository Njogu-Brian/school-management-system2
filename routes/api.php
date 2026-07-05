<?php

use App\Http\Controllers\Api\ApiSeniorTeacherController;
use App\Http\Controllers\Api\ApiAccountController;
use App\Http\Controllers\Api\ApiNotificationPreferencesController;
use App\Http\Controllers\Api\ApiStaffClockController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ApiExamReportsController;
use App\Http\Controllers\Api\ApiFeeClearanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group assigned
| the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/login/google', [AuthApiController::class, 'loginWithGoogle']);
Route::post('/login/otp/request', [AuthApiController::class, 'requestLoginOtp']);
Route::post('/login/otp/verify', [AuthApiController::class, 'verifyLoginOtp']);
Route::post('/password/email', [AuthApiController::class, 'requestPasswordResetEmailLink']);
Route::post('/password/sms-link', [AuthApiController::class, 'requestPasswordResetSmsLink']);
Route::post('/password/otp', [AuthApiController::class, 'requestPasswordResetOtp']);
Route::post('/password/verify-otp', [AuthApiController::class, 'verifyPasswordResetOtp']);
Route::post('/password/reset', [AuthApiController::class, 'resetPassword']);

/** School name + logo from portal Settings (General); no auth — for mobile sign-in screen. */
Route::get('/app-branding', [\App\Http\Controllers\Api\ApiAppBrandingController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Public Website API (Next.js frontend — no auth)
|--------------------------------------------------------------------------
*/
Route::prefix('website')->group(function () {
    $api = \App\Http\Controllers\Api\Website\WebsiteApiController::class;
    Route::get('/settings', [$api, 'settings']);
    Route::get('/homepage', [$api, 'homepage']);
    Route::get('/pages/{slug}', [$api, 'page']);
    Route::get('/blogs', [$api, 'blogs']);
    Route::get('/blogs/search', [$api, 'searchBlogs']);
    Route::get('/blogs/{slug}', [$api, 'blog']);
    Route::get('/events', [$api, 'events']);
    Route::get('/events/{slug}', [$api, 'event']);
    Route::get('/testimonials', [$api, 'testimonials']);
    Route::get('/gallery', [$api, 'gallery']);
    Route::get('/media/hero', [$api, 'heroMedia']);
    Route::get('/faqs', [$api, 'faqs']);
    Route::get('/brand', [\App\Http\Controllers\Api\Website\WebsiteBrandApiController::class, 'index']);
    Route::post('/enquiry', [$api, 'enquiry']);

    // Sprint 6: Admissions engine
    $admissions = \App\Http\Controllers\Api\Website\AdmissionApplicationApiController::class;
    Route::post('/admissions/start', [$admissions, 'start']);
    Route::get('/admissions/options', [$admissions, 'options']);
    Route::post('/admissions/{token}/step', [$admissions, 'saveStep']);
    Route::post('/admissions/{token}/documents', [$admissions, 'uploadDocument']);
    Route::post('/admissions/{token}/submit', [$admissions, 'submit']);
    Route::get('/admissions/track/{applicationNo}', [$admissions, 'track']);

    // Sprint 9: SEO public endpoints
    Route::get('/sitemap.xml', [\App\Http\Controllers\Api\Website\WebsiteSeoController::class, 'sitemap']);
    Route::get('/robots.txt', [\App\Http\Controllers\Api\Website\WebsiteSeoController::class, 'robots']);

    // Sprint 10: Virtual tour
    Route::get('/virtual-tour', [\App\Http\Controllers\Api\Website\WebsiteMediaApiController::class, 'virtualTour']);
    Route::get('/gallery/albums', [\App\Http\Controllers\Api\Website\WebsiteMediaApiController::class, 'albums']);

    // Sprint 11: Newsletter
    Route::post('/newsletter/subscribe', [\App\Http\Controllers\Api\Website\NewsletterApiController::class, 'subscribe']);

    // Sprint 12: Analytics tracking (public)
    $analytics = \App\Http\Controllers\Api\Website\WebsiteAnalyticsApiController::class;
    Route::post('/analytics/page-view', [$analytics, 'trackView']);
    Route::post('/analytics/event', [$analytics, 'trackEvent']);

    // Sprint 13: Public school assistant
    Route::post('/assistant/chat', [\App\Http\Controllers\Api\Website\SchoolAssistantApiController::class, 'chat']);

    // Sprint 14: Student showcase
    Route::get('/showcase', [\App\Http\Controllers\Api\Website\StudentShowcaseApiController::class, 'index']);

    // Sprint 15: Live website panel (CMS meals + public noticeboard)
    $live = \App\Http\Controllers\Api\Website\LiveOperationsApiController::class;
    Route::get('/live', [$live, 'dashboard']);
    Route::get('/live/noticeboard', [$live, 'noticeboard']);
    Route::get('/live/status', [$live, 'schoolStatus']);
    Route::get('/live/meals', [$live, 'meals']);

    // Community (public website)
    $community = \App\Http\Controllers\Api\Website\CommunityApiController::class;
    Route::get('/community', [$community, 'index']);
    Route::post('/community/referrals', [$community, 'submitReferral']);
    Route::post('/community/prayer-requests', [$community, 'submitPrayer']);

    // Sprints 21–30: Conversion, SEO, events
    $conversion = \App\Http\Controllers\Api\Website\ConversionApiController::class;
    Route::get('/conversion/ctas', [$conversion, 'ctas']);
    Route::post('/conversion/cta-click', [$conversion, 'trackCta']);
    Route::get('/conversion/exit-intent', [$conversion, 'exitIntent']);
    Route::post('/conversion/exit-intent/convert', [$conversion, 'exitConvert']);
    Route::get('/conversion/lead-magnets', [$conversion, 'leadMagnets']);
    Route::post('/conversion/lead-magnets/{slug}/download', [$conversion, 'downloadLeadMagnet']);

    $seoEngine = \App\Http\Controllers\Api\Website\SeoDominanceApiController::class;
    Route::get('/seo/schema', [$seoEngine, 'schema']);
    Route::post('/seo/score', [$seoEngine, 'score']);
    Route::get('/seo/local-areas', [$seoEngine, 'localAreas']);
    Route::get('/seo/local-areas/{slug}', [$seoEngine, 'area']);

    Route::post('/events/{slug}/register', [\App\Http\Controllers\Api\Website\EventRegistrationApiController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Sprint 7: Parent portal website connector
    Route::prefix('website/parent')->group(function () {
        $parent = \App\Http\Controllers\Api\Website\ParentPortalApiController::class;
        Route::get('/dashboard', [$parent, 'dashboard']);
        Route::get('/children', [$parent, 'children']);
        Route::get('/children/{student}', [$parent, 'child']);
        Route::get('/children/{student}/statement', [$parent, 'statement']);
        Route::get('/children/{student}/attendance', [$parent, 'attendance']);
        Route::get('/report-cards', [$parent, 'reportCards']);
        Route::get('/announcements', [$parent, 'announcements']);
    });

    // Sprint 13: AI content (website CMS staff)
    Route::prefix('website/ai')->group(function () {
        $ai = \App\Http\Controllers\Api\Website\AiContentApiController::class;
        Route::get('/logs', [$ai, 'index']);
        Route::post('/generate', [$ai, 'generate']);
        Route::get('/logs/{id}', [$ai, 'show']);
    });

    Route::get('/user', [AuthApiController::class, 'user']);
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::post('/password/change', [ApiAccountController::class, 'changePassword']);
    Route::post('/device-tokens', [\App\Http\Controllers\Api\ApiDeviceTokenController::class, 'store']);
    Route::post('/device-tokens/revoke', [\App\Http\Controllers\Api\ApiDeviceTokenController::class, 'destroy']);
    Route::get('/notification-preferences', [ApiNotificationPreferencesController::class, 'show']);
    Route::put('/notification-preferences', [ApiNotificationPreferencesController::class, 'update']);
    Route::get('/admin-alerts', [\App\Http\Controllers\AdminAlertController::class, 'index']);
    Route::post('/admin-alerts/{id}/acknowledge', [\App\Http\Controllers\AdminAlertController::class, 'acknowledge']);
    Route::get('/staff-attendance/geofence', [ApiStaffClockController::class, 'geofence']);
    Route::put('/staff-attendance/geofence', [ApiStaffClockController::class, 'updateGeofence']);
    Route::get('/staff-attendance/me/today', [ApiStaffClockController::class, 'today']);
    Route::get('/staff-attendance/me/history', [ApiStaffClockController::class, 'history']);
    Route::get('/staff-attendance/clock-roster', [ApiStaffClockController::class, 'clockRoster']);
    Route::get('/staff-attendance/staff/history', [ApiStaffClockController::class, 'staffHistory']);
    Route::post('/staff-attendance/clock-in', [ApiStaffClockController::class, 'clockIn']);
    Route::post('/staff-attendance/clock-out', [ApiStaffClockController::class, 'clockOut']);

    Route::get('/search', [\App\Http\Controllers\Api\ApiSearchController::class, 'index']);
    Route::get('/search/suggest', [\App\Http\Controllers\Api\ApiSearchController::class, 'suggest']);
    Route::get('/audit-trail', [\App\Http\Controllers\Api\ApiAuditTrailController::class, 'index']);
    Route::get('/audit-trail/{id}', [\App\Http\Controllers\Api\ApiAuditTrailController::class, 'show']);
    Route::get('/sessions', [\App\Http\Controllers\Api\ApiSessionController::class, 'index']);
    Route::post('/sessions/revoke', [\App\Http\Controllers\Api\ApiSessionController::class, 'revoke']);
    Route::post('/auth/refresh', [\App\Http\Controllers\Api\ApiSessionController::class, 'refresh']);
    Route::get('/analytics/executive', [\App\Http\Controllers\Api\ApiAnalyticsController::class, 'executive']);

    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\ApiDashboardController::class, 'stats']);
    Route::get('/finance/summary', [\App\Http\Controllers\Api\ApiFinanceSummaryController::class, 'show']);
    Route::get('/operations/summary', [\App\Http\Controllers\Api\ApiOperationsSummaryController::class, 'show']);
    Route::get('/approvals', [\App\Http\Controllers\Api\ApiApprovalsController::class, 'index']);
    Route::get('/approvals/{compositeId}', [\App\Http\Controllers\Api\ApiApprovalsController::class, 'show'])
        ->where('compositeId', '.*');
    Route::post('/approvals/{compositeId}/approve', [\App\Http\Controllers\Api\ApiApprovalsController::class, 'approve'])
        ->where('compositeId', '.*');
    Route::post('/approvals/{compositeId}/reject', [\App\Http\Controllers\Api\ApiApprovalsController::class, 'reject'])
        ->where('compositeId', '.*');

    Route::prefix('admissions')->group(function () {
        $admissions = \App\Http\Controllers\Api\ApiAdmissionsController::class;
        Route::get('/stats', [$admissions, 'stats']);
        Route::get('/', [$admissions, 'index']);
        Route::get('/{admission}/files/{field}', [$admissions, 'downloadFile']);
        Route::put('/{admission}/status', [$admissions, 'updateStatus']);
        Route::post('/{admission}/waitlist', [$admissions, 'waitlist']);
        Route::post('/{admission}/reject', [$admissions, 'reject']);
        Route::post('/{admission}/enroll', [$admissions, 'enroll']);
        Route::get('/{admission}', [$admissions, 'show']);
    });
    Route::get('/student-categories', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'categories']);
    Route::get('/students/search', [\App\Http\Controllers\Students\StudentController::class, 'search']);
    Route::get('/students', [\App\Http\Controllers\Api\ApiStudentController::class, 'index']);
    Route::post('/students', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'store']);
    Route::get('/students/{id}/stats', [\App\Http\Controllers\Api\ApiStudentController::class, 'stats']);
    Route::get('/students/{student}/assessment-history', [\App\Http\Controllers\Api\ApiStudentAssessmentController::class, 'assessmentHistory']);
    Route::get('/students/{student}/academic-summary', [\App\Http\Controllers\Api\ApiStudentAssessmentController::class, 'academicSummary']);
    Route::get('/students/{id}/attendance-calendar', [\App\Http\Controllers\Api\ApiStudentController::class, 'attendanceCalendar']);
    Route::get('/students/{id}/statement', [\App\Http\Controllers\Api\ApiStudentStatementController::class, 'show']);
    Route::get('/students/{id}/profile-update-link', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'profileUpdateLink']);
    Route::post('/students/{id}/update', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'update']);
    Route::post('/students/{id}/mpesa/prompt', [\App\Http\Controllers\Api\ApiMpesaPaymentController::class, 'prompt']);
    Route::get('/students/{id}/mpesa/payment-link', [\App\Http\Controllers\Api\ApiMpesaPaymentController::class, 'paymentLinkUrl']);
    Route::get('/students/{id}/fee-clearance', [ApiFeeClearanceController::class, 'show']);
    Route::get('/students/{id}/documents', [\App\Http\Controllers\Api\ApiStudentDocumentsController::class, 'index']);
    Route::get('/students/{studentId}/documents/{documentId}/download', [\App\Http\Controllers\Api\ApiStudentDocumentsController::class, 'download']);
    Route::get('/students/{studentId}/medical-records', [\App\Http\Controllers\Api\ApiMedicalRecordsController::class, 'index']);
    Route::post('/students/{studentId}/medical-records', [\App\Http\Controllers\Api\ApiMedicalRecordsController::class, 'store']);
    Route::get('/students/{studentId}/medical-records/{id}', [\App\Http\Controllers\Api\ApiMedicalRecordsController::class, 'show']);
    Route::post('/students/{studentId}/medical-records/{id}/certificate', [\App\Http\Controllers\Api\ApiMedicalRecordsController::class, 'uploadCertificate']);
    Route::get('/students/{id}', [\App\Http\Controllers\Api\ApiStudentController::class, 'show']);
    Route::get('/invoices', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'show']);
    Route::get('/fee-structures', [\App\Http\Controllers\Api\ApiFeeStructureController::class, 'index']);
    Route::get('/payments', [\App\Http\Controllers\Api\ApiPaymentController::class, 'index']);
    Route::get('/payments/{id}', [\App\Http\Controllers\Api\ApiPaymentController::class, 'show']);
    Route::post('/payments', [\App\Http\Controllers\Api\ApiPaymentController::class, 'store']);
    Route::prefix('jenga')->group(function () {
        Route::post('/token', [\App\Http\Controllers\Api\ApiJengaController::class, 'token']);
        Route::get('/accounts/{countryCode}/{accountNumber}/inquiry', [\App\Http\Controllers\Api\ApiJengaController::class, 'accountInquiry']);
        Route::get('/accounts/{countryCode}/{accountId}/balance', [\App\Http\Controllers\Api\ApiJengaController::class, 'accountBalance']);
        Route::get('/accounts/{countryCode}/{accountNumber}/mini-statement', [\App\Http\Controllers\Api\ApiJengaController::class, 'miniStatement']);
        Route::post('/accounts/full-statement', [\App\Http\Controllers\Api\ApiJengaController::class, 'fullStatement']);

        Route::post('/collect/stk-ussd-push', [\App\Http\Controllers\Api\ApiJengaController::class, 'stkUssdPush']);

        Route::post('/disburse/mobile-wallet', [\App\Http\Controllers\Api\ApiJengaController::class, 'disburseMobile']);
        Route::post('/disburse/within-equity', [\App\Http\Controllers\Api\ApiJengaController::class, 'disburseWithinEquity']);
        Route::post('/disburse/rtgs', [\App\Http\Controllers\Api\ApiJengaController::class, 'disburseRtgs']);
        Route::get('/disburse/rtgs/payment-purposes', [\App\Http\Controllers\Api\ApiJengaController::class, 'rtgsPaymentPurposes']);

        Route::get('/queries/transactions/{reference}', [\App\Http\Controllers\Api\ApiJengaController::class, 'queryTransactionDetails']);
        Route::get('/queries/billers', [\App\Http\Controllers\Api\ApiJengaController::class, 'billers']);
        Route::get('/queries/merchants', [\App\Http\Controllers\Api\ApiJengaController::class, 'merchants']);
        Route::post('/signed-proxy', [\App\Http\Controllers\Api\ApiJengaController::class, 'signedProxy']);
    });

    Route::get('/finance/transactions', [\App\Http\Controllers\Api\ApiFinanceTransactionsController::class, 'index']);
    Route::post('/finance/transactions/mark-swimming', [\App\Http\Controllers\Finance\BankStatementController::class, 'bulkMarkAsSwimming']);
    Route::post('/finance/transactions/{bankStatement}/confirm', [\App\Http\Controllers\Finance\BankStatementController::class, 'confirm']);
    Route::post('/finance/transactions/{bankStatement}/reject', [\App\Http\Controllers\Finance\BankStatementController::class, 'reject']);
    Route::post('/finance/transactions/{bankStatement}/share', [\App\Http\Controllers\Finance\BankStatementController::class, 'share']);
    Route::post('/finance/transactions/{bankStatement}/assign', [\App\Http\Controllers\Finance\BankStatementController::class, 'assign']);
    Route::get('/finance/transactions/{id}', [\App\Http\Controllers\Api\ApiFinanceTransactionsController::class, 'show']);

    Route::get('/classes', [\App\Http\Controllers\Api\ApiClassroomController::class, 'index']);
    Route::get('/classes/{classId}/streams', [\App\Http\Controllers\Api\ApiClassroomController::class, 'streams']);
    Route::get('/classes/{classId}/subjects', [\App\Http\Controllers\Api\ApiClassroomController::class, 'subjects']);
    Route::get('/teacher-assignments/stream-slots', [\App\Http\Controllers\Api\ApiTeacherAssignmentController::class, 'streamSlots']);
    Route::get('/staff/{id}/teaching-assignments', [\App\Http\Controllers\Api\ApiTeacherAssignmentController::class, 'show']);
    Route::put('/staff/{id}/teaching-assignments', [\App\Http\Controllers\Api\ApiTeacherAssignmentController::class, 'update']);
    Route::get('/staff/{id}/archive-preview', [\App\Http\Controllers\Api\ApiStaffController::class, 'archivePreview']);
    Route::post('/staff/{id}/archive', [\App\Http\Controllers\Api\ApiStaffController::class, 'archive']);
    Route::get('/staff/filter-options', [\App\Http\Controllers\Api\ApiStaffController::class, 'filterOptions']);
    Route::get('/staff', [\App\Http\Controllers\Api\ApiStaffController::class, 'index']);
    Route::get('/staff/{id}/leave-balances', [\App\Http\Controllers\Api\ApiStaffController::class, 'leaveBalances']);
    Route::get('/staff/{id}/attendance-history', [\App\Http\Controllers\Api\ApiStaffController::class, 'attendanceHistory']);
    Route::get('/staff/{id}/documents', [\App\Http\Controllers\Api\ApiStaffDocumentsController::class, 'index']);
    Route::get('/staff/{staffId}/documents/{documentId}/download', [\App\Http\Controllers\Api\ApiStaffDocumentsController::class, 'download']);
    Route::get('/staff/{staffId}/performance-reviews', [\App\Http\Controllers\Api\ApiStaffPerformanceController::class, 'index']);
    Route::get('/staff/{staffId}/performance-reviews/{id}', [\App\Http\Controllers\Api\ApiStaffPerformanceController::class, 'show']);
    Route::get('/staff/{staffId}/training-records', [\App\Http\Controllers\Api\ApiStaffTrainingController::class, 'index']);
    Route::get('/staff/{staffId}/training-records/{id}', [\App\Http\Controllers\Api\ApiStaffTrainingController::class, 'show']);
    Route::get('/staff/{id}', [\App\Http\Controllers\Api\ApiStaffController::class, 'show']);
    Route::put('/staff/{id}', [\App\Http\Controllers\Api\ApiStaffController::class, 'update']);
    Route::post('/staff/{id}/photo', [\App\Http\Controllers\Api\ApiStaffController::class, 'uploadPhoto']);
    Route::get('/payroll-records', [\App\Http\Controllers\Api\ApiPayrollRecordsController::class, 'index']);
    Route::get('/payroll-records/{id}/payslip/download', [\App\Http\Controllers\Api\ApiPayslipController::class, 'download']);
    Route::get('/vehicles', [\App\Http\Controllers\Api\ApiVehicleController::class, 'index']);
    Route::get('/vehicles/{id}', [\App\Http\Controllers\Api\ApiVehicleController::class, 'show']);
    Route::post('/vehicles', [\App\Http\Controllers\Api\ApiVehicleController::class, 'store']);
    Route::put('/vehicles/{id}', [\App\Http\Controllers\Api\ApiVehicleController::class, 'update']);
    Route::delete('/vehicles/{id}', [\App\Http\Controllers\Api\ApiVehicleController::class, 'destroy']);
    Route::get('/routes', [\App\Http\Controllers\Api\ApiRouteController::class, 'index']);
    Route::post('/routes', [\App\Http\Controllers\Api\ApiRouteController::class, 'store']);
    Route::get('/routes/{id}', [\App\Http\Controllers\Api\ApiRouteController::class, 'show']);
    Route::put('/routes/{id}', [\App\Http\Controllers\Api\ApiRouteController::class, 'update']);
    Route::delete('/routes/{id}', [\App\Http\Controllers\Api\ApiRouteController::class, 'destroy']);
    Route::get('/routes/{id}/fee-clearance-roster', [ApiFeeClearanceController::class, 'tripRoster']);
    Route::get('/leave-types', [\App\Http\Controllers\Api\ApiLeaveRequestController::class, 'leaveTypes']);
    Route::get('/leave-requests', [\App\Http\Controllers\Api\ApiLeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [\App\Http\Controllers\Api\ApiLeaveRequestController::class, 'store']);
    Route::post('/leave-requests/{id}/approve', [\App\Http\Controllers\Api\ApiLeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{id}/reject', [\App\Http\Controllers\Api\ApiLeaveRequestController::class, 'reject']);
    Route::get('/library/books', [\App\Http\Controllers\Api\ApiLibraryController::class, 'index']);
    Route::get('/library/borrowings', [\App\Http\Controllers\Api\ApiLibraryController::class, 'borrowings']);
    Route::post('/library/borrowings', [\App\Http\Controllers\Api\ApiLibraryController::class, 'issue']);
    Route::post('/library/borrowings/{id}/return', [\App\Http\Controllers\Api\ApiLibraryController::class, 'returnBorrowing']);
    Route::post('/library/borrowings/{id}/renew', [\App\Http\Controllers\Api\ApiLibraryController::class, 'renew']);
    Route::get('/announcements', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'index']);
    Route::post('/announcements', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'store']);
    Route::get('/announcements/{id}', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'show']);
    Route::put('/announcements/{id}', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'destroy']);

    Route::get('/communication/templates', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'templates']);
    Route::post('/communication/templates', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'templateStore']);
    Route::get('/communication/templates/{id}', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'templateShow']);
    Route::put('/communication/templates/{id}', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'templateUpdate']);
    Route::delete('/communication/templates/{id}', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'templateDestroy']);
    Route::get('/communication/logs', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'logs']);
    Route::get('/communication/logs/{id}', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'logShow']);
    Route::get('/communication/recipients', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'recipients']);
    Route::post('/communication/sms', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'sendSms']);
    Route::post('/communication/whatsapp', [\App\Http\Controllers\Api\ApiCommunicationController::class, 'sendWhatsApp']);

    Route::get('/inventory/items', [\App\Http\Controllers\Api\ApiInventoryController::class, 'index']);
    Route::get('/inventory/items/{id}', [\App\Http\Controllers\Api\ApiInventoryController::class, 'show']);
    Route::post('/inventory/items/{id}/adjust', [\App\Http\Controllers\Api\ApiInventoryController::class, 'adjust']);
    Route::get('/requisitions', [\App\Http\Controllers\Api\ApiRequisitionController::class, 'index']);
    Route::post('/requisitions', [\App\Http\Controllers\Api\ApiRequisitionController::class, 'store']);
    Route::get('/requisitions/{id}', [\App\Http\Controllers\Api\ApiRequisitionController::class, 'show']);
    Route::post('/requisitions/{id}/approve', [\App\Http\Controllers\Api\ApiRequisitionController::class, 'approve']);
    Route::post('/requisitions/{id}/reject', [\App\Http\Controllers\Api\ApiRequisitionController::class, 'reject']);

    Route::get('/visitors', [\App\Http\Controllers\Api\ApiVisitorsController::class, 'index']);
    Route::post('/visitors', [\App\Http\Controllers\Api\ApiVisitorsController::class, 'store']);
    Route::get('/visitors/{id}', [\App\Http\Controllers\Api\ApiVisitorsController::class, 'show']);
    Route::post('/visitors/{id}/checkout', [\App\Http\Controllers\Api\ApiVisitorsController::class, 'checkout']);

    Route::get('/assets', [\App\Http\Controllers\Api\ApiFixedAssetsController::class, 'index']);
    Route::post('/assets', [\App\Http\Controllers\Api\ApiFixedAssetsController::class, 'store']);
    Route::get('/assets/{id}', [\App\Http\Controllers\Api\ApiFixedAssetsController::class, 'show']);
    Route::put('/assets/{id}', [\App\Http\Controllers\Api\ApiFixedAssetsController::class, 'update']);
    Route::post('/assets/{id}/status', [\App\Http\Controllers\Api\ApiFixedAssetsController::class, 'updateStatus']);

    Route::get('/reports/weekly', [\App\Http\Controllers\Api\ApiWeeklyReportsController::class, 'index']);
    Route::get('/reports/weekly/{type}/{id}', [\App\Http\Controllers\Api\ApiWeeklyReportsController::class, 'show']);
    Route::get('/reports/expenses/summary', [\App\Http\Controllers\Api\ApiExpenseReportsController::class, 'summary']);
    Route::get('/reports/income-statement', [\App\Http\Controllers\Api\ApiExpenseReportsController::class, 'incomeStatement']);
    Route::get('/reports/board-pack', [\App\Http\Controllers\Api\ApiBoardPackController::class, 'show']);

    Route::get('/expenses', [\App\Http\Controllers\Api\ApiExpensesController::class, 'index']);
    Route::get('/expenses/{id}', [\App\Http\Controllers\Api\ApiExpensesController::class, 'show']);
    Route::post('/expenses/{id}/submit', [\App\Http\Controllers\Api\ApiExpensesController::class, 'submit']);
    Route::post('/expenses/{id}/approve', [\App\Http\Controllers\Api\ApiExpensesController::class, 'approve']);
    Route::post('/expenses/{id}/reject', [\App\Http\Controllers\Api\ApiExpensesController::class, 'reject']);
    Route::post('/expenses/{id}/pay', [\App\Http\Controllers\Api\ApiExpensesController::class, 'pay']);
    Route::post('/expenses/{id}/attachments', [\App\Http\Controllers\Api\ApiExpensesController::class, 'storeAttachment']);
    Route::delete('/expenses/{id}/attachments/{attachmentId}', [\App\Http\Controllers\Api\ApiExpensesController::class, 'destroyAttachment']);

    Route::get('/ledger/postings', [\App\Http\Controllers\Api\ApiLedgerController::class, 'postings']);
    Route::get('/ledger/trial-balance', [\App\Http\Controllers\Api\ApiLedgerController::class, 'trialBalance']);
    Route::get('/reports/balance-sheet', [\App\Http\Controllers\Api\ApiLedgerController::class, 'balanceSheet']);

    Route::get('/cbc/learning-areas', [\App\Http\Controllers\Api\ApiCbcController::class, 'learningAreas']);
    Route::get('/cbc/strands', [\App\Http\Controllers\Api\ApiCbcController::class, 'strands']);
    Route::get('/cbc/substrands', [\App\Http\Controllers\Api\ApiCbcController::class, 'substrands']);
    Route::get('/cbc/substrands/{id}', [\App\Http\Controllers\Api\ApiCbcController::class, 'substrandShow']);

    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\ApiNotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\ApiNotificationController::class, 'unreadCount']);
    Route::get('/notifications', [\App\Http\Controllers\Api\ApiNotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\ApiNotificationController::class, 'markRead']);
    Route::post('/notifications/{id}/acknowledge', [\App\Http\Controllers\Api\ApiNotificationController::class, 'acknowledge']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\ApiNotificationController::class, 'destroy']);
    Route::get('/attendance/class', [\App\Http\Controllers\Api\ApiAttendanceController::class, 'classAttendance']);
    Route::get('/attendance/school-day', [\App\Http\Controllers\Api\ApiAttendanceController::class, 'schoolDay']);
    Route::post('/attendance/mark', [\App\Http\Controllers\Api\ApiAttendanceController::class, 'mark']);

    Route::get('/classes/{classId}/fee-clearance-roster', [ApiFeeClearanceController::class, 'classRoster']);

    Route::get('/timetables/teacher/{staffId}', [\App\Http\Controllers\Api\ApiTimetableController::class, 'teacher']);
    Route::get('/timetables/student/{studentId}', [\App\Http\Controllers\Api\ApiTimetableController::class, 'student']);

    Route::get('/assignments', [\App\Http\Controllers\Api\ApiHomeworkController::class, 'index']);
    Route::post('/assignments', [\App\Http\Controllers\Api\ApiHomeworkController::class, 'store']);
    Route::get('/assignments/{id}', [\App\Http\Controllers\Api\ApiHomeworkController::class, 'show']);
    Route::get('/lesson-plans', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'index']);
    Route::post('/lesson-plans', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'store']);
    Route::get('/lesson-plans/review-queue', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'reviewQueue']);
    Route::get('/lesson-plans/{id}', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'show']);
    Route::put('/lesson-plans/{id}', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'update']);
    Route::post('/lesson-plans/{id}/submit', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'submit']);
    Route::post('/lesson-plans/{id}/approve', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'approve']);
    Route::post('/lesson-plans/{id}/reject', [\App\Http\Controllers\Api\ApiLessonPlansController::class, 'reject']);

    Route::get('/exams', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'exams']);
    Route::get('/exams/{id}/marking-options', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'examMarkingOptions']);
    Route::get('/exams/{id}', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'showExam']);
    Route::get('/marks', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'marks']);
    Route::post('/exam-marks/batch', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'batchMarks']);
    Route::get('/exams/{exam}/mark-entry-audit', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'examMarkEntryAudit']);
    Route::post('/exam-marks/{exam}/submit', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'submitExamMarks']);
    Route::get('/marks/matrix/context', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'marksMatrixContext']);
    Route::get('/marks/matrix', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'marksMatrix']);
    Route::post('/exam-marks/matrix/batch', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'batchMarksMatrix']);

    // Exam Reports & Analysis
    Route::get('/reports/exams/class-sheet', [ApiExamReportsController::class, 'classSheet']);
    Route::get('/reports/exams/teacher-performance', [ApiExamReportsController::class, 'teacherPerformance']);
    Route::get('/reports/exams/subject-performance', [ApiExamReportsController::class, 'subjectPerformance']);
    Route::get('/reports/exams/student-insights', [ApiExamReportsController::class, 'studentInsights']);
    Route::get('/reports/exams/trends', [ApiExamReportsController::class, 'trends']);
    Route::get('/reports/exams/insights', [ApiExamReportsController::class, 'insights']);
    Route::get('/reports/exams/mastery-profile', [ApiExamReportsController::class, 'masteryProfile']);
    Route::get('/reports/exams/export/class-sheet.xlsx', [ApiExamReportsController::class, 'exportClassSheet']);
    Route::get('/reports/exams/export/term-workbook.xlsx', [ApiExamReportsController::class, 'exportTermWorkbook']);

    Route::prefix('senior-teacher')->group(function () {
        Route::get('/supervised-classrooms', [ApiSeniorTeacherController::class, 'supervisedClassrooms']);
        Route::get('/supervised-staff', [ApiSeniorTeacherController::class, 'supervisedStaff']);
        Route::get('/fee-balances', [ApiSeniorTeacherController::class, 'feeBalances']);
        Route::get('/fee-clearances/pending', [ApiSeniorTeacherController::class, 'pendingFeeClearances']);
        Route::get('/students', [ApiSeniorTeacherController::class, 'supervisedStudents']);
    });

    Route::prefix('teacher/requirements')->group(function () {
        Route::get('/students', [\App\Http\Controllers\Api\ApiTeacherRequirementsController::class, 'students']);
        Route::get('/students/{student}/templates', [\App\Http\Controllers\Api\ApiTeacherRequirementsController::class, 'templatesForStudent']);
        Route::post('/collect', [\App\Http\Controllers\Api\ApiTeacherRequirementsController::class, 'collect']);
    });

    Route::prefix('teacher/transport')->group(function () {
        Route::get('/students', [\App\Http\Controllers\Api\ApiTeacherTransportController::class, 'students']);
        Route::get('/vehicles', [\App\Http\Controllers\Api\ApiTeacherTransportController::class, 'vehicles']);
        Route::post('/pickups', [\App\Http\Controllers\Api\ApiTeacherTransportController::class, 'markCollectedByParent']);
        Route::delete('/pickups/{pickupId}', [\App\Http\Controllers\Api\ApiTeacherTransportController::class, 'cancelPickup']);
        Route::post('/reassign', [\App\Http\Controllers\Api\ApiTeacherTransportController::class, 'temporaryReassignment']);
    });

    Route::prefix('academic-reports')->group(function () {
        // Template builder (Super Admin / Admin / Academic Admin / Senior Teacher)
        Route::get('/templates', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'templates']);
        Route::post('/templates', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'storeTemplate']);
        Route::put('/templates/{template}', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'updateTemplate']);
        Route::post('/templates/{template}/publish', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'publish']);

        // Mobile fill
        Route::get('/assigned', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'assigned']);
        Route::get('/templates/{template}', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'showTemplate']);
        Route::post('/submissions', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'submit']);
        Route::post('/submissions/{submission}/questions/{question}/file', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'uploadFile']);

        // View submissions (managers)
        Route::get('/submissions', [\App\Http\Controllers\Api\ApiAcademicReportsController::class, 'submissions']);
    });

    Route::prefix('feedback')->group(function () {
        Route::get('/template', [\App\Http\Controllers\Api\ApiFeedbackController::class, 'template']);
        Route::post('/submit', [\App\Http\Controllers\Api\ApiFeedbackController::class, 'submit']);
        Route::post('/submissions/{submission}/questions/{question}/file', [\App\Http\Controllers\Api\ApiFeedbackController::class, 'uploadFile']);
    });

    Route::get('/driver/trips', [\App\Http\Controllers\Api\ApiDriverTransportController::class, 'index']);
    Route::get('/driver/trips/{trip}', [\App\Http\Controllers\Api\ApiDriverTransportController::class, 'show']);

    Route::get('/report-cards', [\App\Http\Controllers\Api\ApiReportCardController::class, 'index']);
    Route::get('/report-cards/{id}', [\App\Http\Controllers\Api\ApiReportCardController::class, 'show']);

    Route::prefix('settings')->group(function () {
        $hub = \App\Http\Controllers\Api\ApiSettingsHubController::class;
        Route::get('/school', [$hub, 'school']);
        Route::get('/academic-years', [$hub, 'academicYears']);
        Route::get('/terms', [$hub, 'terms']);
        Route::get('/classes', [$hub, 'classes']);
        Route::get('/classes/{classId}/streams', [$hub, 'streams']);
        Route::get('/subjects', [$hub, 'subjects']);
        Route::get('/grading', [$hub, 'gradingSchemes']);
        Route::get('/roles', [$hub, 'roles']);
    });
});

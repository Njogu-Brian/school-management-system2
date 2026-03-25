<?php

use App\Http\Controllers\Api\AuthApiController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthApiController::class, 'user']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\ApiDashboardController::class, 'stats']);
    Route::get('/student-categories', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'categories']);
    Route::get('/students', [\App\Http\Controllers\Api\ApiStudentController::class, 'index']);
    Route::post('/students', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'store']);
    Route::get('/students/{id}/stats', [\App\Http\Controllers\Api\ApiStudentController::class, 'stats']);
    Route::get('/students/{id}/attendance-calendar', [\App\Http\Controllers\Api\ApiStudentController::class, 'attendanceCalendar']);
    Route::get('/students/{id}/statement', [\App\Http\Controllers\Api\ApiStudentStatementController::class, 'show']);
    Route::get('/students/{id}/profile-update-link', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'profileUpdateLink']);
    Route::post('/students/{id}/update', [\App\Http\Controllers\Api\ApiStudentWriteController::class, 'update']);
    Route::post('/students/{id}/mpesa/prompt', [\App\Http\Controllers\Api\ApiMpesaPaymentController::class, 'prompt']);
    Route::get('/students/{id}/mpesa/payment-link', [\App\Http\Controllers\Api\ApiMpesaPaymentController::class, 'paymentLinkUrl']);
    Route::get('/students/{id}', [\App\Http\Controllers\Api\ApiStudentController::class, 'show']);
    Route::get('/invoices', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'show']);
    Route::get('/payments', [\App\Http\Controllers\Api\ApiPaymentController::class, 'index']);
    Route::get('/payments/{id}', [\App\Http\Controllers\Api\ApiPaymentController::class, 'show']);
    Route::post('/payments', [\App\Http\Controllers\Api\ApiPaymentController::class, 'store']);

    Route::get('/finance/transactions', [\App\Http\Controllers\Api\ApiFinanceTransactionsController::class, 'index']);
    Route::post('/finance/transactions/mark-swimming', [\App\Http\Controllers\Finance\BankStatementController::class, 'bulkMarkAsSwimming']);
    Route::post('/finance/transactions/{id}/share', [\App\Http\Controllers\Finance\BankStatementController::class, 'share']);
    Route::get('/finance/transactions/{id}', [\App\Http\Controllers\Api\ApiFinanceTransactionsController::class, 'show']);

    Route::get('/classes', [\App\Http\Controllers\Api\ApiClassroomController::class, 'index']);
    Route::get('/classes/{classId}/streams', [\App\Http\Controllers\Api\ApiClassroomController::class, 'streams']);
    Route::get('/staff', [\App\Http\Controllers\Api\ApiStaffController::class, 'index']);
    Route::get('/staff/{id}', [\App\Http\Controllers\Api\ApiStaffController::class, 'show']);
    Route::get('/routes', [\App\Http\Controllers\Api\ApiRouteController::class, 'index']);
    Route::get('/library/books', [\App\Http\Controllers\Api\ApiLibraryController::class, 'index']);
    Route::get('/announcements', [\App\Http\Controllers\Api\ApiAnnouncementController::class, 'index']);
    Route::get('/attendance/class', [\App\Http\Controllers\Api\ApiAttendanceController::class, 'classAttendance']);
    Route::post('/attendance/mark', [\App\Http\Controllers\Api\ApiAttendanceController::class, 'mark']);

    Route::get('/exams', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'exams']);
    Route::get('/exams/{id}/marking-options', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'examMarkingOptions']);
    Route::get('/exams/{id}', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'showExam']);
    Route::get('/marks', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'marks']);
    Route::post('/exam-marks/batch', [\App\Http\Controllers\Api\ApiAcademicsController::class, 'batchMarks']);
});

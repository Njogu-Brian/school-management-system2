<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Controller Imports
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Attendance
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Attendance\AttendanceNotificationController;
use App\Http\Controllers\Attendance\AttendanceReasonCodeController;

// Transport
use App\Http\Controllers\TransportController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DropOffPointController;
use App\Http\Controllers\StudentAssignmentController;

// Staff / HR
use App\Http\Controllers\Hr\StaffController;
use App\Http\Controllers\Hr\StaffProfileController;
use App\Http\Controllers\Hr\RolePermissionController;
use App\Http\Controllers\Hr\LookupController; // HR lookup CRUD (categories, departments, job titles, custom fields)
use App\Http\Controllers\Academics\AcademicConfigController;
use App\Http\Controllers\Settings\SchoolDayController;
use App\Http\Controllers\Settings\SettingController;

// Students & Parents
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\FamilyUpdateController;
use App\Http\Controllers\Students\ParentInfoController;
use App\Http\Controllers\Students\OnlineAdmissionController;
use App\Http\Controllers\Students\FamilyController;
use App\Http\Controllers\Students\MedicalRecordController;
use App\Http\Controllers\Students\DisciplinaryRecordController;
use App\Http\Controllers\Students\ExtracurricularActivityController;
use App\Http\Controllers\Students\AcademicHistoryController;
use App\Http\Controllers\Students\StudentCategoryController;
use App\Http\Controllers\FileDownloadController;

// Communication
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CommunicationTemplateController;
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\WasenderSessionController;
use App\Http\Controllers\CommunicationDocumentController;

// Finance
use App\Http\Controllers\Finance\VoteheadController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\BankAccountController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Finance\CreditNoteController;
use App\Http\Controllers\Finance\DebitNoteController;
use App\Http\Controllers\Finance\InvoiceAdjustmentController;
use App\Http\Controllers\Finance\OptionalFeeController;
use App\Http\Controllers\Finance\PostingController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\Finance\FeeStatementController;
use App\Http\Controllers\Finance\StudentStatementController;
use App\Http\Controllers\Finance\ReceiptController;
use App\Http\Controllers\Finance\FeeReminderController;
use App\Http\Controllers\Finance\FeePaymentPlanController;
use App\Http\Controllers\Finance\FeeConcessionController;
use App\Http\Controllers\Finance\DiscountController;
use App\Http\Controllers\Finance\DocumentSettingsController;
use App\Http\Controllers\Finance\LegacyFinanceImportController;
use App\Http\Controllers\Finance\TransportFeeController;
use App\Http\Controllers\Finance\BankStatementController;

// Academics
use App\Http\Controllers\Academics\ClassroomController;
use App\Http\Controllers\Academics\StreamController;
use App\Http\Controllers\Academics\SubjectGroupController;
use App\Http\Controllers\Academics\SubjectController;
use App\Http\Controllers\Academics\ExamController;
use App\Http\Controllers\Academics\ExamGradeController;
use App\Http\Controllers\Academics\ExamMarkController;
use App\Http\Controllers\Academics\ReportCardController;
use App\Http\Controllers\Academics\ReportCardSkillController;
use App\Http\Controllers\Academics\BehaviourController;
use App\Http\Controllers\Academics\HomeworkController;
use App\Http\Controllers\Academics\HomeworkDiaryController;
use App\Http\Controllers\Academics\StudentDiaryController;
use App\Http\Controllers\Academics\StudentBehaviourController;
use App\Http\Controllers\Academics\ExamScheduleController;
use App\Http\Controllers\Academics\ExamTypeController;
use App\Http\Controllers\Academics\ExamResultController;
use App\Http\Controllers\Academics\ExamPublishingController;
use App\Http\Controllers\Academics\StudentSkillGradeController;
use App\Http\Controllers\Academics\SchemeOfWorkController;
use App\Http\Controllers\Academics\LessonPlanController;
use App\Http\Controllers\Academics\CBCStrandController;
use App\Http\Controllers\Academics\CBCSubstrandController;
use App\Http\Controllers\Academics\LearningAreaController;
use App\Http\Controllers\Academics\CompetencyController;
use App\Http\Controllers\Academics\PortfolioAssessmentController;
use App\Http\Controllers\Academics\TimetableController;
use App\Http\Controllers\Academics\ExtraCurricularActivityController as AcademicsExtraCurricularActivityController;
use App\Http\Controllers\Academics\CurriculumDesignController;
use App\Http\Controllers\Academics\CurriculumAssistantController;
use App\Http\Controllers\Academics\ExamAnalyticsController;
use App\Http\Controllers\ParentPortal\DiaryController as ParentDiaryController;

// Communication

// Events & Documents
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\DocumentManagementController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\GeneratedDocumentController;
use App\Http\Controllers\BackupRestoreController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Password Reset
    Route::get('/password/reset', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'reset'])->name('password.update');
    
    // OTP Password Reset
    Route::get('/password/reset-otp', [AuthController::class, 'showOTPResetForm'])->name('password.reset.otp');
    Route::post('/password/reset-otp', [AuthController::class, 'resetWithOTP'])->name('password.reset.otp.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Public student search (kept public per your original)
Route::get('/students/search', [StudentController::class, 'search'])->name('students.search');

// Public finance views (no authentication required, using hashed IDs/tokens)
// These routes explicitly use hashed_id/public_token, not numeric IDs
Route::get('receipt/{token}', [\App\Http\Controllers\Finance\PaymentController::class, 'publicViewReceipt'])
    ->where('token', '[A-Za-z0-9]{10}') // Only 10-char tokens, not numeric IDs
    ->name('receipts.public');
Route::get('invoice/{hash}', [\App\Http\Controllers\Finance\InvoiceController::class, 'publicView'])
    ->where('hash', '[A-Za-z0-9]{10}') // Only 10-char hashes, not numeric IDs
    ->name('invoices.public');
Route::get('statement/{hash}', [\App\Http\Controllers\Finance\StudentStatementController::class, 'publicView'])
    ->where('hash', '[A-Za-z0-9]{10}') // Only 10-char hashes, not numeric IDs
    ->name('statements.public');
Route::get('payment-plan/{hash}', [\App\Http\Controllers\Finance\FeePaymentPlanController::class, 'publicView'])
    ->where('hash', '[A-Za-z0-9]{10}') // Only 10-char hashes, not numeric IDs
    ->name('payment-plans.public');

// SMS Delivery Report Webhook
Route::post('/webhooks/sms/dlr', [CommunicationController::class, 'smsDeliveryReport'])->name('webhooks.sms.dlr');
Route::post('/webhooks/whatsapp/wasender', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp.wasender');

// Payment Webhooks (public, no auth required)
Route::post('/webhooks/payment/mpesa', [\App\Http\Controllers\PaymentWebhookController::class, 'handleMpesa'])->name('payment.webhook.mpesa');
Route::post('/webhooks/payment/stripe', [\App\Http\Controllers\PaymentWebhookController::class, 'handleStripe'])->name('payment.webhook.stripe');
Route::post('/webhooks/payment/paypal', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePaypal'])->name('payment.webhook.paypal');

// Public family profile update (no auth)
Route::get('/family-update/{token}', [FamilyUpdateController::class, 'publicForm'])->name('family-update.form');
Route::post('/family-update/{token}', [FamilyUpdateController::class, 'submit'])->name('family-update.submit');

// Admin file download for private files
Route::middleware(['auth', 'role:Super Admin|Admin|Secretary'])->group(function () {
    Route::get('/admin/files/{model}/{id}/{field}', [FileDownloadController::class, 'show'])
        ->name('file.download');
});

/*
|--------------------------------------------------------------------------
| Self-service Profile (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/my/profile',  [StaffProfileController::class, 'show'])->name('staff.profile.show');
    Route::post('/my/profile', [StaffProfileController::class, 'update'])->name('staff.profile.update');
    // Friendly alias
    Route::redirect('/profile', '/my/profile')->name('staff.profile.alias');
});

/*
|--------------------------------------------------------------------------
| Home redirect by role (Auth)
|--------------------------------------------------------------------------
*/
Route::get('/home', function () {
    $user = Auth::user();

    // Prefer Spatie helpers; also be tolerant of case/aliases
    $aliases = [
        'super admin' => 'admin.dashboard',
        'admin'       => 'admin.dashboard',
        'secretary'   => 'admin.dashboard',
        'teacher'     => 'teacher.dashboard',
        'driver'      => 'transport.dashboard',
        'parent'      => 'parent.dashboard',
        'student'     => 'student.dashboard',
    ];

    // If the user has any role that maps above, send them there.
    foreach ($aliases as $role => $route) {
        if ($user->hasRole($role) || $user->hasRole(ucwords($role))) {
            return redirect()->route($route);
        }
    }

    // Fallback: if user has roles, send supervisors/teachers to appropriate dashboard
    if (is_supervisor() && !$user->hasAnyRole(['Admin', 'Super Admin'])) {
        return redirect()->route('supervisor.dashboard');
    }
    if ($user->hasAnyRole(['Teacher','teacher'])) {
        return redirect()->route('teacher.dashboard');
    }

    abort(403, 'No dashboard defined for your role.');
})->middleware('auth')->name('home');

/*
|--------------------------------------------------------------------------
| Dashboards (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/home',    [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/teacher/home',  [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/supervisor/home',  [DashboardController::class, 'supervisorDashboard'])->name('supervisor.dashboard');
    Route::get('/parent/home',   [DashboardController::class, 'parentDashboard'])->name('parent.dashboard');
    Route::get('/student/home',  [DashboardController::class, 'studentDashboard'])->name('student.dashboard');
    Route::get('/finance/home',  [DashboardController::class, 'financeDashboard'])->name('finance.dashboard');
    Route::get('/transport/home',[DashboardController::class, 'transportDashboard'])->name('transport.dashboard');
});

/*
|--------------------------------------------------------------------------
| Authenticated Modules
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

     /*
    |----------------------------------------------------------------------
    | Attendance  (teachers can access)
    |----------------------------------------------------------------------
    */
    Route::prefix('attendance')
        ->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')
        ->group(function () {
            Route::get('/mark',          [AttendanceController::class, 'markForm'])->name('attendance.mark.form');
            Route::post('/mark',         [AttendanceController::class, 'mark'])->name('attendance.mark');
            Route::get('/records',       [AttendanceController::class, 'records'])->name('attendance.records');
            Route::get('/at-risk',       [AttendanceController::class, 'atRiskStudents'])->name('attendance.at-risk');
            Route::get('/consecutive',   [AttendanceController::class, 'consecutiveAbsences'])->name('attendance.consecutive');
            Route::get('/students/{student}/analytics', [AttendanceController::class, 'studentAnalytics'])->name('attendance.student-analytics');
            Route::post('/update-consecutive', [AttendanceController::class, 'updateConsecutiveCounts'])->name('attendance.update-consecutive');
            Route::post('/notify-consecutive', [AttendanceController::class, 'notifyConsecutiveAbsences'])->name('attendance.notify-consecutive');
            Route::get('/edit/{id}',     [AttendanceController::class, 'edit'])->name('attendance.edit');
            Route::post('/update/{id}',  [AttendanceController::class, 'update'])->name('attendance.update');
        });

    // Attendance notifications (admin only)
    Route::prefix('attendance/notifications')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/',            [AttendanceNotificationController::class, 'index'])->name('attendance.notifications.index');
            Route::get('/create',      [AttendanceNotificationController::class, 'create'])->name('attendance.notifications.create');
            Route::post('/',           [AttendanceNotificationController::class, 'store'])->name('attendance.notifications.store');
            Route::get('/{id}/edit',   [AttendanceNotificationController::class, 'edit'])->name('attendance.notifications.edit');
            Route::put('/{id}',        [AttendanceNotificationController::class, 'update'])->name('attendance.notifications.update');
            Route::delete('/{id}',     [AttendanceNotificationController::class, 'destroy'])->name('attendance.notifications.destroy');

            Route::get('/notify',      [AttendanceNotificationController::class, 'notifyForm'])->name('attendance.notifications.notify.form');
            Route::post('/notify',     [AttendanceNotificationController::class, 'sendNotify'])->name('attendance.notifications.notify.send');
        });

    // Attendance reason codes (admin only)
    Route::prefix('attendance/reason-codes')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/',            [AttendanceReasonCodeController::class, 'index'])->name('attendance.reason-codes.index');
            Route::get('/create',      [AttendanceReasonCodeController::class, 'create'])->name('attendance.reason-codes.create');
            Route::post('/',           [AttendanceReasonCodeController::class, 'store'])->name('attendance.reason-codes.store');
            Route::get('/{reasonCode}/edit', [AttendanceReasonCodeController::class, 'edit'])->name('attendance.reason-codes.edit');
            Route::put('/{reasonCode}', [AttendanceReasonCodeController::class, 'update'])->name('attendance.reason-codes.update');
            Route::delete('/{reasonCode}', [AttendanceReasonCodeController::class, 'destroy'])->name('attendance.reason-codes.destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Academics (teachers included)
    |----------------------------------------------------------------------
    */
    Route::prefix('academics')->as('academics.')
        ->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')
        ->group(function () {

        // Core setup
        Route::resource('classrooms',      ClassroomController::class)->except(['show']);
        Route::resource('streams',         StreamController::class)->except(['show']);
        Route::post('streams/{id}/assign-teachers', [StreamController::class, 'assignTeachers'])->name('streams.assign-teachers');
        Route::resource('subject_groups',  SubjectGroupController::class)->except(['show']);
        Route::get('subjects/teacher-assignments', [SubjectController::class, 'teacherAssignments'])->name('subjects.teacher-assignments');
        Route::post('subjects/teacher-assignments', [SubjectController::class, 'saveTeacherAssignments'])->name('subjects.teacher-assignments.save');
        Route::resource('subjects',        SubjectController::class);
        Route::post('subjects/generate-cbc', [SubjectController::class, 'generateCBCSubjects'])->name('subjects.generate-cbc');
        Route::post('subjects/assign-classrooms', [SubjectController::class, 'assignToClassrooms'])->name('subjects.assign-classrooms');
        
        // Teacher Assignments
        Route::get('assign-teachers', [\App\Http\Controllers\Academics\AssignTeachersController::class, 'index'])->name('assign-teachers');
        Route::post('classrooms/{id}/assign-teachers', [\App\Http\Controllers\Academics\AssignTeachersController::class, 'assignToClassroom'])->name('classrooms.assign-teachers');
        
        // Student Promotions
        Route::get('promotions', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'index'])->name('promotions.index');
        Route::get('promotions/alumni', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'alumni'])->name('promotions.alumni');
        Route::get('promotions/{classroom}', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'show'])->name('promotions.show');
        Route::post('promotions/{classroom}/promote', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'promote'])->name('promotions.promote');
        Route::post('promotions/{classroom}/promote-all', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'promoteAll'])->name('promotions.promote-all');
        Route::post('promotions/students/{student}/demote', [\App\Http\Controllers\Academics\StudentPromotionController::class, 'demote'])->name('promotions.demote');

        // Exams + lookups
        Route::get('exams/results',      [ExamResultController::class, 'index'])->name('exams.results.index');
        Route::post('exams/publish/{exam}', [ExamPublishingController::class, 'publish'])->name('exams.publish');
        Route::get('exams/timetable', [ExamController::class, 'timetable'])->name('exams.timetable');
        Route::resource('exam-grades', ExamGradeController::class);
        Route::resource('exams', ExamController::class);

        // Exam schedules
        Route::get('exams/{exam}/schedules',                   [ExamScheduleController::class, 'index'])->name('exams.schedules.index');
        Route::post('exams/{exam}/schedules',                  [ExamScheduleController::class, 'store'])->name('exams.schedules.store');
        Route::patch('exams/{exam}/schedules/{examSchedule}',  [ExamScheduleController::class, 'update'])->name('exams.schedules.update');
        Route::delete('exams/{exam}/schedules/{examSchedule}', [ExamScheduleController::class, 'destroy'])->name('exams.schedules.destroy');

        // Exam marks routes are defined in routes/teacher.php with proper permissions

        // Schemes of Work
        Route::resource('schemes-of-work', SchemeOfWorkController::class)->parameters(['schemes-of-work' => 'schemes_of_work']);
        Route::post('schemes-of-work/{schemes_of_work}/approve', [SchemeOfWorkController::class, 'approve'])->name('schemes-of-work.approve');
        Route::post('schemes-of-work/generate', [SchemeOfWorkController::class, 'generate'])->name('schemes-of-work.generate');
        Route::get('schemes-of-work/{schemes_of_work}/export-pdf', [SchemeOfWorkController::class, 'exportPdf'])->name('schemes-of-work.export-pdf');
        Route::get('schemes-of-work/{schemes_of_work}/export-excel', [SchemeOfWorkController::class, 'exportExcel'])->name('schemes-of-work.export-excel');
        Route::post('schemes-of-work/bulk-export', [SchemeOfWorkController::class, 'bulkExport'])->name('schemes-of-work.bulk-export');

        // Lesson Plans
        Route::resource('lesson-plans', LessonPlanController::class)->parameters(['lesson-plans' => 'lesson_plan']);
        Route::post('lesson-plans/{lesson_plan}/approve', [LessonPlanController::class, 'approve'])->name('lesson-plans.approve');
        Route::get('lesson-plans/{lesson_plan}/export-pdf', [LessonPlanController::class, 'exportPdf'])->name('lesson-plans.export-pdf');
        Route::get('lesson-plans/{lesson_plan}/export-excel', [LessonPlanController::class, 'exportExcel'])->name('lesson-plans.export-excel');
        Route::get('lesson-plans/{lesson_plan}/assign-homework', [LessonPlanController::class, 'assignHomeworkForm'])->name('lesson-plans.assign-homework');
        Route::post('lesson-plans/{lesson_plan}/assign-homework', [LessonPlanController::class, 'assignHomework'])->name('lesson-plans.assign-homework.store');

        // Curriculum Designs
        Route::resource('curriculum-designs', CurriculumDesignController::class)->parameters(['curriculum-designs' => 'curriculum_design']);
        Route::get('curriculum-designs/{curriculum_design}/review', [CurriculumDesignController::class, 'review'])->name('curriculum-designs.review');
        Route::post('curriculum-designs/{curriculum_design}/reprocess', [CurriculumDesignController::class, 'reprocess'])->name('curriculum-designs.reprocess');
        Route::get('curriculum-designs/{curriculum_design}/progress', [CurriculumDesignController::class, 'progress'])->name('curriculum-designs.progress');

        // Curriculum AI Assistant
        Route::prefix('curriculum-assistant')->name('curriculum-assistant.')->group(function () {
            Route::post('generate', [CurriculumAssistantController::class, 'generate'])->name('generate');
            Route::post('chat', [CurriculumAssistantController::class, 'chat'])->name('chat');
        });

        // Homework Diary
        Route::resource('homework-diary', HomeworkDiaryController::class)->parameters(['homework-diary' => 'homework_diary']);
        Route::get('homework-diary/{homework_diary}/submit', [HomeworkDiaryController::class, 'submitForm'])->name('homework-diary.submit');
        Route::post('homework-diary/{homework_diary}/submit', [HomeworkDiaryController::class, 'submit'])->name('homework-diary.submit.store');
        Route::get('homework-diary/{homework_diary}/mark', [HomeworkDiaryController::class, 'markForm'])->name('homework-diary.mark');
        Route::post('homework-diary/{homework_diary}/mark', [HomeworkDiaryController::class, 'mark'])->name('homework-diary.mark.store');
        Route::put('homework-diary/{homework_diary}/submission', [HomeworkDiaryController::class, 'updateSubmission'])->name('homework-diary.update-submission');

        // Learning Areas (Admin/Teacher can view, Admin only can manage)
        Route::resource('learning-areas', LearningAreaController::class)->parameters(['learning-areas' => 'learning_area']);
        Route::get('learning-areas/{learning_area}/strands', [LearningAreaController::class, 'getStrands'])->name('learning-areas.strands');

        // Competencies
        Route::resource('competencies', CompetencyController::class)->parameters(['competencies' => 'competency']);
        Route::get('competencies/by-substrand', [CompetencyController::class, 'getBySubstrand'])->name('competencies.by-substrand');
        Route::get('competencies/by-strand', [CompetencyController::class, 'getByStrand'])->name('competencies.by-strand');

        // CBC Strands (Admin only)
        Route::middleware('role:Super Admin|Admin')->group(function() {
            Route::resource('cbc-strands', CBCStrandController::class)->parameters(['cbc-strands' => 'cbc_strand']);
            Route::get('cbc-strands/{cbc_strand}/substrands', [CBCStrandController::class, 'substrands'])->name('cbc-strands.substrands');
            Route::resource('cbc-substrands', CBCSubstrandController::class)->parameters(['cbc-substrands' => 'cbc_substrand']);
        });

        // AJAX endpoints for dynamic filtering
        Route::get('schemes-of-work/get-strands', [SchemeOfWorkController::class, 'getStrands'])->name('schemes-of-work.get-strands');
        Route::get('lesson-plans/get-substrands', [LessonPlanController::class, 'getSubstrands'])->name('lesson-plans.get-substrands');

        // Portfolio Assessments
        Route::resource('portfolio-assessments', PortfolioAssessmentController::class)->parameters(['portfolio-assessments' => 'portfolio_assessment']);

        // Timetable
        Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index');
        Route::get('timetable/classroom/{classroom}', [TimetableController::class, 'classroom'])->name('timetable.classroom');
        Route::get('timetable/classroom/{classroom}/edit', [TimetableController::class, 'edit'])->name('timetable.edit');
        Route::get('timetable/teacher/{teacher}', [TimetableController::class, 'teacher'])->name('timetable.teacher');
        Route::post('timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');
        Route::post('timetable/save', [TimetableController::class, 'save'])->name('timetable.save');
        Route::post('timetable/duplicate', [TimetableController::class, 'duplicate'])->name('timetable.duplicate');
        Route::put('timetable/{timetable}/period', [TimetableController::class, 'updatePeriod'])->name('timetable.period.update');
        Route::post('timetable/check-conflicts', [TimetableController::class, 'checkConflicts'])->name('timetable.check-conflicts');

        // Extra-Curricular Activities / Activities
        Route::resource('extra-curricular-activities', AcademicsExtraCurricularActivityController::class)->parameters(['extra-curricular-activities' => 'extra_curricular_activity']);
        Route::post('extra-curricular-activities/{extra_curricular_activity}/assign-students', [AcademicsExtraCurricularActivityController::class, 'assignStudents'])->name('extra-curricular-activities.assign-students');
        
        // Alias route for "activities"
        Route::get('activities', [AcademicsExtraCurricularActivityController::class, 'index'])->name('activities.index');
        Route::get('activities/create', [AcademicsExtraCurricularActivityController::class, 'create'])->name('activities.create');
        Route::post('activities', [AcademicsExtraCurricularActivityController::class, 'store'])->name('activities.store');
        Route::get('activities/{extra_curricular_activity}', [AcademicsExtraCurricularActivityController::class, 'show'])->name('activities.show');
        Route::get('activities/{extra_curricular_activity}/edit', [AcademicsExtraCurricularActivityController::class, 'edit'])->name('activities.edit');
        Route::put('activities/{extra_curricular_activity}', [AcademicsExtraCurricularActivityController::class, 'update'])->name('activities.update');
        Route::delete('activities/{extra_curricular_activity}', [AcademicsExtraCurricularActivityController::class, 'destroy'])->name('activities.destroy');

        // Classroom Subject Lessons per Week
        Route::put('classroom-subjects/{classroomSubject}/update-lessons', [SubjectController::class, 'updateLessonsPerWeek'])->name('classroom-subjects.update-lessons');

        // Homework & Diaries
        Route::resource('homework', HomeworkController::class);
        Route::prefix('diaries')->name('diaries.')->group(function () {
            Route::get('/', [StudentDiaryController::class, 'index'])->name('index');
            Route::get('/{diary}', [StudentDiaryController::class, 'show'])->name('show');
            Route::post('/{diary}/entries', [StudentDiaryController::class, 'storeEntry'])->name('entries.store');
            Route::post('/entries/bulk', [StudentDiaryController::class, 'bulkStore'])->name('entries.bulk-store');
        });

        Route::middleware('role:Parent|parent')->prefix('parent/diaries')->name('parent.diaries.')->group(function () {
            Route::get('/', [ParentDiaryController::class, 'index'])->name('index');
            Route::get('/{student}', [ParentDiaryController::class, 'show'])->name('show');
            Route::post('/{student}/entries', [ParentDiaryController::class, 'storeEntry'])->name('entries.store');
        });

        // Term Assessment
        Route::get('assessments/term', [ReportCardController::class,'termAssessment'])->name('assessments.term');

        // Generate batch reports
        Route::get('report_cards/generate',  [ReportCardController::class,'generateForm'])->name('report_cards.generate.form');
        Route::post('report_cards/generate', [ReportCardController::class,'generate'])->name('report_cards.generate');

        // Report Cards
        Route::resource('report_cards', ReportCardController::class)
            ->names('report_cards')
            ->parameters(['report_cards' => 'report_card']);

        Route::delete('report_cards/{report_card}', [ReportCardController::class,'destroy'])->name('report_cards.destroy');
        Route::post('report_cards/{report}/publish', [ReportCardController::class,'publish'])->name('report_cards.publish');
        Route::get('report_cards/{report}/pdf',      [ReportCardController::class,'exportPdf'])->name('report_cards.pdf');
        Route::get('r/{token}',                      [ReportCardController::class,'publicView'])->name('report_cards.public');

        // Report Card Skills (per report)
        Route::prefix('report_cards/{report_card}')->name('report_cards.skills.')->group(function () {
            Route::get('skills',              [ReportCardSkillController::class,'index'])->name('index');
            Route::get('skills/create',       [ReportCardSkillController::class,'create'])->name('create');
            Route::post('skills',             [ReportCardSkillController::class,'store'])->name('store');
            Route::get('skills/{skill}/edit', [ReportCardSkillController::class,'edit'])->name('edit');
            Route::put('skills/{skill}',      [ReportCardSkillController::class,'update'])->name('update');
            Route::delete('skills/{skill}',   [ReportCardSkillController::class,'destroy'])->name('destroy');
        });

        // Behaviour
        Route::resource('behaviours', BehaviourController::class);
        Route::resource('student-behaviours', StudentBehaviourController::class);

        // Student skills grading
        Route::get('skills/grade',  [StudentSkillGradeController::class,'index'])->name('skills.grade.index');
        Route::post('skills/grade', [StudentSkillGradeController::class,'store'])->name('skills.grade.store');
    });

    // Exams: types
    Route::middleware(['auth', 'role:Super Admin|Admin|Secretary|Teacher|teacher'])
        ->prefix('academics')
        ->as('academics.')
        ->group(function () {
            Route::resource('exam-types',  ExamTypeController::class)
                ->names('exams.types')->only(['index','store','update','destroy']);
        });

    /*
    |----------------------------------------------------------------------
    | Transport
    |----------------------------------------------------------------------
    */
    Route::prefix('transport')->name('transport.')
        ->middleware('role:Super Admin|Admin|Secretary|Driver')
        ->group(function () {

            Route::get('/', [TransportController::class, 'index'])->name('index');

            // Actions
            Route::post('/assign-driver',                       [TransportController::class, 'assignDriver'])->name('assign.driver');

            // Resources
            Route::resource('vehicles',VehicleController::class)->except(['show']);
            Route::resource('trips',   TripController::class);
            Route::resource('dropoffpoints', DropOffPointController::class);
            Route::resource('student-assignments', StudentAssignmentController::class)
                ->parameters(['student-assignments' => 'student_assignment']);
            Route::get('student-assignments/bulk/assign', [StudentAssignmentController::class, 'bulkAssign'])->name('student-assignments.bulk-assign');
            Route::post('student-assignments/bulk/assign', [StudentAssignmentController::class, 'bulkAssignStore'])->name('student-assignments.bulk-assign.store');

            // Import & Template for dropoff points
            Route::get('dropoffpoints/import',   [DropOffPointController::class, 'importForm'])->name('dropoffpoints.import.form');
            Route::post('dropoffpoints/import',  [DropOffPointController::class, 'import'])->name('dropoffpoints.import');
            Route::get('dropoffpoints/template', [DropOffPointController::class, 'template'])->name('dropoffpoints.template');

            // Trip Attendance
            Route::get('trips/{trip}/attendance', [\App\Http\Controllers\Transport\TripAttendanceController::class, 'create'])->name('trip-attendance.create');
            Route::post('trips/{trip}/attendance', [\App\Http\Controllers\Transport\TripAttendanceController::class, 'store'])->name('trip-attendance.store');
            Route::get('trips/{trip}/attendance/history', [\App\Http\Controllers\Transport\TripAttendanceController::class, 'index'])->name('trip-attendance.index');

            // Driver Change Requests
            Route::resource('driver-change-requests', \App\Http\Controllers\Transport\DriverChangeRequestController::class)
                ->parameters(['driver-change-requests' => 'driverChangeRequest']);
            Route::post('driver-change-requests/{driverChangeRequest}/approve', [\App\Http\Controllers\Transport\DriverChangeRequestController::class, 'approve'])->name('driver-change-requests.approve');
            Route::post('driver-change-requests/{driverChangeRequest}/reject', [\App\Http\Controllers\Transport\DriverChangeRequestController::class, 'reject'])->name('driver-change-requests.reject');

            // Special Assignments
            Route::resource('special-assignments', \App\Http\Controllers\Transport\TransportSpecialAssignmentController::class)
                ->parameters(['special-assignments' => 'transportSpecialAssignment']);
            Route::post('special-assignments/{transportSpecialAssignment}/approve', [\App\Http\Controllers\Transport\TransportSpecialAssignmentController::class, 'approve'])->name('special-assignments.approve');
            Route::post('special-assignments/{transportSpecialAssignment}/reject', [\App\Http\Controllers\Transport\TransportSpecialAssignmentController::class, 'reject'])->name('special-assignments.reject');
            Route::post('special-assignments/{transportSpecialAssignment}/cancel', [\App\Http\Controllers\Transport\TransportSpecialAssignmentController::class, 'cancel'])->name('special-assignments.cancel');
        });

        // Driver-specific routes
        Route::prefix('driver')->name('driver.')
            ->middleware('role:Driver')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Driver\DriverController::class, 'index'])->name('index');
                Route::get('trips/{trip}', [\App\Http\Controllers\Driver\DriverController::class, 'showTrip'])->name('trips.show');
                Route::get('transport-sheet', [\App\Http\Controllers\Driver\DriverController::class, 'transportSheet'])->name('transport-sheet');
                Route::get('transport-sheet/trip/{trip}', [\App\Http\Controllers\Driver\DriverController::class, 'transportSheet'])->name('transport-sheet.trip');
            });

    /*
    |----------------------------------------------------------------------
    | Staff / HR
    |----------------------------------------------------------------------
    */
    Route::prefix('staff')->name('staff.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            // CRUD - Index and Create (must come before {id})
            Route::get('/',          [StaffController::class, 'index'])->name('index');
            Route::get('/create',    [StaffController::class, 'create'])->name('create');
            Route::post('/',         [StaffController::class, 'store'])->name('store');

            // Bulk Upload (new two-step + legacy) + Template (must come before {id})
            Route::get('/upload',         [StaffController::class, 'showUploadForm'])->name('upload.form');
            Route::post('/upload/parse',  [StaffController::class, 'uploadParse'])->name('upload.parse');   // preview
            Route::post('/upload/commit', [StaffController::class, 'uploadCommit'])->name('upload.commit'); // finalize
            Route::post('/upload',        [StaffController::class, 'handleUpload'])->name('upload.handle'); // legacy
            Route::get('/template',       [StaffController::class, 'template'])->name('template');

            // Leave Management (must come before {id})
            Route::prefix('leave-types')->name('leave-types.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'store'])->name('store');
                Route::get('/{leaveType}/edit', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'edit'])->name('edit');
                Route::put('/{leaveType}', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'update'])->name('update');
                Route::delete('/{leaveType}', [\App\Http\Controllers\Hr\LeaveTypeController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('leave-requests')->name('leave-requests.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'store'])->name('store');
                Route::get('/{leaveRequest}', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'show'])->name('show');
                Route::post('/{leaveRequest}/approve', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'approve'])->name('approve');
                Route::post('/{leaveRequest}/reject', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'reject'])->name('reject');
                Route::post('/{leaveRequest}/cancel', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('leave-balances')->name('leave-balances.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\StaffLeaveBalanceController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Hr\StaffLeaveBalanceController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Hr\StaffLeaveBalanceController::class, 'store'])->name('store');
                Route::get('/{staff}', [\App\Http\Controllers\Hr\StaffLeaveBalanceController::class, 'show'])->name('show');
                Route::put('/{balance}', [\App\Http\Controllers\Hr\StaffLeaveBalanceController::class, 'update'])->name('update');
            });

            // Staff Attendance (must come before {id})
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\StaffAttendanceController::class, 'index'])->name('index');
                Route::post('/bulk-mark', [\App\Http\Controllers\Hr\StaffAttendanceController::class, 'bulkMark'])->name('bulk-mark');
                Route::get('/report', [\App\Http\Controllers\Hr\StaffAttendanceController::class, 'report'])->name('report');
            });

            // Staff Document Management (must come before {id})
            Route::prefix('documents')->name('documents.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'store'])->name('store');
                Route::get('/{document}', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'show'])->name('show');
                Route::get('/{document}/download', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'download'])->name('download');
                Route::delete('/{document}', [\App\Http\Controllers\Hr\StaffDocumentController::class, 'destroy'])->name('destroy');
            });

            // CRUD - Individual staff routes (must come AFTER all specific routes)
            Route::get('/{id}',      [StaffController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{id}',      [StaffController::class, 'update'])->name('update');

            // Archive / Restore
            Route::patch('/{id}/archive', [StaffController::class, 'archive'])->name('archive');
            Route::patch('/{id}/restore', [StaffController::class, 'restore'])->name('restore');
            
            // Bulk supervisor assignment
            Route::post('/bulk-assign-supervisor', [StaffController::class, 'bulkAssignSupervisor'])->name('bulk-assign-supervisor');
            
            // Resend Login Credentials
            Route::post('/{id}/resend-credentials', [StaffController::class, 'resendCredentials'])->name('resend-credentials');
            
            // Reset Password
            Route::post('/{id}/reset-password', [StaffController::class, 'resetPassword'])->name('reset-password');
        });

    // HR Management - Roles & Lookups (moved from settings)
    Route::prefix('hr')->name('hr.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/', fn() => redirect()->route('staff.index'))->name('index');
            
            // Combined Roles & HR Lookups page
            Route::get('/access-lookups', [RolePermissionController::class, 'accessAndLookups'])->name('access-lookups');
            
            // Roles & Permissions
            Route::get('/roles',         [RolePermissionController::class, 'listRoles'])->name('roles.index');
            Route::get('/roles/{role}',  [RolePermissionController::class, 'index'])->name('roles.edit');
            Route::post('/roles/{role}/permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');

            // HR Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Hr\StaffReportController::class, 'index'])->name('index');
                Route::get('/directory', [\App\Http\Controllers\Hr\StaffReportController::class, 'exportDirectory'])->name('directory');
                Route::get('/department', [\App\Http\Controllers\Hr\StaffReportController::class, 'departmentReport'])->name('department');
                Route::get('/category', [\App\Http\Controllers\Hr\StaffReportController::class, 'categoryReport'])->name('category');
                Route::get('/new-hires', [\App\Http\Controllers\Hr\StaffReportController::class, 'newHiresReport'])->name('new-hires');
                Route::get('/terminations', [\App\Http\Controllers\Hr\StaffReportController::class, 'terminationsReport'])->name('terminations');
                Route::get('/turnover', [\App\Http\Controllers\Hr\StaffReportController::class, 'turnoverAnalysis'])->name('turnover');
            });

            // HR Analytics Dashboard
            Route::get('/analytics', [\App\Http\Controllers\Hr\HRAnalyticsController::class, 'index'])->name('analytics.index');

            // Payroll Management
            Route::prefix('payroll')->name('payroll.')->group(function () {
                // Salary Structures
                Route::resource('salary-structures', \App\Http\Controllers\Hr\SalaryStructureController::class);
                
                // Payroll Periods
                Route::resource('periods', \App\Http\Controllers\Hr\PayrollPeriodController::class);
                Route::post('/periods/{id}/process', [\App\Http\Controllers\Hr\PayrollPeriodController::class, 'process'])->name('periods.process');
                Route::post('/periods/{id}/lock', [\App\Http\Controllers\Hr\PayrollPeriodController::class, 'lock'])->name('periods.lock');
                
                // Payroll Records
                Route::resource('records', \App\Http\Controllers\Hr\PayrollRecordController::class);
                Route::get('/records/{id}/payslip', [\App\Http\Controllers\Hr\PayslipController::class, 'show'])->name('records.payslip');
                Route::get('/records/{id}/payslip/download', [\App\Http\Controllers\Hr\PayslipController::class, 'download'])->name('records.payslip.download');
                
                // Staff Advances
                Route::resource('advances', \App\Http\Controllers\Hr\StaffAdvanceController::class);
                Route::post('/advances/{id}/approve', [\App\Http\Controllers\Hr\StaffAdvanceController::class, 'approve'])->name('advances.approve');
                Route::post('/advances/{id}/repayment', [\App\Http\Controllers\Hr\StaffAdvanceController::class, 'recordRepayment'])->name('advances.repayment');
                
                // Deduction Types
                Route::resource('deduction-types', \App\Http\Controllers\Hr\DeductionTypeController::class);
                
                // Custom Deductions
                Route::resource('custom-deductions', \App\Http\Controllers\Hr\CustomDeductionController::class);
                Route::post('/custom-deductions/{id}/suspend', [\App\Http\Controllers\Hr\CustomDeductionController::class, 'suspend'])->name('custom-deductions.suspend');
                Route::post('/custom-deductions/{id}/activate', [\App\Http\Controllers\Hr\CustomDeductionController::class, 'activate'])->name('custom-deductions.activate');
            });
        });

    // Supervisor routes (for supervisors to access their subordinates' data)
    Route::prefix('supervisor')->name('supervisor.')
        ->middleware('auth')
        ->group(function () {
            // Leave Requests (supervisors can approve their subordinates' leaves)
            Route::get('/leave-requests', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'index'])->name('leave-requests.index');
            Route::get('/leave-requests/{leaveRequest}', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'show'])->name('leave-requests.show');
            Route::post('/leave-requests/{leaveRequest}/approve', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
            Route::post('/leave-requests/{leaveRequest}/reject', [\App\Http\Controllers\Hr\LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
            
            // Staff Attendance (supervisors can view their subordinates' attendance)
            Route::get('/attendance', [\App\Http\Controllers\Hr\StaffAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/attendance/report', [\App\Http\Controllers\Hr\StaffAttendanceController::class, 'report'])->name('attendance.report');
        });

    // HR Lookups standalone page (optional UI outside settings tab)
    Route::get('/lookups', [LookupController::class, 'index'])
        ->middleware('role:Super Admin|Admin|Secretary')
        ->name('lookups.index');

    // Lookups AJAX Endpoints
    Route::prefix('lookups')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::post('/category',         [LookupController::class, 'storeCategory'])->name('lookups.category.store');
            Route::delete('/category/{id}',  [LookupController::class, 'deleteCategory'])->name('lookups.category.delete');

            Route::post('/department',        [LookupController::class, 'storeDepartment'])->name('lookups.department.store');
            Route::delete('/department/{id}', [LookupController::class, 'deleteDepartment'])->name('lookups.department.delete');

            Route::post('/job-title',        [LookupController::class, 'storeJobTitle'])->name('lookups.jobtitle.store');
            Route::delete('/job-title/{id}', [LookupController::class, 'deleteJobTitle'])->name('lookups.jobtitle.delete');

            Route::post('/custom-field',     [LookupController::class, 'storeCustomField'])->name('lookups.customfield.store');
            Route::delete('/custom-field/{id}', [LookupController::class, 'deleteCustomField'])->name('lookups.customfield.delete');
        });
    // ============ HR: Staff Profile Changes (Admin review) ============
    Route::middleware(['auth','role:Super Admin|Admin'])
        ->prefix('hr/profile-requests')
        ->name('hr.profile_requests.')
        ->group(function () {
            Route::get('/',            [\App\Http\Controllers\Hr\ProfileChangeController::class, 'index'])->name('index');
            Route::post('/approve-all', [\App\Http\Controllers\Hr\ProfileChangeController::class, 'approveAll'])->name('approve-all');
            Route::get('/{change}',    [\App\Http\Controllers\Hr\ProfileChangeController::class, 'show'])->name('show');
            Route::post('/{change}/approve', [\App\Http\Controllers\Hr\ProfileChangeController::class, 'approve'])->name('approve');
            Route::post('/{change}/reject',  [\App\Http\Controllers\Hr\ProfileChangeController::class, 'reject'])->name('reject');
        });

    /*
    |----------------------------------------------------------------------
    | Settings (includes Roles & HR lookups tab)
    |----------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');

            // Branding / General / Regional / System / IDs
            Route::post('/update-branding', [SettingController::class, 'updateBranding'])->name('update.branding');
            Route::post('/update-general',  [SettingController::class, 'updateSettings'])->name('update.general');
            Route::post('/update-regional', [SettingController::class, 'updateRegional'])->name('update.regional');
            Route::post('/update-system',   [SettingController::class, 'updateSystem'])->name('update.system');
            Route::post('/id-settings',     [SettingController::class, 'updateIdSettings'])->name('ids.save');

            // Modules update
            Route::post('/update-features', [SettingController::class, 'updateFeatures'])->name('update.features');
            Route::post('/update-modules',  [SettingController::class, 'updateModules'])->name('update.modules');
        });

    // Academic Config (Years, Terms)
    Route::prefix('settings')->name('settings.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        // School Days Management
        Route::prefix('school-days')->name('school-days.')->group(function () {
            Route::get('/', [SchoolDayController::class, 'index'])->name('index');
            Route::post('/generate-holidays', [SchoolDayController::class, 'generateHolidays'])->name('generate-holidays');
            Route::post('/', [SchoolDayController::class, 'store'])->name('store');
            Route::delete('/{schoolDay}', [SchoolDayController::class, 'destroy'])->name('destroy');
        });

        Route::get('academic', [AcademicConfigController::class, 'index'])->name('academic.index');

        // Year
        Route::get('academic/year/create',        [AcademicConfigController::class, 'createYear'])->name('academic.year.create');
        Route::post('academic/year',              [AcademicConfigController::class, 'storeYear'])->name('academic.year.store');
        Route::get('academic/year/{year}/edit',   [AcademicConfigController::class, 'editYear'])->name('academic.year.edit');
        Route::put('academic/year/{year}',        [AcademicConfigController::class, 'updateYear'])->name('academic.year.update');
        Route::delete('academic/year/{year}',     [AcademicConfigController::class, 'destroyYear'])->name('academic.year.destroy');

        // Term
        Route::get('academic/term/create',        [AcademicConfigController::class, 'createTerm'])->name('academic.term.create');
        Route::post('academic/term',              [AcademicConfigController::class, 'storeTerm'])->name('academic.term.store');
        Route::get('academic/term/{term}/edit',   [AcademicConfigController::class, 'editTerm'])->name('academic.term.edit');
        Route::put('academic/term/{term}',        [AcademicConfigController::class, 'updateTerm'])->name('academic.term.update');
        Route::delete('academic/term/{term}',     [AcademicConfigController::class, 'destroyTerm'])->name('academic.term.destroy');
        Route::get('academic/term-holidays',      [AcademicConfigController::class, 'termHolidays'])->name('academic.term-holidays');
        Route::post('academic/term-holidays',     [AcademicConfigController::class, 'storeTermHoliday'])->name('academic.term-holidays.store');
        Route::put('academic/term-holidays/{schoolDay}', [AcademicConfigController::class, 'updateTermHoliday'])->name('academic.term-holidays.update');
    });

    // Settings → Placeholders management
    Route::prefix('settings/placeholders')
        ->name('settings.placeholders.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/',                 [PlaceholderController::class, 'index'])->name('index');
            Route::get('/create',           [PlaceholderController::class, 'create'])->name('create');
            Route::post('/store',           [PlaceholderController::class, 'store'])->name('store');
            Route::get('/{placeholder}/edit',[PlaceholderController::class, 'edit'])->name('edit');
            Route::put('/{placeholder}',    [PlaceholderController::class, 'update'])->name('update');
            Route::delete('/{placeholder}', [PlaceholderController::class, 'destroy'])->name('destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Students
    |----------------------------------------------------------------------
    */
    // Specific routes must be defined BEFORE resource routes to avoid conflicts
    Route::get('/students/bulk-upload',   [StudentController::class, 'bulkForm'])->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk');
    Route::post('/students/bulk-parse',   [StudentController::class, 'bulkParse'])->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.parse');
    Route::post('/students/bulk-import',  [StudentController::class, 'bulkImport'])->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.import');
    Route::get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.template');
    
    // Bulk category assignment
    Route::get('/students/bulk-assign-categories', [StudentController::class, 'bulkAssignCategories'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.assign-categories');
    Route::post('/students/bulk-assign-categories', [StudentController::class, 'processBulkCategoryAssignment'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.assign-categories.process');

// Family update links (admin)
Route::get('/admin/family-update', [FamilyUpdateController::class, 'adminIndex'])
    ->middleware('role:Super Admin|Admin|Secretary')->name('family-update.admin.index');
Route::post('/admin/family-update/reset-all', [FamilyUpdateController::class, 'resetAll'])
    ->middleware('role:Super Admin|Admin|Secretary')->name('family-update.admin.reset-all');
Route::post('/families/{family}/update-link/reset', [FamilyUpdateController::class, 'reset'])
    ->middleware('role:Super Admin|Admin|Secretary')->name('families.update-link.reset');
Route::get('/families/{family}/update-link', [FamilyUpdateController::class, 'showLink'])
    ->middleware('role:Super Admin|Admin|Secretary')->name('families.update-link.show');

    // Bulk stream assignment - MUST be before resource route to avoid conflicts
    Route::get('/students/bulk-assign-streams', [StudentController::class, 'bulkAssignStreams'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.assign-streams');
    Route::post('/students/bulk-assign-streams', [StudentController::class, 'processBulkStreamAssignment'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.assign-streams.process');

    // Archived list must be defined before the resource route to avoid being captured by /students/{student}
    Route::get('/students/archived', [StudentController::class, 'archived'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.archived');

    Route::resource('students', StudentController::class)
        ->except(['destroy'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher');

    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.archive');

    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.restore');

    // Helper for cascading class → streams
    Route::post('/get-streams', [StudentController::class, 'getStreams'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')->name('students.getStreams');

    // API-like search (inside auth)
    Route::get('/api/students/search', [StudentController::class, 'search'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')->name('api.students.search');

    // Export filtered list
    Route::get('/students/export', [StudentController::class, 'export'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.export');

    // Bulk assign (class/stream) + bulk archive/restore
    Route::post('/students/bulk-assign', [StudentController::class, 'bulkAssign'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.assign');

    Route::post('/students/bulk-archive', [StudentController::class, 'bulkArchive'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.archive');

    Route::post('/students/bulk-restore', [StudentController::class, 'bulkRestore'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.restore');

    // Student Categories management
    Route::resource('student-categories', StudentCategoryController::class)
        ->middleware('role:Super Admin|Admin|Secretary');

    // Student Records (Medical, Disciplinary, Activities, Academic History)
    Route::prefix('students/{student}')->name('students.')->middleware('role:Super Admin|Admin|Secretary|Teacher')->group(function () {
        Route::get('medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
        Route::get('medical-records/create', [MedicalRecordController::class, 'create'])->name('medical-records.create');
        Route::post('medical-records', [MedicalRecordController::class, 'store'])->name('medical-records.store');
        Route::get('medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])->name('medical-records.show');
        Route::get('medical-records/{medicalRecord}/edit', [MedicalRecordController::class, 'edit'])->name('medical-records.edit');
        Route::put('medical-records/{medicalRecord}', [MedicalRecordController::class, 'update'])->name('medical-records.update');
        Route::delete('medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy'])->name('medical-records.destroy');

        Route::get('disciplinary-records', [DisciplinaryRecordController::class, 'index'])->name('disciplinary-records.index');
        Route::get('disciplinary-records/create', [DisciplinaryRecordController::class, 'create'])->name('disciplinary-records.create');
        Route::post('disciplinary-records', [DisciplinaryRecordController::class, 'store'])->name('disciplinary-records.store');
        Route::get('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'show'])->name('disciplinary-records.show');
        Route::get('disciplinary-records/{disciplinaryRecord}/edit', [DisciplinaryRecordController::class, 'edit'])->name('disciplinary-records.edit');
        Route::put('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'update'])->name('disciplinary-records.update');
        Route::delete('disciplinary-records/{disciplinaryRecord}', [DisciplinaryRecordController::class, 'destroy'])->name('disciplinary-records.destroy');

        Route::get('activities', [ExtracurricularActivityController::class, 'index'])->name('activities.index');
        Route::get('activities/create', [ExtracurricularActivityController::class, 'create'])->name('activities.create');
        Route::post('activities', [ExtracurricularActivityController::class, 'store'])->name('activities.store');
        Route::get('activities/{activity}', [ExtracurricularActivityController::class, 'show'])->name('activities.show');
        Route::get('activities/{activity}/edit', [ExtracurricularActivityController::class, 'edit'])->name('activities.edit');
        Route::put('activities/{activity}', [ExtracurricularActivityController::class, 'update'])->name('activities.update');
        Route::delete('activities/{activity}', [ExtracurricularActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('academic-history', [AcademicHistoryController::class, 'index'])->name('academic-history.index');
        Route::get('academic-history/create', [AcademicHistoryController::class, 'create'])->name('academic-history.create');
        Route::post('academic-history', [AcademicHistoryController::class, 'store'])->name('academic-history.store');
        Route::get('academic-history/{academicHistory}', [AcademicHistoryController::class, 'show'])->name('academic-history.show');
        Route::get('academic-history/{academicHistory}/edit', [AcademicHistoryController::class, 'edit'])->name('academic-history.edit');
        Route::put('academic-history/{academicHistory}', [AcademicHistoryController::class, 'update'])->name('academic-history.update');
        Route::delete('academic-history/{academicHistory}', [AcademicHistoryController::class, 'destroy'])->name('academic-history.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Parent Info
    |----------------------------------------------------------------------
    */
    Route::resource('parent-info', ParentInfoController::class)
        ->except(['show'])
        ->middleware('role:Super Admin|Admin|Secretary');
    
    //families
    Route::prefix('families')->middleware('role:Super Admin|Admin|Secretary|Teacher')->group(function () {
        Route::get('/',                [FamilyController::class,'index'])->name('families.index');
        Route::post('/populate-all',   [FamilyController::class,'populateAllFamilies'])->name('families.populate'); // fix existing families
        Route::get('/link',            [FamilyController::class,'link'])->name('families.link');              // show link form
        Route::post('/link-students',   [FamilyController::class,'linkStudents'])->name('families.link.store'); // link two students
        Route::get('/{family}',        [FamilyController::class,'manage'])->name('families.manage');         // view + edit page
        Route::put('/{family}',        [FamilyController::class,'update'])->name('families.update');         // save guardian/phone/email (optional)
        Route::post('/{family}/attach',[FamilyController::class,'attachMember'])->name('families.attach');   // add student to family
        Route::post('/{family}/detach',[FamilyController::class,'detachMember'])->name('families.detach');   // remove student from family
        Route::delete('/{family}',     [FamilyController::class,'destroy'])->name('families.destroy');       // delete family
    });

    /*
    |----------------------------------------------------------------------
    | Online Admissions
    |----------------------------------------------------------------------
    */
    Route::prefix('online-admissions')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        Route::get('/', [OnlineAdmissionController::class, 'index'])->name('online-admissions.index');
        Route::get('/{admission}', [OnlineAdmissionController::class, 'show'])
            ->whereNumber('admission')
            ->name('online-admissions.show');
        Route::post('/{admission}/approve', [OnlineAdmissionController::class, 'approve'])
            ->whereNumber('admission')
            ->name('online-admissions.approve');
        Route::post('/{admission}/reject', [OnlineAdmissionController::class, 'reject'])
            ->whereNumber('admission')
            ->name('online-admissions.reject');
        Route::post('/{admission}/waitlist', [OnlineAdmissionController::class, 'addToWaitlist'])
            ->whereNumber('admission')
            ->name('online-admissions.waitlist');
        Route::post('/{admission}/transfer', [OnlineAdmissionController::class, 'transferFromWaitlist'])
            ->whereNumber('admission')
            ->name('online-admissions.transfer');
        Route::put('/{admission}/status', [OnlineAdmissionController::class, 'updateStatus'])
            ->whereNumber('admission')
            ->name('online-admissions.update-status');
        Route::delete('/{admission}', [OnlineAdmissionController::class, 'destroy'])
            ->whereNumber('admission')
            ->name('online-admissions.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Communication
    |----------------------------------------------------------------------
    */
    Route::prefix('communication')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        // Senders
        Route::get('send-email', [CommunicationController::class, 'createEmail'])->name('communication.send.email');
        Route::post('send-email',[CommunicationController::class, 'sendEmail'])->name('communication.send.email.submit');

        Route::get('send-sms',   [CommunicationController::class, 'createSMS'])->name('communication.send.sms');
        Route::post('send-sms',  [CommunicationController::class, 'sendSMS'])->name('communication.send.sms.submit');
        Route::get('send-whatsapp', [CommunicationController::class, 'createWhatsApp'])->name('communication.send.whatsapp');
        Route::post('send-whatsapp', [CommunicationController::class, 'sendWhatsApp'])->name('communication.send.whatsapp.submit');
        Route::post('send-document', [CommunicationDocumentController::class, 'send'])->name('communication.send.document');
        Route::get('whatsapp-sessions', [WasenderSessionController::class, 'index'])->name('communication.wasender.sessions');
        Route::post('whatsapp-sessions', [WasenderSessionController::class, 'store'])->name('communication.wasender.sessions.store');
        Route::post('whatsapp-sessions/{id}/connect', [WasenderSessionController::class, 'connect'])->name('communication.wasender.sessions.connect');
        Route::post('whatsapp-sessions/{id}/restart', [WasenderSessionController::class, 'restart'])->name('communication.wasender.sessions.restart');
        Route::delete('whatsapp-sessions/{id}', [WasenderSessionController::class, 'destroy'])->name('communication.wasender.sessions.destroy');

        // Logs
        Route::get('logs',           [CommunicationController::class, 'logs'])->name('communication.logs');
        Route::get('logs/scheduled', [CommunicationController::class, 'logsScheduled'])->name('communication.logs.scheduled');

        // Announcements
        Route::resource('announcements', CommunicationAnnouncementController::class)->except(['show']);

        // Templates
        Route::resource('communication-templates', CommunicationTemplateController::class)
            ->parameters(['communication-templates' => 'communication_template'])
            ->except(['show']);

    });

    /*
    |----------------------------------------------------------------------
    | Finance
    |----------------------------------------------------------------------
    */
    Route::prefix('finance')->name('finance.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {

        // Voteheads
        Route::resource('voteheads', VoteheadController::class)->except(['show']);
        Route::get('voteheads/import', [VoteheadController::class, 'import'])->name('voteheads.import');
        Route::post('voteheads/import', [VoteheadController::class, 'processImport'])->name('voteheads.process-import');
        Route::get('voteheads/template/download', [VoteheadController::class, 'downloadTemplate'])->name('voteheads.download-template');

        // Fee Structures
        Route::get('fee-structures/manage',   [FeeStructureController::class, 'manage'])->name('fee-structures.manage');
        Route::post('fee-structures/manage',  [FeeStructureController::class, 'save'])->name('fee-structures.save');
        Route::post('fee-structures/replicate',[FeeStructureController::class, 'replicateTo'])->name('fee-structures.replicate');
        Route::post('fee-structures/replicate-terms',[FeeStructureController::class, 'replicateTerms'])->name('fee-structures.replicate-terms');
        Route::get('fee-structures/import', [FeeStructureController::class, 'import'])->name('fee-structures.import');
        Route::post('fee-structures/import', [FeeStructureController::class, 'processImport'])->name('fee-structures.process-import');
        Route::get('fee-structures/template/download', [FeeStructureController::class, 'downloadTemplate'])->name('fee-structures.download-template');

        // Legacy finance imports (PDF → staging)
        Route::get('legacy-imports', [LegacyFinanceImportController::class, 'index'])->name('legacy-imports.index');
        Route::post('legacy-imports', [LegacyFinanceImportController::class, 'store'])->name('legacy-imports.store');
        Route::get('legacy-imports/{batch}', [LegacyFinanceImportController::class, 'show'])->name('legacy-imports.show');
        Route::get('legacy-imports/{batch}/edit-history', [LegacyFinanceImportController::class, 'editHistory'])->name('legacy-imports.edit-history');
        Route::post('legacy-imports/edit-history/{editHistory}/revert', [LegacyFinanceImportController::class, 'revertEdit'])->name('legacy-imports.edit-history.revert');
        Route::get('legacy-imports/student-search', [LegacyFinanceImportController::class, 'searchStudent'])->name('legacy-imports.student-search');
        Route::post('legacy-imports/{batch}/rerun', [LegacyFinanceImportController::class, 'rerun'])->name('legacy-imports.rerun');
        Route::delete('legacy-imports/{batch}', [LegacyFinanceImportController::class, 'destroy'])->name('legacy-imports.destroy');
        Route::put('legacy-imports/lines/{line}', [LegacyFinanceImportController::class, 'updateLine'])->name('legacy-imports.lines.update');

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'index'])->name('index');
            Route::get('/create',         [InvoiceController::class, 'create'])->name('create');
            Route::post('/generate',      [InvoiceController::class, 'generate'])->name('generate');
            Route::get('/{invoice}',      [InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}',      [InvoiceController::class, 'update'])->name('update');
            Route::post('/reverse/{invoice}', [InvoiceController::class, 'reverse'])->name('reverse');

            // Print PDFs
            Route::get('/print',            [InvoiceController::class, 'printBulk'])->name('print');
            Route::get('/{invoice}/print',  [InvoiceController::class, 'printSingle'])->name('print_single');

            // Excel Import
            Route::get('/import', [InvoiceController::class, 'importForm'])->name('import.form');
            Route::post('/import',[InvoiceController::class, 'import'])->name('import');

            // Adjustments (Credit/Debit Notes via batch import)
            Route::get('/adjustments/import', [InvoiceAdjustmentController::class, 'importForm'])->name('adjustments.import.form');
            Route::post('/adjustments/import',[InvoiceAdjustmentController::class, 'import'])->name('adjustments.import');
        });

        // Optional Fees
        Route::prefix('optional-fees')->name('optional_fees.')->group(function () {
            Route::get('/',               [OptionalFeeController::class, 'index'])->name('index');
            Route::get('/class',          [OptionalFeeController::class, 'classView'])->name('class_view');
            Route::post('/class/save',    [OptionalFeeController::class, 'saveClassBilling'])->name('save_class');
            Route::get('/student',        [OptionalFeeController::class, 'studentView'])->name('student_view');
            Route::post('/student/save',  [OptionalFeeController::class, 'saveStudentBilling'])->name('save_student');
        });
        Route::post('optional-fees/import/preview', [\App\Http\Controllers\Finance\OptionalFeeImportController::class, 'importPreview'])->name('optional-fees.import.preview');
        Route::post('optional-fees/import/commit', [\App\Http\Controllers\Finance\OptionalFeeImportController::class, 'importCommit'])->name('optional-fees.import.commit');
        Route::post('optional-fees/import/{import}/reverse', [\App\Http\Controllers\Finance\OptionalFeeImportController::class, 'reverse'])->name('optional-fees.import.reverse');
        Route::get('optional-fees/import/template', [\App\Http\Controllers\Finance\OptionalFeeImportController::class, 'template'])->name('optional-fees.import.template');

        // Transport Fees
        Route::get('transport-fees', [TransportFeeController::class, 'index'])->name('transport-fees.index');
        Route::post('transport-fees/bulk-update', [TransportFeeController::class, 'bulkUpdate'])->name('transport-fees.bulk-update');
        Route::post('transport-fees/import/preview', [TransportFeeController::class, 'importPreview'])->name('transport-fees.import.preview');
        Route::post('transport-fees/import/commit', [TransportFeeController::class, 'importCommit'])->name('transport-fees.import.commit');
        Route::post('transport-fees/import/{import}/reverse', [TransportFeeController::class, 'reverseImport'])->name('transport-fees.import.reverse');
        Route::get('transport-fees/template', [TransportFeeController::class, 'template'])->name('transport-fees.template');

        // Payments
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments/store', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/allocate', [PaymentController::class, 'allocate'])->name('payments.allocate');
        Route::delete('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
        Route::post('payments/{payment}/transfer', [PaymentController::class, 'transfer'])->name('payments.transfer');
        Route::get('payments/receipt/{payment}', [PaymentController::class, 'printReceipt'])->name('payments.receipt');
        Route::get('payments/receipt/{payment}/view', [PaymentController::class, 'viewReceipt'])->name('payments.receipt.view');
        Route::get('payments/bulk-print', [PaymentController::class, 'bulkPrintReceipts'])->name('payments.bulk-print');
        Route::get('payments/student/{student}/info', [PaymentController::class, 'getStudentBalanceAndSiblings'])->name('payments.student-info');
        
        // Bank Accounts
        Route::resource('bank-accounts', BankAccountController::class)->parameters(['bank-accounts' => 'bankAccount']);
        
        // Payment Methods
        Route::resource('payment-methods', PaymentMethodController::class)->parameters(['payment-methods' => 'paymentMethod']);
        
        // Bank Statements
        Route::prefix('bank-statements')->name('bank-statements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\BankStatementController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Finance\BankStatementController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Finance\BankStatementController::class, 'store'])->name('store');
            Route::get('/{bankStatement}', [\App\Http\Controllers\Finance\BankStatementController::class, 'show'])->name('show');
            Route::get('/{bankStatement}/edit', [\App\Http\Controllers\Finance\BankStatementController::class, 'edit'])->name('edit');
            Route::put('/{bankStatement}', [\App\Http\Controllers\Finance\BankStatementController::class, 'update'])->name('update');
            Route::delete('/{bankStatement}', [\App\Http\Controllers\Finance\BankStatementController::class, 'destroy'])->name('destroy');
            Route::post('/{bankStatement}/confirm', [\App\Http\Controllers\Finance\BankStatementController::class, 'confirm'])->name('confirm');
            Route::post('/{bankStatement}/reject', [\App\Http\Controllers\Finance\BankStatementController::class, 'reject'])->name('reject');
            Route::post('/{bankStatement}/share', [\App\Http\Controllers\Finance\BankStatementController::class, 'share'])->name('share');
            Route::put('/{bankStatement}/update-allocations', [\App\Http\Controllers\Finance\BankStatementController::class, 'updateAllocations'])->name('update-allocations');
            Route::get('/{bankStatement}/view-pdf', [\App\Http\Controllers\Finance\BankStatementController::class, 'viewPdf'])->name('view-pdf');
            Route::get('/{bankStatement}/serve-pdf', [\App\Http\Controllers\Finance\BankStatementController::class, 'servePdf'])->name('serve-pdf');
            Route::get('/{bankStatement}/download-pdf', [\App\Http\Controllers\Finance\BankStatementController::class, 'downloadPdf'])->name('download-pdf');
            Route::post('/bulk-confirm', [\App\Http\Controllers\Finance\BankStatementController::class, 'bulkConfirm'])->name('bulk-confirm');
            Route::post('/auto-assign', [\App\Http\Controllers\Finance\BankStatementController::class, 'autoAssign'])->name('auto-assign');
            Route::post('/{bankStatement}/reparse', [\App\Http\Controllers\Finance\BankStatementController::class, 'reparse'])->name('reparse');
            Route::post('/{bankStatement}/archive', [\App\Http\Controllers\Finance\BankStatementController::class, 'archive'])->name('archive');
            Route::post('/{bankStatement}/unarchive', [\App\Http\Controllers\Finance\BankStatementController::class, 'unarchive'])->name('unarchive');
        });
        
        // Document Settings
        Route::get('document-settings', [DocumentSettingsController::class, 'index'])->name('document-settings.index');
        Route::post('document-settings', [DocumentSettingsController::class, 'update'])->name('document-settings.update');
        
        // Student Statements
        Route::get('student-statements', [StudentStatementController::class, 'index'])->name('student-statements.index');
        Route::get('student-statements/{student}', [StudentStatementController::class, 'show'])->name('student-statements.show');
        Route::get('student-statements/{student}/print', [StudentStatementController::class, 'print'])->name('student-statements.print');
        Route::get('student-statements/{student}/export', [StudentStatementController::class, 'export'])->name('student-statements.export');
        
        // Balance Brought Forward
        Route::get('balance-brought-forward', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'index'])->name('balance-brought-forward.index');
        Route::post('balance-brought-forward/import/preview', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'importPreview'])->name('balance-brought-forward.import.preview');
        Route::post('balance-brought-forward/import/commit', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'importCommit'])->name('balance-brought-forward.import.commit');
        Route::post('balance-brought-forward/import/{import}/reverse', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'reverse'])->name('balance-brought-forward.import.reverse');
        Route::put('balance-brought-forward/{student}', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'update'])->name('balance-brought-forward.update');
        Route::post('balance-brought-forward/add', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'add'])->name('balance-brought-forward.add');
        Route::get('balance-brought-forward/import/template', [\App\Http\Controllers\Finance\BalanceBroughtForwardController::class, 'template'])->name('balance-brought-forward.import.template');
        
        // Online Payments
        Route::post('payments/initiate-online', [PaymentController::class, 'initiateOnline'])->name('payments.initiate-online');
        Route::get('payment-transactions/{transaction}', [PaymentController::class, 'showTransaction'])->name('payment-transactions.show');
        Route::post('payment-transactions/{transaction}/verify', [PaymentController::class, 'verifyTransaction'])->name('payment-transactions.verify');

        // Credit & Debit Notes (manual)
        Route::get('credits/create', [CreditNoteController::class, 'create'])->name('credits.create');
        Route::post('credits/store', [CreditNoteController::class, 'store'])->name('credits.store');
        Route::delete('credit-notes/{creditNote}/reverse', [CreditNoteController::class, 'reverse'])->name('credit-notes.reverse');
        Route::get('debits/create',  [DebitNoteController::class, 'create'])->name('debits.create');
        Route::post('debits/store',  [DebitNoteController::class, 'store'])->name('debits.store');
        Route::delete('debit-notes/{debitNote}/reverse', [DebitNoteController::class, 'reverse'])->name('debit-notes.reverse');
        
        // Credit/Debit Notes Import
        Route::post('credit-debit-notes/import/preview', [\App\Http\Controllers\Finance\CreditDebitNoteImportController::class, 'importPreview'])->name('credit-debit-notes.import.preview');
        Route::post('credit-debit-notes/import/commit', [\App\Http\Controllers\Finance\CreditDebitNoteImportController::class, 'importCommit'])->name('credit-debit-notes.import.commit');
        Route::post('credit-debit-notes/import/{import}/reverse', [\App\Http\Controllers\Finance\CreditDebitNoteImportController::class, 'reverse'])->name('credit-debit-notes.import.reverse');
        Route::get('credit-debit-notes/import/template', [\App\Http\Controllers\Finance\CreditDebitNoteImportController::class, 'template'])->name('credit-debit-notes.import.template');

        // Fee Payment Plans
        Route::resource('fee-payment-plans', FeePaymentPlanController::class)->parameters(['fee-payment-plans' => 'feePaymentPlan']);
        Route::post('fee-payment-plans/{feePaymentPlan}/update-status', [FeePaymentPlanController::class, 'updateStatus'])->name('fee-payment-plans.update-status');

        // Fee Concessions
        Route::resource('fee-concessions', FeeConcessionController::class)->parameters(['fee-concessions' => 'feeConcession']);
        Route::post('fee-concessions/{feeConcession}/approve', [FeeConcessionController::class, 'approve'])->name('fee-concessions.approve');
        Route::post('fee-concessions/{feeConcession}/deactivate', [FeeConcessionController::class, 'deactivate'])->name('fee-concessions.deactivate');

        // Fee Reminders
        Route::resource('fee-reminders', FeeReminderController::class)->parameters(['fee-reminders' => 'feeReminder']);
        Route::post('fee-reminders/{feeReminder}/send', [FeeReminderController::class, 'send'])->name('fee-reminders.send');
        Route::post('fee-reminders/automated/send', [FeeReminderController::class, 'sendAutomatedReminders'])->name('fee-reminders.automated');

        // Accountant Dashboard
        Route::prefix('accountant-dashboard')->name('accountant-dashboard.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\AccountantDashboardController::class, 'index'])->name('index');
            Route::get('settings', [\App\Http\Controllers\Finance\AccountantDashboardController::class, 'settings'])->name('settings');
            Route::post('settings', [\App\Http\Controllers\Finance\AccountantDashboardController::class, 'updateSettings'])->name('settings.update');
            Route::get('students/{student}/history', [\App\Http\Controllers\Finance\AccountantDashboardController::class, 'studentHistory'])->name('student-history');
        });

        // Posting (Pending → Active)
        Route::prefix('posting')->name('posting.')->group(function(){
            Route::get('/',       [PostingController::class, 'index'])->name('index');
            Route::match(['get', 'post'], '/preview',[PostingController::class, 'preview'])->name('preview');
            Route::post('/commit', [PostingController::class, 'commit'])->name('commit');
            Route::get('/{run}',  [PostingController::class, 'show'])->name('show');
            Route::post('/{run}/reverse', [PostingController::class, 'reverse'])->name('reverse');
        });

        // Journals
        Route::prefix('journals')->name('journals.')->group(function(){
            Route::get('/', [JournalController::class, 'index'])->name('index');
            Route::get('/create', [JournalController::class, 'create'])->name('create');
            Route::post('/store', [JournalController::class, 'store'])->name('store');
            Route::get('/get-invoice-voteheads', [JournalController::class, 'getInvoiceVoteheads'])->name('get-invoice-voteheads');
        });
        // Journals (bulk)
        Route::get('journals/bulk',      [JournalController::class, 'bulkForm'])->name('journals.bulk.form');
        Route::post('journals/bulk',     [JournalController::class, 'bulkImport'])->name('journals.bulk.import');
        Route::get('journals/template',  [JournalController::class, 'template'])->name('journals.bulk.template');

        // Invoice line editing
        Route::post('invoices/{invoice}/items/{item}/update', [InvoiceController::class,'updateItem'])
            ->name('invoices.items.update');
        Route::get('invoices/{invoice}/history', [InvoiceController::class, 'history'])
            ->name('invoices.history');
            
        // Discounts (Fee Concessions)
        Route::get('discounts/replicate', [DiscountController::class, 'replicateForm'])->name('discounts.replicate.form');
        Route::post('discounts/replicate', [DiscountController::class, 'replicate'])->name('discounts.replicate');
        Route::prefix('discounts')->name('discounts.')->group(function () {
            Route::get('/', [DiscountController::class, 'index'])->name('index');
            Route::get('/create', [DiscountController::class, 'create'])->name('create');
            Route::post('/', [DiscountController::class, 'store'])->name('store');
            
            // Templates
            Route::get('/templates', [DiscountController::class, 'templatesIndex'])->name('templates.index');
            
            // Allocation
            Route::get('/allocate', [DiscountController::class, 'allocate'])->name('allocate');
            Route::post('/allocate', [DiscountController::class, 'storeAllocation'])->name('allocate.store');
            
            // Allocations list
            Route::get('/allocations', [DiscountController::class, 'allocationsIndex'])->name('allocations.index');
            
            // Approve/Reject (individual actions)
            Route::post('/approve/{discount}', [DiscountController::class, 'approve'])->name('approve');
            Route::post('/reject/{discount}', [DiscountController::class, 'reject'])->name('reject');
            
            // Bulk sibling allocation
            Route::get('/bulk-allocate-sibling', [DiscountController::class, 'bulkAllocateSiblingForm'])->name('bulk-allocate-sibling');
            Route::post('/bulk-allocate-sibling', [DiscountController::class, 'bulkAllocateSibling'])->name('bulk-allocate-sibling.store');
            
            // Bulk actions
            Route::post('/allocations/bulk-approve', [DiscountController::class, 'bulkApprove'])->name('allocations.bulk-approve');
            Route::post('/allocations/bulk-reject', [DiscountController::class, 'bulkReject'])->name('allocations.bulk-reject');
            
            // Reverse allocation
            Route::delete('/allocations/{allocation}/reverse', [DiscountController::class, 'reverse'])->name('allocations.reverse');
            
            // Apply sibling discount (legacy)
            Route::post('/apply-sibling/{student}', [DiscountController::class, 'applySiblingDiscount'])->name('apply-sibling');
            
            // Show discount (must be last to avoid route conflicts)
            Route::get('/{discount}', [DiscountController::class, 'show'])->name('show');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Exam Analytics
    |----------------------------------------------------------------------
    */
    Route::prefix('academics')->as('academics.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        Route::get('exam-analytics', [ExamAnalyticsController::class, 'index'])->name('exam-analytics.index');
        Route::get('exam-analytics/classroom/{classroom}', [ExamAnalyticsController::class, 'classroomPerformance'])->name('exam-analytics.classroom');
    });

    /*
    |----------------------------------------------------------------------
    | Events Calendar
    |----------------------------------------------------------------------
    */
    Route::prefix('events')->name('events.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        Route::get('/', [EventCalendarController::class, 'index'])->name('index');
        Route::get('/api', [EventCalendarController::class, 'api'])->name('api');
        Route::get('/create', [EventCalendarController::class, 'create'])->name('create');
        Route::post('/', [EventCalendarController::class, 'store'])->name('store');
        Route::get('/{event}', [EventCalendarController::class, 'show'])->name('show');
        Route::get('/{event}/edit', [EventCalendarController::class, 'edit'])->name('edit');
        Route::put('/{event}', [EventCalendarController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventCalendarController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Document Management
    |----------------------------------------------------------------------
    */
    Route::prefix('documents')->name('documents.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        Route::get('/', [DocumentManagementController::class, 'index'])->name('index');
        Route::get('/create', [DocumentManagementController::class, 'create'])->name('create');
        Route::post('/', [DocumentManagementController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentManagementController::class, 'show'])->name('show');
        Route::get('/{document}/download', [DocumentManagementController::class, 'download'])->name('download');
        Route::get('/{document}/preview', [DocumentManagementController::class, 'preview'])->name('preview');
        Route::post('/{document}/email', [DocumentManagementController::class, 'email'])->name('email');
        Route::post('/{document}/version', [DocumentManagementController::class, 'updateVersion'])->name('version');
        Route::delete('/{document}', [DocumentManagementController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Document Templates & Generation
    |----------------------------------------------------------------------
    */
    Route::prefix('document-templates')->name('document-templates.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        Route::get('/', [\App\Http\Controllers\DocumentTemplateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\DocumentTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\DocumentTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [\App\Http\Controllers\DocumentTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/preview', [\App\Http\Controllers\DocumentTemplateController::class, 'preview'])->name('preview');
        Route::post('/{template}/generate/student/{student}', [\App\Http\Controllers\DocumentTemplateController::class, 'generateForStudent'])->name('generate.student');
        Route::post('/{template}/generate/staff/{staff}', [\App\Http\Controllers\DocumentTemplateController::class, 'generateForStaff'])->name('generate.staff');
    });

    Route::prefix('generated-documents')->name('generated-documents.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        Route::get('/', [\App\Http\Controllers\GeneratedDocumentController::class, 'index'])->name('index');
        Route::get('/{generatedDocument}', [\App\Http\Controllers\GeneratedDocumentController::class, 'show'])->name('show');
        Route::get('/{generatedDocument}/download', [\App\Http\Controllers\GeneratedDocumentController::class, 'download'])->name('download');
        Route::delete('/{generatedDocument}', [\App\Http\Controllers\GeneratedDocumentController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Backup & Restore
    |----------------------------------------------------------------------
    */
    Route::prefix('backup-restore')->name('backup-restore.')->middleware('role:Super Admin|Admin')->group(function () {
        Route::get('/', [BackupRestoreController::class, 'index'])->name('index');
        Route::post('/create', [BackupRestoreController::class, 'create'])->name('create');
        Route::get('/download/{filename}', [BackupRestoreController::class, 'download'])->name('download');
        Route::post('/restore', [BackupRestoreController::class, 'restore'])->name('restore');
        Route::post('/schedule', [BackupRestoreController::class, 'updateSchedule'])->name('schedule');
    });

    /*
    |----------------------------------------------------------------------
    | Inventory & Requirements Management
    |----------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('inventory.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        // Inventory Items
        Route::resource('items', \App\Http\Controllers\Inventory\InventoryItemController::class);
        Route::post('items/{item}/adjust-stock', [\App\Http\Controllers\Inventory\InventoryItemController::class, 'adjustStock'])->name('items.adjust-stock');
        
        // Requirement Types
        Route::get('requirement-types', [\App\Http\Controllers\Inventory\RequirementTypeController::class, 'index'])->name('requirement-types.index');
        Route::post('requirement-types', [\App\Http\Controllers\Inventory\RequirementTypeController::class, 'store'])->name('requirement-types.store');
        Route::put('requirement-types/{type}', [\App\Http\Controllers\Inventory\RequirementTypeController::class, 'update'])->name('requirement-types.update');
        Route::delete('requirement-types/{type}', [\App\Http\Controllers\Inventory\RequirementTypeController::class, 'destroy'])->name('requirement-types.destroy');
        
        // Requirement Templates
        Route::resource('requirement-templates', \App\Http\Controllers\Inventory\RequirementTemplateController::class);
        
        // Student Requirements
        Route::get('student-requirements', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'index'])->name('student-requirements.index');
        Route::get('student-requirements/collect', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'collectForm'])->name('student-requirements.collect');
        Route::post('student-requirements/collect', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'collect'])->name('student-requirements.collect.store');
        Route::get('student-requirements/load-streams', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'loadStreams'])->name('student-requirements.load-streams');
        Route::get('student-requirements/load-students', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'loadStudents'])->name('student-requirements.load-students');
        Route::get('student-requirements/load-student-requirements', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'loadStudentRequirements'])->name('student-requirements.load-student-requirements');
        Route::get('student-requirements/{requirement}', [\App\Http\Controllers\Inventory\StudentRequirementController::class, 'show'])->name('student-requirements.show');
        
        // Requisitions
        Route::get('requisitions', [\App\Http\Controllers\Inventory\RequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('requisitions/create', [\App\Http\Controllers\Inventory\RequisitionController::class, 'create'])->name('requisitions.create');
        Route::post('requisitions', [\App\Http\Controllers\Inventory\RequisitionController::class, 'store'])->name('requisitions.store');
        Route::get('requisitions/{requisition}', [\App\Http\Controllers\Inventory\RequisitionController::class, 'show'])->name('requisitions.show');
        Route::post('requisitions/{requisition}/approve', [\App\Http\Controllers\Inventory\RequisitionController::class, 'approve'])->name('requisitions.approve');
        Route::post('requisitions/{requisition}/fulfill', [\App\Http\Controllers\Inventory\RequisitionController::class, 'fulfill'])->name('requisitions.fulfill');
        Route::post('requisitions/{requisition}/reject', [\App\Http\Controllers\Inventory\RequisitionController::class, 'reject'])->name('requisitions.reject');
    });

    /*
    |----------------------------------------------------------------------
    | Point of Sale (POS) Management
    |----------------------------------------------------------------------
    */
    Route::prefix('pos')->name('pos.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        // Products
        Route::resource('products', \App\Http\Controllers\Pos\ProductController::class);
        Route::post('products/{product}/adjust-stock', [\App\Http\Controllers\Pos\ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::post('products/bulk-import', [\App\Http\Controllers\Pos\ProductController::class, 'bulkImport'])->name('products.bulk-import');
        Route::get('products/template/download', [\App\Http\Controllers\Pos\ProductController::class, 'downloadTemplate'])->name('products.template.download');
        
        // Product Variants
        Route::get('products/{product}/variants', [\App\Http\Controllers\Pos\ProductVariantController::class, 'index'])->name('products.variants.index');
        Route::post('products/{product}/variants', [\App\Http\Controllers\Pos\ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('variants/{variant}', [\App\Http\Controllers\Pos\ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('variants/{variant}', [\App\Http\Controllers\Pos\ProductVariantController::class, 'destroy'])->name('variants.destroy');
        
        // Orders
        Route::get('orders', [\App\Http\Controllers\Pos\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [\App\Http\Controllers\Pos\OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/update-status', [\App\Http\Controllers\Pos\OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/cancel', [\App\Http\Controllers\Pos\OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/items/{item}/fulfill', [\App\Http\Controllers\Pos\OrderController::class, 'fulfillItem'])->name('orders.items.fulfill');
        
        // Discounts
        Route::resource('discounts', \App\Http\Controllers\Pos\DiscountController::class);
        
        // Public Shop Links
        Route::resource('public-links', \App\Http\Controllers\Pos\PublicShopLinkController::class);
        Route::post('public-links/{link}/regenerate-token', [\App\Http\Controllers\Pos\PublicShopLinkController::class, 'regenerateToken'])->name('public-links.regenerate-token');
        
        // Teacher Requirements (for teachers)
        Route::get('teacher-requirements', [\App\Http\Controllers\Pos\TeacherRequirementsController::class, 'index'])->name('teacher-requirements.index');
        Route::get('teacher-requirements/{requirement}', [\App\Http\Controllers\Pos\TeacherRequirementsController::class, 'show'])->name('teacher-requirements.show');
        Route::post('teacher-requirements/{requirement}/mark-received', [\App\Http\Controllers\Pos\TeacherRequirementsController::class, 'markReceived'])->name('teacher-requirements.mark-received');
        
        // Uniform Management
        Route::get('uniforms', [\App\Http\Controllers\Pos\UniformController::class, 'index'])->name('uniforms.index');
        Route::get('uniforms/{uniform}', [\App\Http\Controllers\Pos\UniformController::class, 'show'])->name('uniforms.show');
        Route::get('uniforms/{uniform}/manage-sizes', [\App\Http\Controllers\Pos\UniformController::class, 'manageSizes'])->name('uniforms.manage-sizes');
        Route::post('uniforms/{uniform}/update-size-stock', [\App\Http\Controllers\Pos\UniformController::class, 'updateSizeStock'])->name('uniforms.update-size-stock');
        Route::get('uniforms/backorders', [\App\Http\Controllers\Pos\UniformController::class, 'backorders'])->name('uniforms.backorders');
        Route::post('orders/{order}/items/{item}/fulfill-backorder', [\App\Http\Controllers\Pos\UniformController::class, 'fulfillBackorder'])->name('orders.items.fulfill-backorder');
    });

    /*
    |----------------------------------------------------------------------
    | Library Management
    |----------------------------------------------------------------------
    */
    Route::prefix('library')->name('library.')->middleware('role:Super Admin|Admin|Secretary|Teacher|teacher')->group(function () {
        // Books
        Route::resource('books', \App\Http\Controllers\Library\BookController::class);
        
        // Library Cards
        Route::get('cards', [\App\Http\Controllers\Library\LibraryCardController::class, 'index'])->name('cards.index');
        Route::get('cards/create', [\App\Http\Controllers\Library\LibraryCardController::class, 'create'])->name('cards.create');
        Route::post('cards', [\App\Http\Controllers\Library\LibraryCardController::class, 'store'])->name('cards.store');
        Route::get('cards/{card}', [\App\Http\Controllers\Library\LibraryCardController::class, 'show'])->name('cards.show');
        Route::post('cards/{card}/renew', [\App\Http\Controllers\Library\LibraryCardController::class, 'renew'])->name('cards.renew');
        
        // Borrowings
        Route::get('borrowings', [\App\Http\Controllers\Library\BookBorrowingController::class, 'index'])->name('borrowings.index');
        Route::get('borrowings/create', [\App\Http\Controllers\Library\BookBorrowingController::class, 'create'])->name('borrowings.create');
        Route::post('borrowings', [\App\Http\Controllers\Library\BookBorrowingController::class, 'store'])->name('borrowings.store');
        Route::get('borrowings/{borrowing}', [\App\Http\Controllers\Library\BookBorrowingController::class, 'show'])->name('borrowings.show');
        Route::post('borrowings/{borrowing}/return', [\App\Http\Controllers\Library\BookBorrowingController::class, 'return'])->name('borrowings.return');
        Route::post('borrowings/{borrowing}/renew', [\App\Http\Controllers\Library\BookBorrowingController::class, 'renew'])->name('borrowings.renew');
    });

    /*
    |----------------------------------------------------------------------
    | Hostel Management
    |----------------------------------------------------------------------
    */
    Route::prefix('hostel')->name('hostel.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        // Hostels
        Route::resource('hostels', \App\Http\Controllers\Hostel\HostelController::class);
        
        // Allocations
        Route::get('allocations', [\App\Http\Controllers\Hostel\HostelAllocationController::class, 'index'])->name('allocations.index');
        Route::get('allocations/create', [\App\Http\Controllers\Hostel\HostelAllocationController::class, 'create'])->name('allocations.create');
        Route::post('allocations', [\App\Http\Controllers\Hostel\HostelAllocationController::class, 'store'])->name('allocations.store');
        Route::get('allocations/{allocation}', [\App\Http\Controllers\Hostel\HostelAllocationController::class, 'show'])->name('allocations.show');
        Route::post('allocations/{allocation}/deallocate', [\App\Http\Controllers\Hostel\HostelAllocationController::class, 'deallocate'])->name('allocations.deallocate');
    });

    /*
    |----------------------------------------------------------------------
    | Activity Logs
    |----------------------------------------------------------------------
    */
    Route::prefix('activity-logs')->name('activity-logs.')->middleware('role:Super Admin|Admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('index');
        Route::get('/{log}', [\App\Http\Controllers\ActivityLogController::class, 'show'])->name('show');
    });

    /*
    |----------------------------------------------------------------------
    | System Logs
    |----------------------------------------------------------------------
    */
    Route::prefix('system-logs')->name('system-logs.')->middleware('role:Super Admin|Admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\SystemLogController::class, 'index'])->name('index');
        Route::post('/clear', [\App\Http\Controllers\SystemLogController::class, 'clear'])->name('clear');
        Route::get('/download', [\App\Http\Controllers\SystemLogController::class, 'download'])->name('download');
    });

});

/*
|--------------------------------------------------------------------------
| Public Online Admissions
|--------------------------------------------------------------------------
*/
Route::prefix('online-admissions')->group(function () {
    Route::get('/apply', [OnlineAdmissionController::class, 'showPublicForm'])->name('online-admissions.public-form');
    Route::post('/apply', [OnlineAdmissionController::class, 'storePublicApplication'])->name('online-admissions.public-submit');
});

/*
||--------------------------------------------------------------------------
|| Public POS Shop
||--------------------------------------------------------------------------
*/
Route::prefix('shop')->name('pos.shop.')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\Pos\PublicShopController::class, 'shop'])->name('public');
    Route::post('/{token}/cart/add', [\App\Http\Controllers\Pos\PublicShopController::class, 'addToCart'])->name('cart.add');
    Route::post('/{token}/cart/update', [\App\Http\Controllers\Pos\PublicShopController::class, 'updateCart'])->name('cart.update');
    Route::post('/{token}/cart/remove', [\App\Http\Controllers\Pos\PublicShopController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/{token}/cart', [\App\Http\Controllers\Pos\PublicShopController::class, 'getCart'])->name('cart.get');
    Route::post('/{token}/discount/apply', [\App\Http\Controllers\Pos\PublicShopController::class, 'applyDiscount'])->name('discount.apply');
    Route::get('/{token}/checkout', [\App\Http\Controllers\Pos\PublicShopController::class, 'checkout'])->name('checkout');
    Route::post('/{token}/checkout', [\App\Http\Controllers\Pos\PublicShopController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/{token}/order/{order}/confirmation', [\App\Http\Controllers\Pos\PublicShopController::class, 'orderConfirmation'])->name('order-confirmation');
    Route::get('/{token}/order/{order}/payment', [\App\Http\Controllers\Pos\PaymentController::class, 'initiatePayment'])->name('payment');
    Route::post('/{token}/order/{order}/payment', [\App\Http\Controllers\Pos\PaymentController::class, 'initiatePayment'])->name('payment.initiate');
    Route::get('/{token}/order/{order}/payment-status', [\App\Http\Controllers\Pos\PaymentController::class, 'paymentStatus'])->name('payment-status');
    Route::post('/{token}/order/{order}/verify-payment', [\App\Http\Controllers\Pos\PaymentController::class, 'verifyPayment'])->name('verify-payment');
});

// Include teacher routes
require __DIR__.'/teacher.php';

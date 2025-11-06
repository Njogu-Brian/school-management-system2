<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Auth + Home
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Modules
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceNotificationController;

use App\Http\Controllers\TransportController;
use App\Http\Controllers\RouteController as SchoolRouteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DropOffPointController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\AcademicConfigController;

use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParentInfoController;
use App\Http\Controllers\OnlineAdmissionController;

use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CommunicationTemplateController;
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\PlaceholderController;

use App\Http\Controllers\VoteheadController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\InvoiceAdjustmentController;
use App\Http\Controllers\OptionalFeeController;

use App\Http\Controllers\SettingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\LookupController;

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
use App\Http\Controllers\Academics\behaviourController;
use App\Http\Controllers\Academics\HomeworkController;
use App\Http\Controllers\Academics\DiaryController;
use App\Http\Controllers\Academics\StudentBehaviourController;
use App\Http\Controllers\Academics\ExamPaperController;
use App\Http\Controllers\Academics\DiaryMessageController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public student search (kept public per your original)
Route::get('/students/search', [StudentController::class, 'search'])->name('students.search');

// SMS Delivery Report Webhook
Route::post('/webhooks/sms/dlr', [\App\Http\Controllers\CommunicationController::class, 'smsDeliveryReport']) ->name('webhooks.sms.dlr');
/*
|--------------------------------------------------------------------------
| Home redirect by role
|--------------------------------------------------------------------------
*/
Route::get('/home', function () {
    $user = Auth::user();
    $user->load('roles');

    $map = [
        'Super Admin' => 'admin.dashboard',
        'Admin'       => 'admin.dashboard',
        'Secretary'   => 'admin.dashboard',
        'Teacher'     => 'teacher.dashboard',
        'Driver'      => 'transport.index',
        'Parent'      => 'students.index',
        'Student'     => 'student.dashboard',
    ];

    foreach ($user->roles as $role) {
        if (isset($map[$role->name])) {
            return redirect()->route($map[$role->name]);
        }
    }
    abort(403, 'No dashboard defined for your role.');
})->middleware('auth')->name('home');

    /*
    |---------------------- Dashboards ----------------------
    */
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/teacher/home', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/parent/home', [DashboardController::class, 'parentDashboard'])->name('parent.dashboard');
    Route::get('/student/home', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');
    Route::get('/finance/home', [DashboardController::class, 'financeDashboard'])->name('finance.dashboard');
    Route::get('/transport/home', [DashboardController::class, 'transportDashboard'])->name('transport.dashboard');
});


/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |---------------------- Attendance ----------------------
    */
    Route::prefix('attendance')
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')
        ->group(function () {
            Route::get('/mark', [AttendanceController::class, 'markForm'])->name('attendance.mark.form');
            Route::post('/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
            Route::get('/records', [AttendanceController::class, 'records'])->name('attendance.records');
            Route::get('/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
            Route::post('/update/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
        });

    // Attendance notifications (kept separate; removed the stray resource-on-/ bug)
    Route::prefix('attendance/notifications')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/', [AttendanceNotificationController::class, 'index'])->name('attendance.notifications.index');
            Route::get('/create', [AttendanceNotificationController::class, 'create'])->name('attendance.notifications.create');
            Route::post('/', [AttendanceNotificationController::class, 'store'])->name('attendance.notifications.store');
            Route::get('/{id}/edit', [AttendanceNotificationController::class, 'edit'])->name('attendance.notifications.edit');
            Route::put('/{id}', [AttendanceNotificationController::class, 'update'])->name('attendance.notifications.update');
            Route::delete('/{id}', [AttendanceNotificationController::class, 'destroy'])->name('attendance.notifications.destroy');

            Route::get('/notify', [AttendanceNotificationController::class, 'notifyForm'])->name('attendance.notifications.notify.form');
            Route::post('/notify', [AttendanceNotificationController::class, 'notifySend'])->name('attendance.notifications.notify.send');
        });
    
        // ===== Exams: Core =====
Route::middleware(['auth'])->group(function () {

    // Exam Groups
    Route::resource(
        'academics/exam-groups',
        \App\Http\Controllers\Academics\ExamGroupController::class
    )->names('exams.groups')->only(['index','store','edit','update','destroy']);

    // Exam Types
    Route::resource(
        'academics/exam-types',
        \App\Http\Controllers\Academics\ExamTypeController::class
    )->names('exams.types')->only(['index','store','update','destroy']);

    // Exams CRUD (you already have ExamController)
    Route::resource(
        'academics/exams',
        \App\Http\Controllers\Academics\ExamController::class
    )->names('academics.exams'); // index/create/store/edit/update/destroy

    // Schedules (per-exam)
    Route::get('exams/{exam}/schedules',        [\App\Http\Controllers\Academics\ExamScheduleController::class, 'index'])->name('exams.schedules.index');
    Route::post('exams/{exam}/schedules',       [\App\Http\Controllers\Academics\ExamScheduleController::class, 'store'])->name('exams.schedules.store');
    Route::patch('exams/{exam}/schedules/{examSchedule}', [\App\Http\Controllers\Academics\ExamScheduleController::class, 'update'])->name('exams.schedules.update');
    Route::delete('exams/{exam}/schedules/{examSchedule}',[\App\Http\Controllers\Academics\ExamScheduleController::class, 'destroy'])->name('exams.schedules.destroy');

    // Results (admin listing + publish)
    Route::get('exams/results', [\App\Http\Controllers\Academics\ExamResultController::class,'index'])->name('exams.results.index');

    // Bulk marks entry (reuses your existing ExamMarkController routes if you prefer)
Route::get('exam-marks',                 [ExamMarkController::class, 'index'])->name('academics.exam-marks.index');
Route::get('exam-marks/bulk',            [ExamMarkController::class, 'bulkForm'])->name('academics.exam-marks.bulk.form');
Route::post('exam-marks/bulk',           [ExamMarkController::class, 'bulkEdit'])->name('academics.exam-marks.bulk.edit');
Route::get('exam-marks/bulk/view',       [ExamMarkController::class, 'bulkEditView'])->name('academics.exam-marks.bulk.edit.view');
Route::post('exam-marks/bulk/store',     [ExamMarkController::class, 'bulkStore'])->name('academics.exam-marks.bulk.store');
Route::get('exam-marks/{exam_mark}/edit',[ExamMarkController::class, 'edit'])->name('academics.exam-marks.edit');
Route::put('exam-marks/{exam_mark}',     [ExamMarkController::class, 'update'])->name('academics.exam-marks.update');

    // Publish results to report cards
    Route::post('exams/publish/{exam}', [\App\Http\Controllers\Academics\ExamPublishingController::class,'publish'])->name('exams.publish');

    // Print timetable
    Route::get('academics/exams/timetable', [\App\Http\Controllers\Academics\ExamController::class,'timetable'])->name('academics.exams.timetable');
});

           /*
    |---------------------- Academics ----------------------
    */
    Route::prefix('academics')->as('academics.')
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')
        ->group(function () {

        // Core setup
        Route::resource('classrooms', ClassroomController::class)->except(['show']);
        Route::resource('streams', StreamController::class)->except(['show']);
        Route::resource('subject_groups', SubjectGroupController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class)->except(['show']);
        Route::resource('exams', ExamController::class)->except(['show']);
        Route::resource('exam-grades', ExamGradeController::class);
        Route::resource('homework', HomeworkController::class);
        Route::resource('diaries', DiaryController::class);

        // Diary messages
        Route::prefix('diaries/{diary}/messages')->as('diary.messages.')->group(function () {
            Route::post('/', [DiaryMessageController::class, 'store'])->name('store');
        });

        /*
        |---------------------- Term Assessment (roll-up) ----------------------
        | UI to view all exams in a term for a class (optional subject filter)
        */
        Route::get('assessments/term', [ReportCardController::class,'termAssessment'])
            ->name('assessments.term');

        /*
        |---------------------- Report Cards ----------------------
        */
        // routes/web.php  (inside the academics group)
        Route::resource('report_cards', ReportCardController::class)
            ->names('report_cards')
            ->parameters(['report_cards' => 'report_card']);

        Route::delete('report_cards/{report_card}', [ReportCardController::class,'destroy'])
            ->name('report_cards.destroy');

        Route::post('report_cards/{report}/publish', [ReportCardController::class,'publish'])
            ->name('report_cards.publish');

        Route::get('report_cards/{report}/pdf', [ReportCardController::class,'exportPdf'])
            ->name('report_cards.pdf');

        Route::get('r/{token}', [ReportCardController::class,'publicView'])
            ->name('report_cards.public');

        // Batch generate (dashboard)
        Route::get('report_cards/generate', [ReportCardController::class,'generateForm'])
            ->name('report_cards.generate.form');
        Route::post('report_cards/generate', [ReportCardController::class,'generate'])
            ->name('report_cards.generate');

        // Nested SKILLS routes (per-report)
        Route::prefix('report_cards/{report_card}')->as('report_cards.skills.')->group(function () {
            Route::get('skills',             [ReportCardSkillController::class,'index'])->name('index');
            Route::get('skills/create',      [ReportCardSkillController::class,'create'])->name('create');
            Route::post('skills',            [ReportCardSkillController::class,'store'])->name('store');
            Route::get('skills/{skill}/edit',[ReportCardSkillController::class,'edit'])->name('edit');
            Route::put('skills/{skill}',     [ReportCardSkillController::class,'update'])->name('update');
            Route::delete('skills/{skill}',  [ReportCardSkillController::class,'destroy'])->name('destroy');
        });

        /*
        |---------------------- Behaviour ----------------------
        */
        Route::resource('behaviours', BehaviourController::class);
        Route::resource('student-behaviours', StudentBehaviourController::class);


        /*
        |---------------------- Student Skill Grades (per-term grading UI) ----------------------
        | Index shows class/term selector + grid; Store saves grades.
        */
        Route::get('skills/grade',  [\App\Http\Controllers\Academics\StudentSkillGradeController::class,'index'])
            ->name('skills.grade.index');
        Route::post('skills/grade', [\App\Http\Controllers\Academics\StudentSkillGradeController::class,'store'])
            ->name('skills.grade.store');
    });

    /*
    |---------------------- Transport ----------------------
    */
    Route::prefix('transport')
        ->name('transport.')
        ->middleware('role:Super Admin|Admin|Secretary|Driver')
        ->group(function () {

            Route::get('/', [TransportController::class, 'index'])->name('index');

            // AJAX/data helpers
            Route::get('/routes/{route}/data', [TransportController::class, 'getRouteData'])->name('routes.data');
            Route::get('/routes/{route}/dropoff-points', [TransportController::class, 'getDropOffPoints'])->name('routes.dropoffs');
            Route::get('/routes/{route}/vehicles', [TransportController::class, 'getVehicles'])->name('routes.vehicles');

            // Actions
            Route::post('/assign-driver', [TransportController::class, 'assignDriver'])->name('assign.driver');
            Route::post('/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('assign.student');
            Route::post('/routes/{route}/assign-vehicle', [SchoolRouteController::class, 'assignVehicle'])->name('routes.assignVehicle');

            // Resources
            Route::resource('routes', SchoolRouteController::class)->except(['show']);
            Route::resource('vehicles', VehicleController::class)->except(['show']);
            Route::resource('trips', TripController::class);
            Route::resource('dropoffpoints', DropOffPointController::class);
            Route::resource('student-assignments', StudentAssignmentController::class)
                ->parameters(['student-assignments' => 'student_assignment']);

            // Import & Template
            Route::get('dropoffpoints/import', [DropOffPointController::class, 'importForm'])->name('dropoffpoints.import.form');
            Route::post('dropoffpoints/import', [DropOffPointController::class, 'import'])->name('dropoffpoints.import');
            Route::get('dropoffpoints/template', [DropOffPointController::class, 'template'])->name('dropoffpoints.template');
        });

    /*
    |---------------------- Staff ----------------------
    */
    Route::prefix('staff')
        ->name('staff.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('/create', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{id}', [StaffController::class, 'update'])->name('update');

            // Archive / Restore
            Route::patch('/{id}/archive', [StaffController::class, 'archive'])->name('archive');
            Route::patch('/{id}/restore', [StaffController::class, 'restore'])->name('restore');

            // Bulk Upload
            Route::get('/upload', [StaffController::class, 'showUploadForm'])->name('upload.form');
            Route::post('/upload', [StaffController::class, 'handleUpload'])->name('upload.handle');
        });

    /*
    |---------------------- Settings ----------------------
    */
    Route::prefix('settings')
        ->name('settings.')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');

            // Branding / General / Regional / System / ID configs
            Route::post('/update-branding', [SettingController::class, 'updateBranding'])->name('update.branding');
            Route::post('/update-general', [SettingController::class, 'updateSettings'])->name('update.general');
            Route::post('/update-regional', [SettingController::class, 'updateRegional'])->name('update.regional');
            Route::post('/update-system', [SettingController::class, 'updateSystem'])->name('update.system');
            Route::post('/id-settings', [SettingController::class, 'updateIdSettings'])->name('ids.save');

            // Modules update
            Route::post('/update-modules', [SettingController::class, 'updateModules'])->name('update.modules');

            // Unified Roles & Lookups page
            Route::get('/access-lookups', [SettingController::class, 'accessAndLookups'])->name('access_lookups');

            // Role & Permission management
            Route::post('/roles/{role}/update-permissions', [RolePermissionController::class, 'update'])
                ->name('roles.update_permissions');


        });

    // Academic Config (Years, Terms)
    Route::prefix('settings')->name('settings.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        Route::get('academic', [AcademicConfigController::class, 'index'])->name('academic.index');

        // Year
        Route::get('academic/year/create', [AcademicConfigController::class, 'createYear'])->name('academic.year.create');
        Route::post('academic/year', [AcademicConfigController::class, 'storeYear'])->name('academic.year.store');
        Route::get('academic/year/{year}/edit', [AcademicConfigController::class, 'editYear'])->name('academic.year.edit');
        Route::put('academic/year/{year}', [AcademicConfigController::class, 'updateYear'])->name('academic.year.update');
        Route::delete('academic/year/{year}', [AcademicConfigController::class, 'destroyYear'])->name('academic.year.destroy');

        // Term
        Route::get('academic/term/create', [AcademicConfigController::class, 'createTerm'])->name('academic.term.create');
        Route::post('academic/term', [AcademicConfigController::class, 'storeTerm'])->name('academic.term.store');
        Route::get('academic/term/{term}/edit', [AcademicConfigController::class, 'editTerm'])->name('academic.term.edit');
        Route::put('academic/term/{term}', [AcademicConfigController::class, 'updateTerm'])->name('academic.term.update');
        Route::delete('academic/term/{term}', [AcademicConfigController::class, 'destroyTerm'])->name('academic.term.destroy');
    });


    /*
    |---------------------- Lookups AJAX Endpoints ----------------------
    */
    Route::prefix('lookups')
        ->middleware('role:Super Admin|Admin|Secretary')
        ->group(function () {
            Route::post('/category', [LookupController::class, 'storeCategory'])->name('lookups.category.store');
            Route::delete('/category/{id}', [LookupController::class, 'deleteCategory'])->name('lookups.category.delete');

            Route::post('/department', [LookupController::class, 'storeDepartment'])->name('lookups.department.store');
            Route::delete('/department/{id}', [LookupController::class, 'deleteDepartment'])->name('lookups.department.delete');

            Route::post('/job-title', [LookupController::class, 'storeJobTitle'])->name('lookups.jobtitle.store');
            Route::delete('/job-title/{id}', [LookupController::class, 'deleteJobTitle'])->name('lookups.jobtitle.delete');

            Route::post('/custom-field', [LookupController::class, 'storeCustomField'])->name('lookups.customfield.store');
            Route::delete('/custom-field/{id}', [LookupController::class, 'deleteCustomField'])->name('lookups.customfield.delete');
        });

    /*
    |---------------------- Students ----------------------
    */
    Route::resource('students', StudentController::class)
        ->except(['destroy', 'show'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher');

    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.archive');

    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.restore');

    Route::get('/students/bulk-upload', [StudentController::class, 'bulkForm'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk');

    Route::post('/students/bulk-parse', [StudentController::class, 'bulkParse'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.parse');

    Route::post('/students/bulk-import', [StudentController::class, 'bulkImport'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.import');

    Route::get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])
        ->middleware('role:Super Admin|Admin|Secretary')->name('students.bulk.template');

    // Helper for cascading class → streams
    Route::post('/get-streams', [StudentController::class, 'getStreams'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')->name('students.getStreams');

    // API-like search route (kept inside auth per your code; name preserved)
    Route::get('/api/students/search', [StudentController::class, 'search'])
        ->middleware('role:Super Admin|Admin|Secretary|Teacher')->name('api.students.search');

    
    /*
    |---------------------- Parents ----------------------
    */
    Route::resource('parent-info', ParentInfoController::class)
        ->except(['show'])
        ->middleware('role:Super Admin|Admin|Secretary');

    /*
    |---------------------- Online Admissions ----------------------
    */
    Route::prefix('online-admissions')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        Route::get('/', [OnlineAdmissionController::class, 'index'])->name('online-admissions.index');
        Route::post('/approve/{id}', [OnlineAdmissionController::class, 'approve'])->name('online-admissions.approve');
        Route::post('/reject/{id}', [OnlineAdmissionController::class, 'reject'])->name('online-admissions.reject');
        // Public-facing form endpoints were in your auth group; keeping them here to match your blade links:
        Route::get('/admission-form', [OnlineAdmissionController::class, 'showForm'])->name('online-admission.form');
        Route::post('/admission-form', [OnlineAdmissionController::class, 'submitForm'])->name('online-admission.submit');
    });

    /*
    |---------------------- Communication ----------------------
    */
    Route::prefix('communication')->middleware('role:Super Admin|Admin|Secretary')->group(function () {
        // Senders
        Route::get('send-email', [CommunicationController::class, 'createEmail'])->name('communication.send.email');
        Route::post('send-email', [CommunicationController::class, 'sendEmail'])->name('communication.send.email.submit');

        Route::get('send-sms', [CommunicationController::class, 'createSMS'])->name('communication.send.sms');
        Route::post('send-sms', [CommunicationController::class, 'sendSMS'])->name('communication.send.sms.submit');

        // Logs
        Route::get('logs', [CommunicationController::class, 'logs'])->name('communication.logs');
        Route::get('logs/scheduled', [CommunicationController::class, 'logsScheduled'])->name('communication.logs.scheduled');

        // Announcements
        Route::resource('announcements', CommunicationAnnouncementController::class)->except(['show']);

        // Templates
        Route::resource('communication-templates', CommunicationTemplateController::class)
            ->parameters(['communication-templates' => 'communication_template'])
            ->except(['show']);
    });

        //Placeholders  
        Route::prefix('settings/placeholders')->middleware(['auth'])->group(function () {
        Route::get('/', [App\Http\Controllers\Settings\PlaceholderController::class, 'index'])->name('settings.placeholders.index');
        Route::get('/create', [App\Http\Controllers\Settings\PlaceholderController::class, 'create'])->name('settings.placeholders.create');
        Route::post('/store', [App\Http\Controllers\Settings\PlaceholderController::class, 'store'])->name('settings.placeholders.store');
        Route::get('/{placeholder}/edit', [App\Http\Controllers\Settings\PlaceholderController::class, 'edit'])->name('settings.placeholders.edit');
        Route::put('/{placeholder}', [App\Http\Controllers\Settings\PlaceholderController::class, 'update'])->name('settings.placeholders.update');
        Route::delete('/{placeholder}', [App\Http\Controllers\Settings\PlaceholderController::class, 'destroy'])->name('settings.placeholders.destroy');
        Route::post('/settings/placeholders/store', [SettingController::class, 'storePlaceholder'])->name('settings.placeholders.store');

    });

    /*
    |---------------------- Finance ----------------------
    */
    Route::prefix('finance')->name('finance.')->middleware('role:Super Admin|Admin|Secretary')->group(function () {

        // Voteheads
        Route::resource('voteheads', VoteheadController::class)->except(['show']);

        // Fee Structures
        Route::get('fee-structures/manage', [FeeStructureController::class, 'manage'])->name('fee-structures.manage');
        Route::post('fee-structures/manage', [FeeStructureController::class, 'save'])->name('fee-structures.save');
        Route::post('fee-structures/replicate', [FeeStructureController::class, 'replicateTo'])->name('fee-structures.replicate');

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/generate', [InvoiceController::class, 'generate'])->name('generate');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
            Route::post('/reverse/{invoice}', [InvoiceController::class, 'reverse'])->name('reverse');

            // Print PDFs
            Route::get('/print',              [InvoiceController::class, 'printBulk'])->name('print');
            Route::get('/{invoice}/print',    [InvoiceController::class, 'printSingle'])->name('print_single');

            // Excel Import
            Route::get('/import', [InvoiceController::class, 'importForm'])->name('import.form');
            Route::post('/import', [InvoiceController::class, 'import'])->name('import');

            // Adjustments (Credit/Debit Notes via batch import)
            Route::get('/adjustments/import', [InvoiceAdjustmentController::class, 'importForm'])->name('adjustments.import.form');
            Route::post('/adjustments/import', [InvoiceAdjustmentController::class, 'import'])->name('adjustments.import');
        });

        // Optional Fees
        Route::prefix('optional-fees')->name('optional_fees.')->group(function () {
            Route::get('/', [OptionalFeeController::class, 'index'])->name('index');
            Route::get('/class', [OptionalFeeController::class, 'classView'])->name('class_view');
            Route::post('/class/save', [OptionalFeeController::class, 'saveClassBilling'])->name('save_class');
            Route::get('/student', [OptionalFeeController::class, 'studentView'])->name('student_view');
            Route::post('/student/save', [OptionalFeeController::class, 'saveStudentBilling'])->name('save_student');
        });

        // Payments
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments/store', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/receipt/{payment}', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

        // Credit & Debit Notes (manual create)
        Route::get('credits/create', [CreditNoteController::class, 'create'])->name('credits.create');
        Route::post('credits/store', [CreditNoteController::class, 'store'])->name('credits.store');
        Route::get('debits/create', [DebitNoteController::class, 'create'])->name('debits.create');
        Route::post('debits/store', [DebitNoteController::class, 'store'])->name('debits.store');

        // Posting (Pending → Active)
        Route::prefix('posting')->name('posting.')->group(function(){
            Route::get('/', [\App\Http\Controllers\PostingController::class, 'index'])->name('index');
            Route::post('/preview', [\App\Http\Controllers\PostingController::class, 'preview'])->name('preview');
            Route::post('/commit', [\App\Http\Controllers\PostingController::class, 'commit'])->name('commit');
        });

        // Journals (Credit/Debit)
        Route::prefix('journals')->name('journals.')->group(function(){
            Route::get('/create', [\App\Http\Controllers\JournalController::class, 'create'])->name('create');
            Route::post('/store',  [\App\Http\Controllers\JournalController::class, 'store'])->name('store');
            
        });

        // Journals (Credit/Debit) — bulk
        Route::get('journals/bulk',  [\App\Http\Controllers\JournalController::class, 'bulkForm'])->name('journals.bulk.form');
        Route::post('journals/bulk', [\App\Http\Controllers\JournalController::class, 'bulkImport'])->name('journals.bulk.import');
        Route::get('journals/template', [\App\Http\Controllers\JournalController::class, 'template'])->name('journals.bulk.template');

        // Invoice line editing
        Route::post('invoices/{invoice}/items/{item}/update', [\App\Http\Controllers\InvoiceController::class,'updateItem'])
            ->name('invoices.items.update');

    });

});

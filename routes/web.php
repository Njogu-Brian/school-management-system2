<?php

use Illuminate\Support\Facades\Route;

// Auth + Home
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Modules
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DropOffPointController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParentInfoController;
use App\Http\Controllers\OnlineAdmissionController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentCategoryController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CommunicationTemplateController;
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\VoteheadController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\InvoiceAdjustmentController;
use App\Http\Controllers\OptionalFeeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\AttendanceNotificationController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('welcome'));

// Custom login (AuthController)
Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

// Public student search
Route::get('/students/search', [StudentController::class, 'search'])
    ->name('students.search');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |---------------------- Dashboards ----------------------
    */
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');

    /*
    |---------------------- Attendance ----------------------
    */
        Route::prefix('attendance')->group(function () {
        Route::get('/mark', [AttendanceController::class, 'markForm'])->name('attendance.mark.form');
        Route::post('/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
        Route::get('/records', [AttendanceController::class, 'records'])->name('attendance.records');
        Route::get('/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::post('/update/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::get('notify', [AttendanceNotificationController::class, 'notifyForm'])->name('attendance.notifications.notify');
        Route::post('notify/send', [AttendanceNotificationController::class, 'sendNotify'])->name('attendance.notifications.notify.send');
        Route::resource('/', AttendanceNotificationController::class)->except(['show']);
        
    });
    /*
    |---------------------- Attendance Reports ----------------------
    */
    Route::prefix('attendance/notifications')->group(function () {
        Route::get('/', [AttendanceNotificationController::class, 'index'])->name('attendance.notifications.index');
        Route::get('/create', [AttendanceNotificationController::class, 'create'])->name('attendance.notifications.create');
        Route::post('/', [AttendanceNotificationController::class, 'store'])->name('attendance.notifications.store');
        Route::get('/{id}/edit', [AttendanceNotificationController::class, 'edit'])->name('attendance.notifications.edit');
        Route::put('/{id}', [AttendanceNotificationController::class, 'update'])->name('attendance.notifications.update');
        Route::delete('/{id}', [AttendanceNotificationController::class, 'destroy'])->name('attendance.notifications.destroy');

        Route::get('/notify', [AttendanceNotificationController::class, 'notifyForm'])->name('attendance.notifications.notify.form');
        Route::post('/notify', [AttendanceNotificationController::class, 'notifySend'])->name('attendance.notifications.notify.send');
    });

    /*
    |---------------------- Transport ----------------------
    */
    Route::prefix('transport')->name('transport.')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');

        // AJAX/data helpers
        Route::get('/routes/{route}/data', [TransportController::class, 'getRouteData'])->name('routes.data');
        Route::get('/routes/{route}/dropoff-points', [TransportController::class, 'getDropOffPoints'])->name('routes.dropoffs');
        Route::get('/routes/{route}/vehicles', [TransportController::class, 'getVehicles'])->name('routes.vehicles');

        // Actions
        Route::post('/assign-driver', [TransportController::class, 'assignDriver'])->name('assign.driver');
        Route::post('/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('assign.student');
        Route::post('/routes/{route}/assign-vehicle', [RouteController::class, 'assignVehicle'])->name('routes.assignVehicle');

        // Resources
        Route::resource('routes', RouteController::class)->except(['show']);
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
    Route::prefix('staff')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/{id}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('/{id}/archive', [StaffController::class, 'archive'])->name('staff.archive');
        Route::post('/{id}/restore', [StaffController::class, 'restore'])->name('staff.restore');
        Route::get('/upload', [StaffController::class, 'showUploadForm'])->name('staff.upload.form');
        Route::post('/upload', [StaffController::class, 'handleUpload'])->name('staff.upload.handle');
    });

    /*
    |---------------------- Lookups (Roles, Departments, Job Titles, Custom Fields) ----------------------
    */

    Route::prefix('lookups')->group(function () {
        Route::get('/', [\App\Http\Controllers\LookupController::class, 'index'])->name('lookups.index');

        Route::post('/roles', [\App\Http\Controllers\LookupController::class, 'storeRole'])->name('lookups.roles.store');
        Route::delete('/roles/{id}', [\App\Http\Controllers\LookupController::class, 'deleteRole'])->name('lookups.roles.delete');

        Route::post('/departments', [\App\Http\Controllers\LookupController::class, 'storeDepartment'])->name('lookups.departments.store');
        Route::delete('/departments/{id}', [\App\Http\Controllers\LookupController::class, 'deleteDepartment'])->name('lookups.departments.delete');

        Route::post('/job-titles', [\App\Http\Controllers\LookupController::class, 'storeJobTitle'])->name('lookups.jobtitles.store');
        Route::delete('/job-titles/{id}', [\App\Http\Controllers\LookupController::class, 'deleteJobTitle'])->name('lookups.jobtitles.delete');

        Route::post('/custom-fields', [\App\Http\Controllers\LookupController::class, 'storeCustomField'])->name('lookups.customfields.store');
        Route::delete('/custom-fields/{id}', [\App\Http\Controllers\LookupController::class, 'deleteCustomField'])->name('lookups.customfields.delete');
    });

    /*
    |---------------------- Students ----------------------
    */
    Route::resource('students', StudentController::class)->except(['destroy', 'show']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');

    Route::get('/students/bulk-upload', [StudentController::class, 'bulkForm'])->name('students.bulk');
    Route::post('/students/bulk-parse', [StudentController::class, 'bulkParse'])->name('students.bulk.parse');
    Route::post('/students/bulk-import', [StudentController::class, 'bulkImport'])->name('students.bulk.import');
    Route::get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])->name('students.bulk.template');

    /*
    |---------------------- Academics ----------------------
    */
    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::resource('streams', StreamController::class)->except(['show']);
    Route::resource('student-categories', StudentCategoryController::class)->except(['show']);
    Route::post('/get-streams', [StudentController::class, 'getStreams'])->name('students.getStreams');

    /*
    |---------------------- Parents ----------------------
    */
    Route::resource('parent-info', ParentInfoController::class)->except(['show']);

    /*
    |---------------------- Online Admissions ----------------------
    */
    Route::get('/online-admissions', [OnlineAdmissionController::class, 'index'])->name('online-admissions.index');
    Route::post('/online-admissions/approve/{id}', [OnlineAdmissionController::class, 'approve'])->name('online-admissions.approve');
    Route::post('/online-admissions/reject/{id}', [OnlineAdmissionController::class, 'reject'])->name('online-admissions.reject');
    Route::get('/admission-form', [OnlineAdmissionController::class, 'showForm'])->name('online-admission.form');
    Route::post('/admission-form', [OnlineAdmissionController::class, 'submitForm'])->name('online-admission.submit');

    /*
    /*
    |---------------------- Communication ----------------------
    */
    Route::prefix('communication')->group(function () {
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

        // Unified templates (replaces email-templates and sms-templates)
        Route::resource('communication-templates', CommunicationTemplateController::class)->parameters([
            'communication-templates' => 'communication_template'
        ])->except(['show']);
    });


    Route::get('/api/students/search', [StudentController::class, 'search'])
        ->name('api.students.search');

    /*
    |---------------------- Finance ----------------------
    */
    Route::prefix('finance')->name('finance.')->group(function () {
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

            // Excel Import
            Route::get('/import', [InvoiceController::class, 'importForm'])->name('import.form');
            Route::post('/import', [InvoiceController::class, 'import'])->name('import');

            // Credit & Debit Notes Import
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

        // Credit & Debit Notes
        Route::get('credits/create', [CreditNoteController::class, 'create'])->name('credits.create');
        Route::post('credits/store', [CreditNoteController::class, 'store'])->name('credits.store');
        Route::get('debits/create', [DebitNoteController::class, 'create'])->name('debits.create');
        Route::post('debits/store', [DebitNoteController::class, 'store'])->name('debits.store');
    });

    /*
    |---------------------- Settings ----------------------
    */
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/update-branding', [SettingController::class, 'updateBranding'])->name('settings.update.branding');
        Route::post('/update-general', [SettingController::class, 'updateSettings'])->name('settings.update.general');
        Route::post('/update-regional', [SettingController::class, 'updateRegional'])->name('settings.update.regional');
        Route::post('/update-system', [SettingController::class, 'updateSystem'])->name('settings.update.system');
        Route::post('/id-settings', [SettingController::class, 'updateIdSettings'])->name('settings.ids.save');

        // Roles & Permissions
        Route::get('/role-permissions', [RolePermissionController::class, 'listRoles'])->name('settings.role_permissions');
        Route::get('/role-permissions/edit/{role}', [RolePermissionController::class, 'index'])->name('permissions.edit');
        Route::post('/role-permissions/update/{role}', [RolePermissionController::class, 'update'])->name('permissions.update');

        // Academic Config
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('terms', TermController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Home Redirect
|--------------------------------------------------------------------------
*/

Route::get('/home', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    $user->load('roles');

    if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');
    if ($user->hasRole('teacher')) return redirect()->route('teacher.dashboard');
    if ($user->hasRole('student')) return redirect()->route('student.dashboard');

    return abort(403);
})->middleware('auth')->name('home');

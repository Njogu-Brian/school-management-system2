<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Auth + Home
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;

// Modules
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\KitchenController;
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
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\SMSTemplateController;
use App\Http\Controllers\CommunicationAnnouncementController;

use App\Http\Controllers\VoteheadController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\InvoiceAdjustmentController;

use App\Http\Controllers\SettingController;
use App\Http\Controllers\RolePermissionController;

Route::get('/', fn () => view('welcome'));

Auth::routes();
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');


// ===================== AUTHENTICATED ROUTES =====================
Route::middleware(['auth'])->group(function () {

    // ===================== DASHBOARDS =====================
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');


    // ===================== ATTENDANCE =====================
    Route::prefix('attendance')->group(function () {
        Route::get('/mark', [AttendanceController::class, 'showForm'])->name('attendance.mark.form');
        Route::post('/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
        Route::get('/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::post('/update/{id}', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');
    });


    // ===================== KITCHEN =====================
    Route::get('/notify-kitchen', [KitchenController::class, 'showForm'])->name('notify-kitchen');
    Route::post('/notify-kitchen', [KitchenController::class, 'notifyKitchen'])->name('notify-kitchen.submit');


    // ===================== TRANSPORT =====================
    Route::resource('routes', RouteController::class)->except(['show']);
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::resource('trips', TripController::class);
    Route::resource('dropoffpoints', DropOffPointController::class);
    Route::resource('student_assignments', StudentAssignmentController::class);

    Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
    Route::get('/get-route-data/{routeId}', [TransportController::class, 'getRouteData'])->name('get.route.data');
    Route::post('/transport/assign-driver', [TransportController::class, 'assignDriver'])->name('transport.assign.driver');
    Route::post('/transport/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('transport.assign.student');
    Route::post('/routes/{route}/assign-vehicle', [RouteController::class, 'assignVehicle'])->name('routes.assignVehicle');


    // ===================== STAFF =====================
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


    // ===================== STUDENTS =====================
    Route::resource('students', StudentController::class)->except(['destroy', 'show']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');

    Route::get('/students/bulk-upload', [StudentController::class, 'bulkForm'])->name('students.bulk');
    Route::post('/students/bulk-parse', [StudentController::class, 'bulkParse'])->name('students.bulk.parse');
    Route::post('/students/bulk-import', [StudentController::class, 'bulkImport'])->name('students.bulk.import');
    Route::get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])->name('students.bulk.template');


    // ===================== ACADEMICS =====================
    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::resource('streams', StreamController::class)->except(['show']);
    Route::resource('student-categories', StudentCategoryController::class)->except(['show']);
    Route::post('/get-streams', [StudentController::class, 'getStreams'])->name('students.getStreams');


    // ===================== PARENTS =====================
    Route::resource('parent-info', ParentInfoController::class)->except(['show']);


    // ===================== ONLINE ADMISSIONS =====================
    Route::get('/online-admissions', [OnlineAdmissionController::class, 'index'])->name('online-admissions.index');
    Route::post('/online-admissions/approve/{id}', [OnlineAdmissionController::class, 'approve'])->name('online-admissions.approve');
    Route::post('/online-admissions/reject/{id}', [OnlineAdmissionController::class, 'reject'])->name('online-admissions.reject');
    Route::get('/admission-form', [OnlineAdmissionController::class, 'showForm'])->name('online-admission.form');
    Route::post('/admission-form', [OnlineAdmissionController::class, 'submitForm'])->name('online-admission.submit');


    // ===================== COMMUNICATION =====================
    Route::prefix('communication')->group(function () {
        Route::get('send-email', [CommunicationController::class, 'createEmail'])->name('communication.send.email');
        Route::post('send-email', [CommunicationController::class, 'sendEmail'])->name('communication.send.email.submit');

        Route::get('send-sms', [CommunicationController::class, 'createSMS'])->name('communication.send.sms');
        Route::post('send-sms', [CommunicationController::class, 'sendSMS'])->name('communication.send.sms.submit');

        Route::get('logs', [CommunicationController::class, 'logs'])->name('communication.logs');
        Route::get('logs/scheduled', [CommunicationController::class, 'logsScheduled'])->name('communication.logs.scheduled');

        // Announcements
        Route::resource('announcements', CommunicationAnnouncementController::class)->except(['show']);

        // Email Templates
        Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

        // SMS Templates
        Route::resource('sms-templates', SMSTemplateController::class)->except(['show']);
    });

    // ===================== FINANCE MODULE =====================
    Route::prefix('finance')->name('finance.')->group(function () {

        // ========== VOTEHEADS ==========
        Route::resource('voteheads', VoteheadController::class)->except(['show']);

        // ========== FEE STRUCTURES ==========
        Route::get('fee-structures/manage', [FeeStructureController::class, 'manage'])->name('fee-structures.manage');
        Route::post('fee-structures/manage', [FeeStructureController::class, 'save'])->name('fee-structures.save');
        Route::post('fee-structures/replicate', [FeeStructureController::class, 'replicateTo'])->name('fee-structures.replicate');

        // ========== INVOICES ==========
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

        // ========== PAYMENTS ==========
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments/store', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/receipt/{payment}', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

        // ========== FEE STATEMENTS ==========
        Route::get('statements/{student}', [StatementController::class, 'show'])->name('statements.show');

        // ========== CREDIT & DEBIT NOTES ==========
        Route::get('credits/create', [CreditNoteController::class, 'create'])->name('credits.create');
        Route::post('credits/store', [CreditNoteController::class, 'store'])->name('credits.store');

        Route::get('debits/create', [DebitNoteController::class, 'create'])->name('debits.create');
        Route::post('debits/store', [DebitNoteController::class, 'store'])->name('debits.store');
    });


    // ===================== SETTINGS =====================
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

}); // End of auth group


// ===================== HOME REDIRECT =====================
Route::get('/home', function () {
    $user = auth()->user();
    $user->load('roles');

    if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');
    if ($user->hasRole('teacher')) return redirect()->route('teacher.dashboard');
    if ($user->hasRole('student')) return redirect()->route('student.dashboard');

    return abort(403);
})->middleware('auth')->name('home');

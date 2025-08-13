<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentCategoryController;
use App\Http\Controllers\ParentInfoController;
use App\Http\Controllers\OnlineAdmissionController;
use App\Http\Controllers\DropOffPointController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CommunicationTemplateController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\SMSTemplateController;
use App\Http\Controllers\SettingController; 
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\VoteheadController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\FeeStructureController;

Route::get('/', fn () => view('welcome'));

Auth::routes();
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// ================== AUTHENTICATED ROUTES ==================
Route::middleware(['auth'])->group(function () {

    // ✅ Dashboard
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');

    // ✅ Attendance
    Route::get('/attendance/mark', [AttendanceController::class, 'showForm'])->name('attendance.mark.form');
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::get('/attendance/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::post('/attendance/update/{id}', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');

    // ✅ Kitchen
    Route::get('/notify-kitchen', [KitchenController::class, 'showForm'])->name('notify-kitchen');
    Route::post('/notify-kitchen', [KitchenController::class, 'notifyKitchen'])->name('notify-kitchen.submit');

    // ✅ Transport
    Route::resource('routes', RouteController::class)->except(['show']);
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::resource('trips', TripController::class);
    Route::resource('dropoffpoints', DropOffPointController::class);
    Route::resource('student_assignments', StudentAssignmentController::class);
    Route::get('/get-route-data/{routeId}', [TransportController::class, 'getRouteData'])->name('get.route.data');
    Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
    Route::post('/transport/assign-driver', [TransportController::class, 'assignDriver'])->name('transport.assign.driver');
    Route::post('/transport/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('transport.assign.student');
    Route::post('/routes/{route}/assign-vehicle', [RouteController::class, 'assignVehicle'])->name('routes.assignVehicle');

    // ✅ Staff
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

    // ✅ Students
    Route::resource('students', StudentController::class)->except(['destroy', 'show']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('students.edit');

    //archive&restore students
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');

    //bulk upload students
    Route::get('/students/bulk-upload', [StudentController::class, 'bulkForm'])->name('students.bulk');
    Route::post('/students/bulk-parse', [StudentController::class, 'bulkParse'])->name('students.bulk.parse');
    Route::post('/students/bulk-import', [StudentController::class, 'bulkImport'])->name('students.bulk.import');
    Route::get('/students/bulk-template', [StudentController::class, 'bulkTemplate'])->name('students.bulk.template');

    // ✅ Academics
    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::resource('streams', StreamController::class)->except(['show']);
    Route::resource('student-categories', StudentCategoryController::class)->except(['show']);
    Route::post('/get-streams', [StudentController::class, 'getStreams'])->name('students.getStreams');

    // ✅ Parents
    Route::resource('parent-info', ParentInfoController::class)->except(['show']);

    // ✅ Online Admission
    Route::get('/online-admissions', [OnlineAdmissionController::class, 'index'])->name('online-admissions.index');
    Route::post('/online-admissions/approve/{id}', [OnlineAdmissionController::class, 'approve'])->name('online-admissions.approve');
    Route::post('/online-admissions/reject/{id}', [OnlineAdmissionController::class, 'reject'])->name('online-admissions.reject');
    Route::get('/admission-form', [OnlineAdmissionController::class, 'showForm'])->name('online-admission.form');
    Route::post('/admission-form', [OnlineAdmissionController::class, 'submitForm'])->name('online-admission.submit');

   // ✅ Communication Routes
    Route::prefix('communication')->group(function () {
        Route::get('send-email', [CommunicationController::class, 'createEmail'])->name('communication.send.email');
        Route::post('send-email', [CommunicationController::class, 'sendEmail'])->name('communication.send.email.submit');

        Route::get('send-sms', [CommunicationController::class, 'createSMS'])->name('communication.send.sms');
        Route::post('send-sms', [CommunicationController::class, 'sendSMS'])->name('communication.send.sms.submit');

        Route::get('logs', [CommunicationController::class, 'logs'])->name('communication.logs');
        Route::get('logs/scheduled', [CommunicationController::class, 'logsScheduled'])->name('communication.logs.scheduled');

        // ✅ Announcements
        Route::get('announcements', [CommunicationAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('announcements/create', [CommunicationAnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('announcements', [CommunicationAnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('announcements/{announcement}/edit', [CommunicationAnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('announcements/{announcement}', [CommunicationAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('announcements/{announcement}', [CommunicationAnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // ✅ Email Templates
        Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

        // ✅ SMS Templates
        Route::resource('sms-templates', SMSTemplateController::class)->except(['show']);
        Route::get('/sms-templates/{id}/edit', [SmsTemplateController::class, 'edit'])->name('sms.templates.edit');
        Route::put('/sms-templates/{id}', [SmsTemplateController::class, 'update'])->name('sms.templates.update');
    });

    // ✅ finance
        Route::middleware(['auth'])->group(function () {
            Route::resource('voteheads', VoteheadController::class);
        // routes/web.php
        Route::resource('fee-structures', FeeStructureController::class);
        Route::resource('fee-structures', App\Http\Controllers\FeeStructureController::class);
        Route::post('fee-structures/charge', [FeeStructureController::class, 'chargeStudents'])->name('fee-structures.charge');

        });


    // ✅ Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/update-branding', [SettingController::class, 'updateBranding'])->name('settings.update.branding');
        Route::post('/update-general', [SettingController::class, 'updateSettings'])->name('settings.update.general');
        Route::post('/update-regional', [SettingController::class, 'updateRegional'])->name('settings.update.regional');
        Route::post('/update-system', [SettingController::class, 'updateSystem'])->name('settings.update.system');

        // ✅ Roles & Permissions
        Route::get('/role-permissions', [RolePermissionController::class, 'listRoles'])->name('settings.role_permissions');
        Route::get('/role-permissions/edit/{role}', [RolePermissionController::class, 'index'])->name('permissions.edit');
        Route::post('/role-permissions/update/{role}', [RolePermissionController::class, 'update'])->name('permissions.update');

        Route::post('/id-settings', [SettingController::class, 'updateIdSettings'])->name('settings.ids.save');

        Route::middleware(['auth'])->group(function () {
            Route::resource('academic-years', AcademicYearController::class);
            Route::resource('terms', TermController::class);
        });

    
    }); // Close settings prefix group

}); // ✅ <-- Add this to close the MAIN auth middleware group

// ================== FALLBACK & UTILITIES ==================
Route::get('/home', function () {
    $user = auth()->user();
    $user->load('roles');

    if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');
    if ($user->hasRole('teacher')) return redirect()->route('teacher.dashboard');
    if ($user->hasRole('student')) return redirect()->route('student.dashboard');

    return abort(403);
})->middleware('auth')->name('home');

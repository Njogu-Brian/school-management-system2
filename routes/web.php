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
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{id}/archive', [StaffController::class, 'archive'])->name('staff.archive');
    Route::post('/staff/{id}/restore', [StaffController::class, 'restore'])->name('staff.restore');

    // ✅ Students
    Route::resource('students', StudentController::class)->except(['destroy']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('students.edit');

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

    // ✅ Communication (Email/SMS Send, Logs, Dashboard)
    Route::get('/communication/send-email', [CommunicationController::class, 'createEmail'])->name('communication.send.email');
    Route::post('/communication/send-email', [CommunicationController::class, 'sendEmail'])->name('communication.send.email.submit');

    Route::get('/communication/send-sms', [CommunicationController::class, 'createSMS'])->name('communication.send.sms');
    Route::post('/communication/send-sms', [CommunicationController::class, 'sendSMS'])->name('communication.send.sms.submit');

    Route::get('/communication/logs', [CommunicationController::class, 'logs'])->name('communication.logs');
    Route::get('/communication/logs/scheduled', [CommunicationController::class, 'logsScheduled'])->name('communication.logs.scheduled');

    // ✅ Announcements
    Route::prefix('communication')->middleware('auth')->group(function () {
        Route::get('announcements', [CommunicationAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('announcements/create', [CommunicationAnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('announcements', [CommunicationAnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('announcements/{announcement}/edit', [CommunicationAnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('announcements/{announcement}', [CommunicationAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('announcements/{announcement}', [CommunicationAnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });


    // ✅ Email Templates
    Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

    // ✅ SMS Templates
    Route::resource('sms-templates', SMSTemplateController::class)->except(['show']);
    Route::get('/sms-templates/{id}/edit', [SmsTemplateController::class, 'edit'])->name('sms.templates.edit');
    Route::put('/sms-templates/{id}', [SmsTemplateController::class, 'update'])->name('sms.templates.update');

    // ✅ Settings
    Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/update-branding', [SettingController::class, 'updateBranding'])->name('settings.update.branding');
    Route::post('/update-general', [SettingController::class, 'updateSettings'])->name('settings.update.general');
    Route::post('/update-regional', [SettingController::class, 'updateRegional'])->name('settings.update.regional');
    Route::post('/update-system', [SettingController::class, 'updateSystem'])->name('settings.update.system');
    Route::get('/role-permissions', [SettingController::class, 'rolePermissions'])->name('settings.role_permissions');
    Route::post('/role-permissions', [SettingController::class, 'updateRolePermissions'])->name('settings.update_role_permissions');

    });

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

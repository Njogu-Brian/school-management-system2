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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {

    // ✅ Attendance Routes
    Route::get('/attendance/mark', [AttendanceController::class, 'showForm'])->name('attendance.mark.form');
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::get('/attendance/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::post('/attendance/update/{id}', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');

    // ✅ Notify Kitchen
    Route::get('/notify-kitchen', [KitchenController::class, 'showForm'])->name('notify-kitchen');
    Route::post('/notify-kitchen', [KitchenController::class, 'notifyKitchen'])->name('notify-kitchen.submit');

    // ✅ Transport Module
    Route::resource('routes', RouteController::class)->except(['show']);
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
    Route::post('/transport/assign-driver', [TransportController::class, 'assignDriver'])->name('transport.assign.driver');
    Route::post('/transport/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('transport.assign.student');

    // ✅ Staff Management
    Route::resource('staff', StaffController::class);
    Route::post('/staff/{id}/archive', [StaffController::class, 'archive'])->name('staff.archive');
    Route::post('/staff/{id}/restore', [StaffController::class, 'restore'])->name('staff.restore');

    // ✅ Student Management
    Route::resource('students', StudentController::class)->except(['destroy']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');

    // ✅ Academic Management
    Route::resource('classrooms', ClassroomController::class)->except(['show']);
    Route::resource('streams', StreamController::class)->except(['show']);
    Route::resource('student-categories', StudentCategoryController::class)->except(['show']);

    // ✅ Parent Management
    Route::resource('parent-info', ParentInfoController::class)->except(['show']);
});

/*------------------------------------------
| Admin Routes
------------------------------------------*/
Route::middleware(['auth', 'user-access:admin'])->group(function () {
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
});

/*------------------------------------------
| Teacher Routes
------------------------------------------*/
Route::middleware(['auth', 'user-access:teacher'])->group(function () {
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
});

/*------------------------------------------
| Student Routes
------------------------------------------*/
Route::middleware(['auth', 'user-access:student'])->group(function () {
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');
});

/*------------------------------------------
| Fallback Home Redirection
------------------------------------------*/
Route::get('/home', function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $user->load('roles');

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('teacher')) {
        return redirect()->route('teacher.dashboard');
    } elseif ($user->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    return abort(403);
})->middleware('auth')->name('home');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
// ✅ Redirect users based on role
Route::middleware(['auth'])->group(function () {
    // Route::get('/dashboard', function () { if (auth()->check()) { return auth()->user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('teacher.dashboard'); } return redirect()->route('login'); })->name('dashboard');

    // ✅ Attendance Routes
    Route::get('/attendance/mark', [AttendanceController::class, 'showForm'])->name('attendance.mark.form');
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
    Route::get('/attendance/edit/{id}', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::post('/attendance/update/{id}', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');

    // ✅ Notify Kitchen
    Route::get('/notify-kitchen', [KitchenController::class, 'showForm'])->name('notify-kitchen');
    Route::post('/notify-kitchen', [KitchenController::class, 'notifyKitchen'])->name('notify-kitchen.submit');

    // ✅ Transport Module
    Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
    Route::post('/transport/assign-driver', [TransportController::class, 'assignDriver'])->name('transport.assign.driver');
    Route::post('/transport/assign-student', [TransportController::class, 'assignStudentToRoute'])->name('transport.assign.student');

    // ✅ Student Management (Only Admins)
    Route::resource('students', StudentController::class)->except(['destroy']);
    Route::post('/students/{id}/archive', [StudentController::class, 'archive'])->name('students.archive');
    Route::post('/students/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
});

/*------------------------------------------
--------------------------------------------
All Admin Routes
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:admin'])->group(function () {
    Route::get('/admin/home', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
});
/*------------------------------------------
--------------------------------------------
All Teachers Routes
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:teacher'])->group(function () {
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard'])->name('teacher.dashboard');
});

/*------------------------------------------
--------------------------------------------
All Teachers Routes
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:student'])->group(function () {
    Route::get('/student/dashboard', [DashboardController::class, 'studentDashboard'])->name('student.dashboard');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');



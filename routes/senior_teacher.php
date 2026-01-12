<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\SeniorTeacher\SeniorTeacherController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Academics\ExamMarkController;
use App\Http\Controllers\Academics\ReportCardController;
use App\Http\Controllers\Academics\ReportCardSkillController;
use App\Http\Controllers\Academics\HomeworkController;
use App\Http\Controllers\Academics\StudentBehaviourController;
use App\Http\Controllers\Academics\TimetableController;
use App\Http\Controllers\Academics\StudentDiaryController;
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\Teacher\SalaryController;
use App\Http\Controllers\Teacher\LeaveController;
use App\Http\Controllers\Teacher\AdvanceRequestController;

Route::middleware(['auth', 'role:Super Admin|Admin|Senior Teacher'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Senior Teacher Dashboard & Main Functions
    |--------------------------------------------------------------------------
    */
    Route::get('/senior-teacher/home', [SeniorTeacherController::class, 'dashboard'])
        ->name('senior_teacher.dashboard');

    Route::prefix('senior-teacher')->name('senior_teacher.')->group(function () {
        
        // Supervisory relationships
        Route::get('/supervised-classrooms', [SeniorTeacherController::class, 'supervisedClassrooms'])
            ->name('supervised_classrooms');
        
        Route::get('/supervised-staff', [SeniorTeacherController::class, 'supervisedStaff'])
            ->name('supervised_staff');
        
        // Students (supervised + assigned)
        Route::get('/students', [SeniorTeacherController::class, 'students'])
            ->name('students.index');
        
        Route::get('/students/{student}', [SeniorTeacherController::class, 'studentShow'])
            ->name('students.show');
        
        // Fee balances (view only)
        Route::get('/fee-balances', [SeniorTeacherController::class, 'feeBalances'])
            ->name('fee_balances');
    });

    /*
    |--------------------------------------------------------------------------
    | Attendance (View + Mark for supervised/assigned classes)
    |--------------------------------------------------------------------------
    */
    Route::prefix('attendance')->group(function () {
        Route::get('/mark', [AttendanceController::class, 'markForm'])
            ->name('attendance.mark.form');

        Route::post('/mark', [AttendanceController::class, 'mark'])
            ->name('attendance.mark');

        Route::get('/records', [AttendanceController::class, 'records'])
            ->name('attendance.records');
    });

    /*
    |--------------------------------------------------------------------------
    | Exam Marks
    |--------------------------------------------------------------------------
    */
    Route::prefix('exam-marks')->group(function () {
        Route::get('/', [ExamMarkController::class, 'index'])
            ->name('academics.exam-marks.index');

        Route::get('/bulk', [ExamMarkController::class, 'bulkForm'])
            ->name('academics.exam-marks.bulk.form');

        Route::post('/bulk', [ExamMarkController::class, 'bulkEdit'])
            ->name('academics.exam-marks.bulk.edit');

        Route::get('/bulk/view', [ExamMarkController::class, 'bulkEditView'])
            ->name('academics.exam-marks.bulk.edit.view');

        Route::post('/bulk/store', [ExamMarkController::class, 'bulkStore'])
            ->name('academics.exam-marks.bulk.store');

        Route::get('/{exam_mark}/edit', [ExamMarkController::class, 'edit'])
            ->name('academics.exam-marks.edit');

        Route::put('/{exam_mark}', [ExamMarkController::class, 'update'])
            ->name('academics.exam-marks.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Report Cards
    |--------------------------------------------------------------------------
    */
    Route::prefix('academics')->group(function () {

        // View report cards
        Route::get('report_cards', [ReportCardController::class, 'index'])
            ->name('academics.report_cards.index');

        Route::get('report_cards/{report_card}', [ReportCardController::class, 'show'])
            ->name('academics.report_cards.show');

        // Skills (per report)
        Route::get('report_cards/{report_card}/skills', [ReportCardSkillController::class,'index'])
            ->name('academics.report_cards.skills.index');

        Route::get('report_cards/{report_card}/skills/create', [ReportCardSkillController::class,'create'])
            ->name('academics.report_cards.skills.create');

        Route::post('report_cards/{report_card}/skills', [ReportCardSkillController::class,'store'])
            ->name('academics.report_cards.skills.store');

        Route::get('report_cards/{report_card}/skills/{skill}/edit', [ReportCardSkillController::class,'edit'])
            ->name('academics.report_cards.skills.edit');

        Route::put('report_cards/{report_card}/skills/{skill}', [ReportCardSkillController::class,'update'])
            ->name('academics.report_cards.skills.update');

        Route::delete('report_cards/{report_card}/skills/{skill}', [ReportCardSkillController::class,'destroy'])
            ->name('academics.report_cards.skills.destroy');

        // Remarks
        Route::post('report_cards/{report_card}/remarks', [ReportCardController::class,'update'])
            ->name('academics.report_cards.remarks.save');

        /*
        |--------------------------------------------------------------------------
        | Homework (Full CRUD for supervised/assigned classes)
        |--------------------------------------------------------------------------
        */
        Route::resource('homework', HomeworkController::class)
            ->names('academics.homework');

        /*
        |--------------------------------------------------------------------------
        | Digital Diaries
        |--------------------------------------------------------------------------
        */
        Route::prefix('diaries')->name('diaries.')->group(function () {
            Route::get('/', [StudentDiaryController::class, 'index'])->name('index');
            Route::get('/{diary}', [StudentDiaryController::class, 'show'])->name('show');
            Route::post('/{diary}/entries', [StudentDiaryController::class, 'storeEntry'])->name('entries.store');
            Route::post('/entries/bulk', [StudentDiaryController::class, 'bulkStore'])->name('entries.bulk-store');
        });

        /*
        |--------------------------------------------------------------------------
        | Student Behaviours (Full CRUD)
        |--------------------------------------------------------------------------
        */
        Route::resource('student-behaviours', StudentBehaviourController::class)
            ->names('academics.student-behaviours');
    });

    /*
    |--------------------------------------------------------------------------
    | Timetable (View and Edit)
    |--------------------------------------------------------------------------
    */
    Route::prefix('timetable')->name('senior_teacher.timetable.')->group(function () {
        Route::get('/', [TimetableController::class, 'index'])->name('index');
        Route::get('/classroom/{classroom}', [TimetableController::class, 'classroom'])->name('classroom');
        Route::get('/my-timetable', function() {
            $user = \Illuminate\Support\Facades\Auth::user();
            $staff = $user->staff;
            if (!$staff) {
                abort(403, 'No staff record found.');
            }
            return redirect()->route('academics.timetable.teacher', $staff);
        })->name('my-timetable');
    });

    /*
    |--------------------------------------------------------------------------
    | Salary & Payslips (Own only)
    |--------------------------------------------------------------------------
    */
    Route::prefix('salary')->name('senior_teacher.salary.')->group(function () {
        Route::get('/', [SalaryController::class, 'index'])->name('index');
        Route::get('/payslip/{record}', [SalaryController::class, 'payslip'])->name('payslip');
        Route::get('/payslip/{record}/download', [SalaryController::class, 'downloadPayslip'])->name('payslip.download');
    });

    /*
    |--------------------------------------------------------------------------
    | Advance Requests (Own only)
    |--------------------------------------------------------------------------
    */
    Route::prefix('advances')->name('senior_teacher.advances.')->group(function () {
        Route::get('/', [AdvanceRequestController::class, 'index'])->name('index');
        Route::get('/create', [AdvanceRequestController::class, 'create'])->name('create');
        Route::post('/', [AdvanceRequestController::class, 'store'])->name('store');
    });

    /*
    |--------------------------------------------------------------------------
    | Leaves (Request and view own)
    |--------------------------------------------------------------------------
    */
    Route::prefix('leaves')->name('senior_teacher.leave.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/{leaveRequest}', [LeaveController::class, 'show'])->name('show');
        Route::post('/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Announcements (View only)
    |--------------------------------------------------------------------------
    */
    Route::get('/announcements', [CommunicationAnnouncementController::class, 'index'])
        ->name('senior_teacher.announcements.index');

    /*
    |--------------------------------------------------------------------------
    | Events Calendar (View only)
    |--------------------------------------------------------------------------
    */
    // Events routes are defined in web.php, senior teachers can access them via role middleware
});


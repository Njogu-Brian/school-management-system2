<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Academics\ExamMarkController;
use App\Http\Controllers\Academics\ReportCardController;
use App\Http\Controllers\Academics\ReportCardSkillController;
use App\Http\Controllers\Academics\HomeworkController;
use App\Http\Controllers\Academics\StudentBehaviourController;
use App\Http\Controllers\Teacher\TransportController;
use App\Http\Controllers\Teacher\StudentsController;
use App\Http\Controllers\Teacher\SalaryController;
use App\Http\Controllers\Teacher\LeaveController;
use App\Http\Controllers\Academics\TimetableController;
use App\Http\Controllers\CommunicationAnnouncementController;
use App\Http\Controllers\EventCalendarController;
use App\Http\Controllers\Teacher\AdvanceRequestController;

Route::middleware(['auth', 'role:Super Admin|Admin|Secretary|Teacher|teacher'])->group(function () {

    // Dashboard
    Route::get('/teacher/home', [DashboardController::class, 'teacherDashboard'])
        ->name('teacher.dashboard');

    /*
    |---------------------- Attendance ----------------------
    | View + Mark (create) for assigned classes
    */
    Route::prefix('attendance')->group(function () {
        Route::get('/mark',    [AttendanceController::class, 'markForm'])
            ->name('attendance.mark.form');

        Route::post('/mark',   [AttendanceController::class, 'mark'])
            ->name('attendance.mark');

        Route::get('/records', [AttendanceController::class, 'records'])
            ->name('attendance.records');
    });

    /*
    |---------------------- Exam Marks ----------------------
    | Enter marks only when exam open (gate in controller)
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
    |---------------------- Report Cards ----------------------
    | View, edit skills, update remarks
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

        // Remarks (adjust to your method name)
        Route::post('report_cards/{report_card}/remarks', [ReportCardController::class,'update'])
            ->name('academics.report_cards.remarks.save');

        /*
        |---------------------- Homework ----------------------
        */
        Route::resource('homework', HomeworkController::class)
            ->names('academics.homework');

        /*
        |---------------------- Student Behaviours ----------------------
        */
        Route::resource('student-behaviours', StudentBehaviourController::class)
            ->only(['index','create','store','destroy'])
            ->names('academics.student-behaviours');
    });

    /*
    |---------------------- Students ----------------------
    | View assigned students details
    */
    Route::prefix('my-students')->name('teacher.students.')->group(function () {
        Route::get('/', [StudentsController::class, 'index'])->name('index');
        Route::get('/{student}', [StudentsController::class, 'show'])->name('show');
    });

    /*
    |---------------------- Transport ----------------------
    | View transport information for assigned students
    */
    Route::prefix('transport')->name('teacher.transport.')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');
        Route::get('/{student}', [TransportController::class, 'show'])->name('show');
        Route::get('/sheet/print', [TransportController::class, 'transportSheet'])->name('sheet.print');
    });

    /*
    |---------------------- Salary & Payslips ----------------------
    | View own salary and payslips
    */
    Route::prefix('salary')->name('teacher.salary.')->group(function () {
        Route::get('/', [SalaryController::class, 'index'])->name('index');
        Route::get('/payslip/{record}', [SalaryController::class, 'payslip'])->name('payslip');
        Route::get('/payslip/{record}/download', [SalaryController::class, 'downloadPayslip'])->name('payslip.download');
    });

    Route::prefix('advances')->name('teacher.advances.')->group(function () {
        Route::get('/', [AdvanceRequestController::class, 'index'])->name('index');
        Route::get('/create', [AdvanceRequestController::class, 'create'])->name('create');
        Route::post('/', [AdvanceRequestController::class, 'store'])->name('store');
    });

    /*
    |---------------------- Leaves ----------------------
    | Request leaves and view leave history
    */
    Route::prefix('leaves')->name('teacher.leave.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/{leaveRequest}', [LeaveController::class, 'show'])->name('show');
        Route::post('/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
    });

    /*
    |---------------------- Timetable ----------------------
    | View class and teacher timetables
    */
    Route::prefix('timetable')->name('teacher.timetable.')->group(function () {
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
    |---------------------- Announcements ----------------------
    | View announcements
    */
    Route::get('/announcements', [CommunicationAnnouncementController::class, 'index'])
        ->name('teacher.announcements.index');

    /*
    |---------------------- Events Calendar ----------------------
    | View events calendar (using main events routes)
    */
    // Events routes are defined in web.php, teachers can access them via role middleware
});

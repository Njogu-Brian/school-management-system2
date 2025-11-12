<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Academics\ExamMarkController;
use App\Http\Controllers\Academics\ReportCardController;
use App\Http\Controllers\Academics\ReportCardSkillController;
use App\Http\Controllers\Academics\HomeworkController;
use App\Http\Controllers\Academics\DiaryController;
use App\Http\Controllers\Academics\DiaryMessageController;
use App\Http\Controllers\Academics\StudentBehaviourController;

Route::middleware(['auth', 'role:Super Admin|Admin|Secretary|Teacher'])->group(function () {

    // Dashboard
    Route::get('/teacher/home', [DashboardController::class, 'teacherDashboard'])
        ->middleware('permission:dashboard.teacher.view')
        ->name('teacher.dashboard');

    /*
    |---------------------- Attendance ----------------------
    | View + Mark (create) for assigned classes
    */
    Route::prefix('attendance')->group(function () {
        Route::get('/mark',    [AttendanceController::class, 'markForm'])
            ->middleware('permission:attendance.create')
            ->name('attendance.mark.form');

        Route::post('/mark',   [AttendanceController::class, 'mark'])
            ->middleware('permission:attendance.create')
            ->name('attendance.mark');

        Route::get('/records', [AttendanceController::class, 'records'])
            ->middleware('permission:attendance.view')
            ->name('attendance.records');
    });

    /*
    |---------------------- Exam Marks ----------------------
    | Enter marks only when exam open (gate in controller)
    */
    Route::prefix('exam-marks')->group(function () {
        Route::get('/', [ExamMarkController::class, 'index'])
            ->middleware('permission:exam_marks.view')
            ->name('academics.exam-marks.index');

        Route::get('/bulk', [ExamMarkController::class, 'bulkForm'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.bulk.form');

        Route::post('/bulk', [ExamMarkController::class, 'bulkEdit'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.bulk.edit');

        Route::get('/bulk/view', [ExamMarkController::class, 'bulkEditView'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.bulk.edit.view');

        Route::post('/bulk/store', [ExamMarkController::class, 'bulkStore'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.bulk.store');

        Route::get('/{exam_mark}/edit', [ExamMarkController::class, 'edit'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.edit');

        Route::put('/{exam_mark}', [ExamMarkController::class, 'update'])
            ->middleware('permission:exam_marks.create')
            ->name('academics.exam-marks.update');
    });

    /*
    |---------------------- Report Cards ----------------------
    | View, edit skills, update remarks
    */
    Route::prefix('academics')->group(function () {

        // View report cards
        Route::get('report_cards', [ReportCardController::class, 'index'])
            ->middleware('permission:report_cards.view')
            ->name('academics.report_cards.index');

        Route::get('report_cards/{report_card}', [ReportCardController::class, 'show'])
            ->middleware('permission:report_cards.view')
            ->name('academics.report_cards.show');

        // Skills (per report)
        Route::get('report_cards/{report_card}/skills', [ReportCardSkillController::class,'index'])
            ->middleware('permission:report_card_skills.edit')
            ->name('academics.report_cards.skills.index');

        Route::post('report_cards/{report_card}/skills', [ReportCardSkillController::class,'store'])
            ->middleware('permission:report_card_skills.edit')
            ->name('academics.report_cards.skills.store');

        Route::put('report_cards/{report_card}/skills/{skill}', [ReportCardSkillController::class,'update'])
            ->middleware('permission:report_card_skills.edit')
            ->name('academics.report_cards.skills.update');

        Route::delete('report_cards/{report_card}/skills/{skill}', [ReportCardSkillController::class,'destroy'])
            ->middleware('permission:report_card_skills.edit')
            ->name('academics.report_cards.skills.destroy');

        // Remarks (adjust to your method name)
        Route::post('report_cards/{report_card}/remarks', [ReportCardController::class,'update'])
            ->middleware('permission:report_cards.remarks.edit')
            ->name('academics.report_cards.remarks.save');

        /*
        |---------------------- Homework ----------------------
        */
        Route::resource('homework', HomeworkController::class)
            ->middleware('permission:homework.view|homework.create|homework.edit')
            ->names('academics.homework');

        /*
        |---------------------- Digital Diaries ----------------------
        */
        Route::resource('diaries', DiaryController::class)
            ->middleware('permission:diaries.view|diaries.create|diaries.edit')
            ->names('academics.diaries');

        Route::post('diaries/{diary}/messages', [DiaryMessageController::class, 'store'])
            ->middleware('permission:diaries.edit')
            ->name('academics.diary.messages.store');

        /*
        |---------------------- Student Behaviours ----------------------
        */
        Route::resource('student-behaviours', StudentBehaviourController::class)
            ->only(['index','create','store','destroy'])
            ->middleware('permission:student_behaviours.view|student_behaviours.create|student_behaviours.edit')
            ->names('academics.student-behaviours');
    });
});

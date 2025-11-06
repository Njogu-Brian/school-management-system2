<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSchedule;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class ExamScheduleController extends Controller
{
    public function index(Exam $exam)
    {
        $schedules = $exam->schedules()->with(['subject','classroom'])
            ->orderBy('exam_date')->orderBy('start_time')->get();

        return view('academics.exam_schedules.index', [
            'exam' => $exam,
            'schedules' => $schedules,
            'subjects' => Subject::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r, Exam $exam)
    {
        $data = $r->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'min_mark' => 'nullable|numeric|min:0',
            'max_mark' => 'nullable|numeric|min:1',
            'weight' => 'nullable|numeric|min:0',
            'room' => 'nullable|string|max:255',
        ]);

        $exam->schedules()->create($data);
        return back()->with('success','Schedule added.');
    }

    public function update(Request $r, Exam $exam, ExamSchedule $examSchedule)
    {
        $data = $r->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'min_mark' => 'nullable|numeric|min:0',
            'max_mark' => 'nullable|numeric|min:1',
            'weight' => 'nullable|numeric|min:0',
            'room' => 'nullable|string|max:255',
        ]);

        $examSchedule->update($data);
        return back()->with('success','Schedule updated.');
    }

    public function destroy(Exam $exam, ExamSchedule $examSchedule)
    {
        $examSchedule->delete();
        return back()->with('success','Schedule deleted.');
    }
}

<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Timetable;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        $selectedClassroom = $request->filled('classroom_id') ? Classroom::find($request->classroom_id) : null;
        $selectedTeacher = $request->filled('teacher_id') ? Staff::find($request->teacher_id) : null;
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();

        $timetable = null;
        if ($selectedClassroom && $selectedYear && $selectedTerm) {
            $timetable = TimetableService::generateForClassroom(
                $selectedClassroom->id,
                $selectedYear->id,
                $selectedTerm->id
            );
        }

        return view('academics.timetable.index', compact(
            'classrooms', 'teachers', 'years', 'terms',
            'selectedClassroom', 'selectedTeacher', 'selectedYear', 'selectedTerm',
            'timetable'
        ));
    }

    public function classroom(Classroom $classroom, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $timetable = TimetableService::generateForClassroom($classroom->id, $yearId, $termId);
        $conflicts = TimetableService::checkConflicts($timetable);

        return view('academics.timetable.classroom', compact('timetable', 'conflicts', 'classroom'));
    }

    public function teacher(Staff $teacher, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $timetable = TimetableService::generateForTeacher($teacher->id, $yearId, $termId);

        return view('academics.timetable.teacher', compact('timetable', 'teacher'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $timetable = TimetableService::generateForClassroom(
            $validated['classroom_id'],
            $validated['academic_year_id'],
            $validated['term_id']
        );

        return view('academics.timetable.preview', compact('timetable'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'timetable' => 'required|array',
        ]);

        // Delete existing timetable
        Timetable::where('classroom_id', $validated['classroom_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->delete();

        // Save new timetable
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['subject_id']) && $data['subject_id']) {
                    Timetable::create([
                        'classroom_id' => $validated['classroom_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                        'term_id' => $validated['term_id'],
                        'day' => $day,
                        'period' => is_numeric($period) ? $period : 0,
                        'start_time' => $data['start'] ?? '08:00',
                        'end_time' => $data['end'] ?? '08:40',
                        'subject_id' => $data['subject_id'],
                        'staff_id' => $data['teacher_id'] ?? null,
                        'room' => $data['room'] ?? null,
                        'is_break' => in_array($period, ['Break', 'Lunch']),
                    ]);
                }
            }
        }

        return redirect()
            ->route('academics.timetable.classroom', $validated['classroom_id'])
            ->with('success', 'Timetable saved successfully.');
    }
}

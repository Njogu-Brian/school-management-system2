<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    public function index()
{
    $exams = Exam::with(['academicYear','term','classrooms','subjects'])
        ->latest()
        ->paginate(20);

    return view('academics.exams.index', compact('exams'));
}


    public function create()
    {
        return view('academics.exams.create', [
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'subjects'   => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'nullable|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'subject_id'       => 'nullable|exists:subjects,id',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
        ]);

       Exam::create($v + ['created_by' => Auth::id()]);

        return redirect()->route('academics.exams.index')->with('success','Exam created.');
    }

    public function edit(Exam $exam)
    {
        return view('academics.exams.edit', [
            'exam'      => $exam,
            'years'     => AcademicYear::orderByDesc('year')->get(),
            'terms'     => Term::orderBy('name')->get(),
            'classrooms'=> Classroom::orderBy('name')->get(),
            'subjects'  => Subject::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $v = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'   => 'required|in:physical,online',
            'starts_on'  => 'nullable|date',
            'ends_on'    => 'nullable|date|after_or_equal:starts_on',
            'max_marks'  => 'required|numeric|min:1',
            'weight'     => 'required|numeric|min:0|max:100',
            'status'     => 'required|in:draft,open,marking,moderation,approved,published,locked'
        ]);

        $exam->update($v);

        return redirect()->route('academics.exams.index')->with('success','Exam updated.');
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return back()->with('success','Exam deleted.');
    }

    public function timetable()
    {
        $papers = \App\Models\Academics\ExamPaper::with(['exam','subject','classroom','exam.term','exam.academicYear'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('exam_date');

        return view('academics.exams.timetable', compact('papers'));
    }

}

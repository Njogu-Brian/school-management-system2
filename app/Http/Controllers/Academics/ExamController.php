<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with(['subject','classroom','term'])->latest()->paginate(20);
        return view('academics.exams.index', compact('exams'));
    }

    public function create()
    {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        $years = AcademicYear::all();
        $terms = Term::all();
        return view('academics.exams.create', compact('subjects','classrooms','years','terms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'type'=>'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'=>'required|in:physical,online',
            'academic_year_id'=>'required|exists:academic_years,id',
            'term_id'=>'required|exists:terms,id',
            'classroom_id'=>'required|exists:classrooms,id',
            'subject_id'=>'required|exists:subjects,id',
            'starts_on'=>'nullable|date',
            'ends_on'=>'nullable|date|after_or_equal:starts_on',
            'max_marks'=>'required|numeric|min:1',
            'weight'=>'required|numeric|min:0|max:100',
        ]);

        Exam::create(array_merge(
            $request->all(),
            ['created_by'=>Auth::id()]
        ));

        return redirect()->route('academics.exams.index')->with('success','Exam created successfully.');
    }

    public function edit(Exam $exam)
    {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        $years = AcademicYear::all();
        $terms = Term::all();
        return view('academics.exams.edit', compact('exam','subjects','classrooms','years','terms'));
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'type'=>'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'=>'required|in:physical,online',
            'academic_year_id'=>'required|exists:academic_years,id',
            'term_id'=>'required|exists:terms,id',
            'classroom_id'=>'required|exists:classrooms,id',
            'subject_id'=>'required|exists:subjects,id',
            'starts_on'=>'nullable|date',
            'ends_on'=>'nullable|date|after_or_equal:starts_on',
            'max_marks'=>'required|numeric|min:1',
            'weight'=>'required|numeric|min:0|max:100',
        ]);

        $exam->update($request->all());

        return redirect()->route('academics.exams.index')->with('success','Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return redirect()->route('academics.exams.index')->with('success','Exam deleted.');
    }
}

<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamPaper;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class ExamPaperController extends Controller
{
    public function index(Exam $exam)
    {
        $papers = $exam->papers()->with(['subject','classroom'])->orderBy('exam_date')->orderBy('start_time')->get();
        return view('academics.exam_papers.index', compact('exam','papers'));
    }

    public function create(Exam $exam)
    {
        return view('academics.exam_papers.create', [
            'exam' => $exam,
            'subjects' => Subject::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'max_marks' => 'nullable|integer|min:1',
        ]);

        $exam->papers()->create($data);

        return redirect()->route('academics.exam-papers.index', $exam)->with('success', 'Exam paper added.');
    }

    public function edit(Exam $exam, ExamPaper $examPaper)
    {
        return view('academics.exam_papers.edit', [
            'exam' => $exam,
            'paper' => $examPaper,
            'subjects' => Subject::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam, ExamPaper $examPaper)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'max_marks' => 'nullable|integer|min:1',
        ]);

        $examPaper->update($data);

        return redirect()->route('academics.exam-papers.index', $exam)->with('success', 'Exam paper updated.');
    }

    public function destroy(Exam $exam, ExamPaper $examPaper)
    {
        $examPaper->delete();
        return redirect()->route('academics.exam-papers.index', $exam)->with('success', 'Exam paper deleted.');
    }
}

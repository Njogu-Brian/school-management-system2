<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamGrade;
use App\Models\Student;
use App\Models\Academics\Subject;
use Illuminate\Http\Request;

class ExamMarkController extends Controller
{
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $marks = ExamMark::with(['student','subject','exam'])
            ->when($examId, fn($q)=>$q->where('exam_id',$examId))
            ->paginate(50);

        return view('academics.exam_marks.index', compact('marks','examId'));
    }

    public function create()
    {
        $exams    = Exam::all();
        $students = Student::all();
        $subjects = Subject::all();

        return view('academics.exam_marks.create', compact('exams','students','subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id'    => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'marks'      => 'required|numeric|min:0|max:100',
        ]);

        // ✅ Find exam type and grade
        $exam      = Exam::findOrFail($request->exam_id);
        $gradeData = ExamGrade::where('exam_type', $exam->type)
            ->where('percent_from', '<=', $request->marks)
            ->where('percent_upto', '>=', $request->marks)
            ->first();

        $gradeLabel = $gradeData?->grade_name ?? 'N/A';
        $gradePoint = $gradeData?->grade_point ?? null;

        ExamMark::create([
            'exam_id'   => $request->exam_id,
            'student_id'=> $request->student_id,
            'subject_id'=> $request->subject_id,
            'score_raw' => $request->marks,
            'grade_label' => $gradeLabel,
            'pl_level' => $gradePoint,
            'status'   => 'submitted'
        ]);

        return redirect()->route('academics.exam-marks.index',['exam_id'=>$exam->id])
            ->with('success','Marks saved successfully.');
    }

    public function edit(ExamMark $exam_mark)
    {
        return view('academics.exam_marks.edit', compact('exam_mark'));
    }

    public function update(Request $request, ExamMark $exam_mark)
    {
        $request->validate([
            'score_raw'=>'required|numeric|min:0|max:100',
            'remark'=>'nullable|string|max:500',
        ]);

        // ✅ Recalculate grade
        $exam      = $exam_mark->exam;
        $gradeData = ExamGrade::where('exam_type', $exam->type)
            ->where('percent_from', '<=', $request->score_raw)
            ->where('percent_upto', '>=', $request->score_raw)
            ->first();

        $exam_mark->update([
            'score_raw'   => $request->score_raw,
            'grade_label' => $gradeData?->grade_name ?? 'N/A',
            'pl_level'    => $gradeData?->grade_point ?? null,
            'remark'      => $request->remark,
            'status'      => 'submitted'
        ]);

        return redirect()->route('academics.exam-marks.index',['exam_id'=>$exam_mark->exam_id])
            ->with('success','Mark updated successfully.');
    }
}

<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamMarkController extends Controller
{
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $marks = ExamMark::with(['student','subject','exam'])
            ->when($examId, fn($q)=>$q->where('exam_id',$examId))
            ->latest()->paginate(50);

        $exams = Exam::latest()->take(30)->get();
        return view('academics.exam_marks.index', compact('marks','examId','exams'));
    }

    public function bulkForm()
    {
        return view('academics.exam_marks.bulk_form', [
            'exams'      => Exam::with('classrooms')->latest()->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'subjects'   => Subject::orderBy('name')->get(),
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $v = $request->validate([
            'exam_id'     => 'required|exists:exams,id',
            'classroom_id'=> 'required|exists:classrooms,id',
            'subject_id'  => 'required|exists:subjects,id',
        ]);

        $exam   = Exam::findOrFail($v['exam_id']);
        $class  = Classroom::findOrFail($v['classroom_id']);
        $subject= Subject::findOrFail($v['subject_id']);

        $students = Student::where('classroom_id',$class->id)
            ->when($exam->stream_id, fn($q)=>$q->where('stream_id',$exam->stream_id))
            ->orderBy('last_name')->get();

        $existing = ExamMark::where('exam_id',$exam->id)
            ->where('subject_id',$subject->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        return view('academics.exam_marks.bulk_edit', compact('exam','class','subject','students','existing'));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'exam_id'     => 'required|exists:exams,id',
            'subject_id'  => 'required|exists:subjects,id',
            'rows'        => 'required|array',
            'rows.*.student_id'    => 'required|exists:students,id',
            'rows.*.opener_score'  => 'nullable|numeric|min:0|max:100',
            'rows.*.midterm_score' => 'nullable|numeric|min:0|max:100',
            'rows.*.endterm_score' => 'nullable|numeric|min:0|max:100',
            'rows.*.subject_remark'=> 'nullable|string|max:500',
        ]);

        $exam = Exam::findOrFail($data['exam_id']);

        foreach ($data['rows'] as $row) {
            $mark = ExamMark::firstOrNew([
                'exam_id'   => $exam->id,
                'student_id'=> $row['student_id'],
                'subject_id'=> $data['subject_id'],
            ]);

            // calculate final score automatically
            $scores = collect([
                $row['opener_score'] ?? null,
                $row['midterm_score'] ?? null,
                $row['endterm_score'] ?? null
            ])->filter();

            $finalScore = $scores->avg();

            $g = null;
            if ($finalScore !== null) {
                $g = ExamGrade::where('exam_type', $exam->type)
                    ->where('percent_from','<=',$finalScore)
                    ->where('percent_upto','>=',$finalScore)
                    ->first();
            }

            $mark->fill([
                'opener_score'  => $row['opener_score'] ?? null,
                'midterm_score' => $row['midterm_score'] ?? null,
                'endterm_score' => $row['endterm_score'] ?? null,
                'score_raw'     => $finalScore,
                'grade_label'   => $g?->grade_name ?? 'BE',
                'pl_level'      => $g?->grade_point ?? 1.0,
                'subject_remark'=> $row['subject_remark'] ?? null,
                'status'        => 'submitted',
                'teacher_id'    => optional(Auth::user()->staff)->id,
            ])->save();
        }

        return back()->with('success','Marks saved successfully.');
    }

    public function edit(ExamMark $exam_mark) {
        return view('academics.exam_marks.edit', compact('exam_mark'));
    }

    public function update(Request $request, ExamMark $exam_mark)
    {
        $v = $request->validate([
            'opener_score'  => 'nullable|numeric|min:0|max:100',
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'endterm_score' => 'nullable|numeric|min:0|max:100',
            'subject_remark'=> 'nullable|string|max:500',
            'remark'        => 'nullable|string|max:500',
        ]);

        // calculate final automatically
        $scores = collect([
            $v['opener_score'] ?? null,
            $v['midterm_score'] ?? null,
            $v['endterm_score'] ?? null
        ])->filter();

        $finalScore = $scores->avg();

        $g = ExamGrade::where('exam_type',$exam_mark->exam->type)
            ->where('percent_from','<=',$finalScore)
            ->where('percent_upto','>=',$finalScore)
            ->first();

        $exam_mark->update(array_merge($v, [
            'score_raw'   => $finalScore,
            'grade_label' => $g?->grade_name ?? 'BE',
            'pl_level'    => $g?->grade_point ?? 1.0,
            'status'      => 'submitted'
        ]));

        return redirect()->route('academics.exam-marks.index',['exam_id'=>$exam_mark->exam_id])
            ->with('success','Mark updated.');
    }
}

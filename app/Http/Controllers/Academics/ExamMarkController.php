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

    /** STEP 1: Selector */
    public function bulkForm()
    {
        return view('academics.exam_marks.bulk_form', [
            'exams'      => Exam::with('classrooms')->latest()->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'subjects'   => Subject::orderBy('name')->get(),
        ]);
    }

    /** STEP 2 (POST): Build editor with validation */
   public function bulkEdit(Request $request)
    {
        $v = $request->validate([
            'exam_id'      => 'required|exists:exams,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id'   => 'required|exists:subjects,id',
        ]);

        $exam = \App\Models\Academics\Exam::findOrFail($v['exam_id']);
        $this->authorize('enter-marks', [$exam, (int)$v['classroom_id'], (int)$v['subject_id']]);

        return $this->renderBulkEditor($v['exam_id'], $v['classroom_id'], $v['subject_id']);
    }

    /** STEP 3 (GET): View editor without validation (for redirects / reloads) */
    public function bulkEditView(Request $request)
    {
        $examId  = $request->query('exam_id');
        $classId = $request->query('classroom_id');
        $subId   = $request->query('subject_id');

        abort_unless($examId && $classId && $subId, 404);

        return $this->renderBulkEditor($examId, $classId, $subId);
    }

    /** Shared renderer */
    private function renderBulkEditor($examId, $classId, $subjectId)
    {
        $exam    = Exam::findOrFail($examId);
        $class   = Classroom::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        // Enforce single-exam workflow: marks are per (exam, student, subject)
        $students = Student::where('classroom_id',$class->id)
            ->when($exam->stream_id, fn($q)=>$q->where('stream_id',$exam->stream_id))
            ->orderBy('last_name')->get();

        $existing = ExamMark::where('exam_id',$exam->id)
            ->where('subject_id',$subject->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        return view('academics.exam_marks.bulk_edit', compact('exam','class','subject','students','existing'));
    }

    /** STEP 4: Save rows */
   public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'exam_id'      => 'required|exists:exams,id',
            'subject_id'   => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'rows'         => 'required|array',
            'rows.*.student_id'    => 'required|exists:students,id',
            'rows.*.score'         => 'nullable|numeric',
            'rows.*.subject_remark'=> 'nullable|string|max:500',
        ]);

        $exam = \App\Models\Academics\Exam::findOrFail($data['exam_id']);
        $this->authorize('enter-marks', [$exam, (int)$data['classroom_id'], (int)$data['subject_id']]);

        foreach ($data['rows'] as $row) {
            $mark = ExamMark::firstOrNew([
                'exam_id'    => $exam->id,
                'student_id' => $row['student_id'],
                'subject_id' => $data['subject_id'],
            ]);

            $scores = collect([
                $row['opener_score']  ?? null,
                $row['midterm_score'] ?? null,
                $row['endterm_score'] ?? null,
            ])->filter(fn($v) => $v !== null && $v !== '' && is_numeric($v));

            $score = array_key_exists('score', $row) && is_numeric($row['score']) ? (float)$row['score'] : null;

            $g = null;
            if (!is_null($score)) {
                $g = ExamGrade::where('exam_type', $exam->type)
                    ->where('percent_from','<=',$score)
                    ->where('percent_upto','>=',$score)
                    ->first();
            }

            $mark->fill([
                'score_raw'      => $score,
                'grade_label'    => $g?->grade_name ?? 'BE',
                'pl_level'       => $g?->grade_point ?? 1.0,
                'subject_remark' => $row['subject_remark'] ?? null,
                'status'         => 'submitted',
                'teacher_id'     => optional(Auth::user()->staff)->id,
            ])->save();
                    }

        // PRG to a GET URL to avoid loop
        return redirect()
            ->route('academics.exam-marks.bulk.edit.view', [
                'exam_id'      => $data['exam_id'],
                'classroom_id' => $data['classroom_id'],
                'subject_id'   => $data['subject_id'],
            ])
            ->with('success','Marks saved successfully.');
    }

    /** Individual edit/update */
    public function edit(ExamMark $exam_mark)
    {
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

        $scores = collect([
            $v['opener_score']  ?? null,
            $v['midterm_score'] ?? null,
            $v['endterm_score'] ?? null,
        ])->filter(fn($val) => $val !== null && $val !== '' && is_numeric($val));

        $finalScore = $scores->count() ? $scores->avg() : null;

        $g = null;
        if (!is_null($finalScore)) {
            $g = ExamGrade::where('exam_type',$exam_mark->exam->type)
                ->where('percent_from','<=',$finalScore)
                ->where('percent_upto','>=',$finalScore)
                ->first();
        }

        $exam_mark->update(array_merge($v, [
            'score_raw'   => $finalScore,
            'grade_label' => $g?->grade_name ?? 'BE',
            'pl_level'    => $g?->grade_point ?? 1.0,
            'status'      => 'submitted'
        ]));

        return redirect()
            ->route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id])
            ->with('success','Mark updated.');
    }
}

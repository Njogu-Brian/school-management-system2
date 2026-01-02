<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSchedule;
use App\Models\Academics\GradingBand;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\GradingSchemeMapping;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        // same as your ExamMarkController@index but under results route
        $examId = $request->query('exam_id');
        $marks = ExamMark::with(['student','subject','exam'])
            ->when($examId, fn($q)=>$q->where('exam_id',$examId))
            ->latest()->paginate(50);

        $exams = Exam::latest()->take(30)->get();
        return view('academics.exam_results.index', compact('marks','examId','exams'));
    }

    // bulk form identical to your existing bulk form; omitted for brevity

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

        $exam = Exam::with(['group.type'])->findOrFail($data['exam_id']);
        $schedule = ExamSchedule::where('exam_id',$exam->id)
            ->where('subject_id',$data['subject_id'])
            ->first();

        $min = $schedule->min_mark
            ?? optional($exam->group?->type)->default_min_mark
            ?? 0;

        $max = $schedule->max_mark
            ?? optional($exam->group?->type)->default_max_mark
            ?? (int)($exam->max_marks ?? 100);

        // validate again with computed bounds
        foreach ($data['rows'] as $i => $row) {
            if (isset($row['score']) && $row['score'] !== '') {
                $request->validate([
                    "rows.$i.score" => "numeric|min:$min|max:$max"
                ]);
            }
        }

        // resolve grading scheme for this class (fallback: first default scheme)
        $schemeId = GradingSchemeMapping::where('classroom_id',$data['classroom_id'])->value('grading_scheme_id')
            ?? GradingScheme::where('is_default',1)->value('id');

        foreach ($data['rows'] as $row) {
            // Validate student is not alumni or archived
            $student = \App\Models\Student::withAlumni()->find($row['student_id']);
            if ($student && ($student->is_alumni || $student->archive)) {
                continue; // Skip alumni/archived students
            }
            
            $score = ($row['score'] === '' ? null : $row['score']);

            $band = null;
            if ($score !== null && $schemeId) {
                $band = GradingBand::where('grading_scheme_id',$schemeId)
                    ->where('min','<=',$score)
                    ->where('max','>=',$score)
                    ->orderByDesc('min')
                    ->first();
            }

            $mark = ExamMark::firstOrNew([
                'exam_id'    => $exam->id,
                'student_id' => $row['student_id'],
                'subject_id' => $data['subject_id'],
            ]);

            $mark->fill([
                'score_raw'      => $score,
                'final_score'    => $score,               // if you later add final_score column
                'grade_label'    => $band?->label ?? null,
                'pl_level'       => $band?->rank ?? null,
                'subject_remark' => $row['subject_remark'] ?? null,
                'status'         => 'submitted',
                'teacher_id'     => optional(Auth::user()->staff)->id,
            ])->save();
        }

        return back()->with('success','Marks saved.');
    }
}

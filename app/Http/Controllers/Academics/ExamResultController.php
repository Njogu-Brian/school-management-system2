<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use App\Models\Academics\ExamSchedule;
use App\Services\Academics\ClassroomGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        $examTypeId = $request->query('exam_type_id');
        $examSessionId = $request->query('exam_session_id');
        $examId = $request->query('exam_id');

        $marks = ExamMark::with(['student', 'subject', 'exam.examSession'])
            ->when($examId, fn ($q) => $q->where('exam_id', $examId))
            ->when(! $examId && $examSessionId, fn ($q) => $q->whereHas('exam', fn ($eq) => $eq->where('exam_session_id', $examSessionId)))
            ->when(! $examId && ! $examSessionId && $examTypeId, function ($q) use ($examTypeId) {
                $tid = (int) $examTypeId;
                $q->whereHas('exam', function ($eq) use ($tid) {
                    $eq->where('exam_type_id', $tid)
                        ->orWhereHas('examSession', fn ($es) => $es->where('exam_type_id', $tid));
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $examTypes = ExamType::orderBy('name')->get();
        $sessions = ExamSession::query()
            ->with(['examType', 'classroom', 'academicYear', 'term'])
            ->when($examTypeId, fn ($q) => $q->where('exam_type_id', $examTypeId))
            ->orderByDesc('id')
            ->limit(500)
            ->get();
        $papersQuery = Exam::query()
            ->with(['academicYear', 'term', 'subject', 'examSession'])
            ->whereNotNull('subject_id')
            ->when($examSessionId, fn ($q) => $q->where('exam_session_id', $examSessionId))
            ->when(! $examSessionId && $examTypeId, function ($q) use ($examTypeId) {
                $tid = (int) $examTypeId;
                $q->where(function ($qq) use ($tid) {
                    $qq->where('exam_type_id', $tid)
                        ->orWhereHas('examSession', fn ($es) => $es->where('exam_type_id', $tid));
                });
            });

        $paperLimit = ($examSessionId || $examTypeId) ? 800 : 200;
        $papers = $papersQuery->orderByDesc('id')->limit($paperLimit)->get();

        return view('academics.exam_results.index', compact(
            'marks',
            'examId',
            'examTypeId',
            'examSessionId',
            'examTypes',
            'sessions',
            'papers'
        ));
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

        $grading = app(ClassroomGradingService::class);
        $classroomId = (int) $data['classroom_id'];

        foreach ($data['rows'] as $row) {
            // Validate student is not alumni or archived
            $student = \App\Models\Student::withAlumni()->find($row['student_id']);
            if ($student && ($student->is_alumni || $student->archive)) {
                continue; // Skip alumni/archived students
            }
            
            $score = ($row['score'] === '' ? null : $row['score']);

            $g = ['label' => null, 'points' => null];
            if ($score !== null && is_numeric($score)) {
                $g = $grading->gradeForRawScore((float) $score, (float) $max, $classroomId);
            }

            $mark = ExamMark::firstOrNew([
                'exam_id'    => $exam->id,
                'student_id' => $row['student_id'],
                'subject_id' => $data['subject_id'],
            ]);

            $mark->fill([
                'score_raw'      => $score,
                'final_score'    => $score,
                'grade_label'    => $g['label'],
                'pl_level'       => $g['points'],
                'subject_remark' => $row['subject_remark'] ?? null,
                'status'         => 'submitted',
                'teacher_id'     => optional(Auth::user()->staff)->id,
            ])->save();
        }

        return back()->with('success','Marks saved.');
    }
}

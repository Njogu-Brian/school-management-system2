<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;
use App\Models\Academics\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAcademicsController extends Controller
{
    public function exams(Request $request)
    {
        $perPage = (int) $request->input('per_page', 30);
        $query = Exam::query()->with(['classroom', 'subject', 'term'])
            ->orderByDesc('starts_on')
            ->orderByDesc('id');

        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()
            && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $allowed = $this->teacherAllowedClassroomIds($user);
            if (empty($allowed)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($allowed) {
                    $q->whereIn('classroom_id', $allowed)
                        ->orWhereIn('id', function ($sub) use ($allowed) {
                            $sub->select('exam_id')
                                ->from('exam_class_subject')
                                ->whereIn('classroom_id', $allowed);
                        });
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($e) => $this->formatExam($e))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function showExam(Request $request, $id)
    {
        $exam = Exam::with(['classroom', 'subject', 'term', 'academicYear'])->findOrFail($id);
        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()
            && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if (! $this->teacherCanAccessExam($user, $exam)) {
                abort(403, 'You do not have access to this exam.');
            }
        }

        return response()->json(['success' => true, 'data' => $this->formatExam($exam, true)]);
    }

    /**
     * Class/subject combinations available for marking (from exam pivot or single exam row).
     */
    public function examMarkingOptions(Request $request, $id)
    {
        $exam = Exam::with(['classroom', 'subject'])->findOrFail($id);
        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()
            && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if (! $this->teacherCanAccessExam($user, $exam)) {
                abort(403, 'You do not have access to this exam.');
            }
        }
        $options = [];

        if ($exam->classroom_id && $exam->subject_id) {
            $options[] = [
                'classroom_id' => $exam->classroom_id,
                'classroom_name' => $exam->classroom->name ?? '',
                'subject_id' => $exam->subject_id,
                'subject_name' => $exam->subject->name ?? '',
            ];
        }

        $pivot = DB::table('exam_class_subject')
            ->where('exam_id', $exam->id)
            ->get();

        foreach ($pivot as $row) {
            $class = \App\Models\Academics\Classroom::find($row->classroom_id);
            $subj = \App\Models\Academics\Subject::find($row->subject_id);
            $options[] = [
                'classroom_id' => (int) $row->classroom_id,
                'classroom_name' => $class->name ?? '',
                'subject_id' => (int) $row->subject_id,
                'subject_name' => $subj->name ?? '',
            ];
        }

        $options = collect($options)->unique(fn ($o) => $o['classroom_id'] . '-' . $o['subject_id'])->values()->all();

        if ($user && $user->hasTeacherLikeRole()
            && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $allowed = $this->teacherAllowedClassroomIds($user);
            $options = array_values(array_filter($options, fn ($o) => in_array((int) $o['classroom_id'], $allowed, true)));
        }

        return response()->json(['success' => true, 'data' => $options]);
    }

    public function marks(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $user = $request->user();

        $studentQuery = Student::where('classroom_id', $request->classroom_id)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if ($user && $user->hasTeacherLikeRole()
            && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if (! $user->canTeacherAccessClassroom((int) $request->classroom_id)) {
                abort(403, 'You do not have access to marks for this classroom.');
            }
        }
        if ($user && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($studentQuery);
        }
        $studentIds = $studentQuery->pluck('id');

        $marks = ExamMark::where('exam_id', $exam->id)
            ->where('subject_id', $request->subject_id)
            ->whereIn('student_id', $studentIds)
            ->with('student')
            ->get();

        $data = $marks->map(function ($m) use ($exam) {
            $score = $m->score_raw ?? $m->final_score;
            return [
                'id' => $m->id,
                'exam_id' => $m->exam_id,
                'student_id' => $m->student_id,
                'student_name' => $m->student->full_name ?? '',
                'subject_id' => $m->subject_id,
                'marks' => $score !== null ? (float) $score : 0,
                'total_marks' => (float) ($exam->max_marks ?? 100),
                'remarks' => $m->subject_remark,
                'percentage' => $exam->max_marks > 0 && $score !== null
                    ? round(((float) $score / (float) $exam->max_marks) * 100, 2)
                    : 0,
                'created_at' => $m->created_at->toIso8601String(),
                'updated_at' => $m->updated_at->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => count($data),
                'total' => count($data),
            ],
        ]);
    }

    public function batchMarks(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'marks' => 'nullable|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks' => 'nullable|numeric',
            'marks.*.remarks' => 'nullable|string|max:500',
        ]);

        $exam = Exam::findOrFail($data['exam_id']);
        $user = $request->user();

        if ($user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $allowedClassrooms = $user->getDashboardClassroomIds();
            if (! in_array((int) $data['classroom_id'], $allowedClassrooms, true)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to enter marks for this classroom.'], 403);
            }
            $staff = $user->staff;
            if ($staff) {
                $hasSubjectAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $data['classroom_id'])
                    ->where('subject_id', $data['subject_id'])
                    ->exists();
                $isDirectOrSupervised = $user->isAssignedToClassroom($data['classroom_id'])
                    || ($user->isSeniorTeacherUser() && in_array((int) $data['classroom_id'], array_map('intval', $user->getSupervisedClassroomIds()), true));
                if (! $hasSubjectAccess && ! $isDirectOrSupervised) {
                    return response()->json(['success' => false, 'message' => 'You do not have access to enter marks for this subject.'], 403);
                }
            }
        }

        if (!in_array($exam->status, ['open', 'marking'])) {
            return response()->json(['success' => false, 'message' => 'This exam is not open for mark entry.'], 422);
        }

        $maxMarks = (float) ($exam->max_marks ?? 100);
        $minMarks = 0;
        $rows = $data['marks'] ?? [];
        $count = 0;

        foreach ($rows as $row) {
            if (!isset($row['marks']) || $row['marks'] === '' || $row['marks'] === null) {
                continue;
            }
            $score = (float) $row['marks'];
            if ($score < $minMarks || $score > $maxMarks) {
                continue;
            }

            $student = Student::find($row['student_id']);
            if (! $student || $student->archive || $student->is_alumni) {
                continue;
            }
            if ((int) $student->classroom_id !== (int) $data['classroom_id']) {
                continue;
            }
            if ($user && $user->hasTeacherLikeRole()) {
                $scope = Student::where('id', $student->id)->where('archive', 0)->where('is_alumni', false);
                $user->applyTeacherStudentFilter($scope);
                if (! $scope->exists()) {
                    continue;
                }
            }

            $mark = ExamMark::firstOrNew([
                'exam_id' => $exam->id,
                'student_id' => $row['student_id'],
                'subject_id' => $data['subject_id'],
            ]);

            $g = ExamGrade::where('exam_type', $exam->type)
                ->where('percent_from', '<=', $score)
                ->where('percent_upto', '>=', $score)
                ->first();

            $mark->fill([
                'score_raw' => $score,
                'final_score' => $score,
                'grade_label' => $g?->grade_name ?? 'BE',
                'pl_level' => $g?->grade_point ?? 1.0,
                'subject_remark' => $row['remarks'] ?? null,
                'status' => 'submitted',
                'teacher_id' => optional($user->staff)->id,
            ])->save();

            $count++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => "Marks saved for {$count} students.",
                'count' => $count,
            ],
        ]);
    }

    public function marksMatrixContext(Request $request)
    {
        $user = $request->user();
        $classroomId = $request->filled('classroom_id') ? (int) $request->input('classroom_id') : null;

        $classrooms = Classroom::query()
            ->when($user && $user->hasTeacherLikeRole(), function ($q) use ($user) {
                $ids = $user->getDashboardClassroomIds();
                if (empty($ids)) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('id', $ids);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $examTypes = ExamType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $streams = collect();
        if ($classroomId) {
            $streams = Stream::query()
                ->where('classroom_id', $classroomId)
                ->when($user && $user->hasTeacherLikeRole(), function ($q) use ($user) {
                    $streamIds = $user->getEffectiveStreamIds();
                    if (!empty($streamIds)) {
                        $q->whereIn('id', $streamIds);
                    }
                })
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'exam_types' => $examTypes,
                'classrooms' => $classrooms,
                'streams' => $streams,
            ],
        ]);
    }

    public function marksMatrix(Request $request)
    {
        $v = $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $examTypeId = (int) $v['exam_type_id'];
        $classroomId = (int) $v['classroom_id'];
        $streamId = $request->filled('stream_id') ? (int) $request->input('stream_id') : null;
        $user = $request->user();

        if (!$this->canAccessClassroom($user, $classroomId)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this classroom.'], 403);
        }

        $studentsQuery = Student::query()
            ->where('classroom_id', $classroomId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId));
        if ($user && $user->hasTeacherLikeRole() && !$user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $user->applyTeacherStudentFilter($studentsQuery);
        }
        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'admission_number', 'classroom_id', 'stream_id']);

        $examCandidates = Exam::query()
            ->with(['subject', 'examType'])
            ->where('exam_type_id', $examTypeId)
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['open', 'marking'])
            ->whereNotNull('subject_id')
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($subQ) use ($streamId) {
                    $subQ->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            })
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();

        $exams = $examCandidates->filter(function (Exam $exam) use ($user, $classroomId, $streamId) {
            return $this->canAccessClassSubject($user, $classroomId, (int) $exam->subject_id, $streamId);
        })->values();

        $existing = collect();
        if ($students->isNotEmpty() && $exams->isNotEmpty()) {
            $existing = ExamMark::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('exam_id', $exams->pluck('id'))
                ->get()
                ->map(fn ($m) => [
                    'student_id' => (int) $m->student_id,
                    'exam_id' => (int) $m->exam_id,
                    'marks' => is_null($m->score_raw) ? null : (float) $m->score_raw,
                    'remarks' => $m->subject_remark,
                ])
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $students->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'full_name' => trim(($s->first_name ?? '').' '.($s->last_name ?? '')),
                    'admission_number' => $s->admission_number,
                    'classroom_id' => (int) $s->classroom_id,
                    'stream_id' => $s->stream_id ? (int) $s->stream_id : null,
                ])->values(),
                'exams' => $exams->map(fn ($e) => [
                    'id' => (int) $e->id,
                    'name' => $e->name,
                    'subject_id' => (int) $e->subject_id,
                    'subject_name' => $e->subject->name ?? null,
                    'max_marks' => (float) ($e->examType?->default_max_mark ?? $e->max_marks ?? 100),
                    'min_marks' => (float) ($e->examType?->default_min_mark ?? 0),
                ])->values(),
                'existing_marks' => $existing,
            ],
        ]);
    }

    public function batchMarksMatrix(Request $request)
    {
        $v = $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'entries' => 'required|array',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.exam_id' => 'required|exists:exams,id',
            'entries.*.marks' => 'nullable|numeric',
            'entries.*.remarks' => 'nullable|string|max:500',
        ]);

        $examTypeId = (int) $v['exam_type_id'];
        $classroomId = (int) $v['classroom_id'];
        $streamId = $request->filled('stream_id') ? (int) $request->input('stream_id') : null;
        $entries = $v['entries'] ?? [];
        $user = $request->user();

        if (!$this->canAccessClassroom($user, $classroomId)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this classroom.'], 403);
        }

        $studentsQuery = Student::query()
            ->where('classroom_id', $classroomId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId));
        if ($user && $user->hasTeacherLikeRole() && !$user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $user->applyTeacherStudentFilter($studentsQuery);
        }
        $allowedStudents = $studentsQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedStudentSet = array_flip($allowedStudents);

        $examCandidates = Exam::query()
            ->with(['examType'])
            ->where('exam_type_id', $examTypeId)
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['open', 'marking'])
            ->whereNotNull('subject_id')
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($subQ) use ($streamId) {
                    $subQ->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            })
            ->get();

        $allowedExams = $examCandidates->filter(function (Exam $exam) use ($user, $classroomId, $streamId) {
            return $this->canAccessClassSubject($user, $classroomId, (int) $exam->subject_id, $streamId);
        })->keyBy('id');
        $allowedExamSet = array_flip($allowedExams->keys()->map(fn ($id) => (int) $id)->all());

        $saved = 0;
        $skipped = 0;
        foreach ($entries as $entry) {
            $studentId = (int) $entry['student_id'];
            $examId = (int) $entry['exam_id'];
            $scoreInput = $entry['marks'] ?? null;
            $remarks = $entry['remarks'] ?? null;

            if (!isset($allowedStudentSet[$studentId]) || !isset($allowedExamSet[$examId])) {
                $skipped++;
                continue;
            }

            $hasScore = !is_null($scoreInput) && $scoreInput !== '';
            $hasRemark = !is_null($remarks) && trim((string) $remarks) !== '';
            if (!$hasScore && !$hasRemark) {
                continue;
            }

            $exam = $allowedExams[$examId];
            $maxMarks = (float) ($exam->examType?->default_max_mark ?? $exam->max_marks ?? 100);
            $minMarks = (float) ($exam->examType?->default_min_mark ?? 0);

            $score = null;
            if ($hasScore) {
                $score = (float) $scoreInput;
                if ($score < $minMarks || $score > $maxMarks) {
                    $skipped++;
                    continue;
                }
            }

            $mark = ExamMark::firstOrNew([
                'exam_id' => $examId,
                'student_id' => $studentId,
                'subject_id' => (int) $exam->subject_id,
            ]);

            $g = null;
            if (!is_null($score)) {
                $g = ExamGrade::where('exam_type', $exam->type)
                    ->where('percent_from', '<=', $score)
                    ->where('percent_upto', '>=', $score)
                    ->first();
            }

            $mark->fill([
                'score_raw' => $score,
                'final_score' => $score,
                'grade_label' => $g?->grade_name ?? ($mark->grade_label ?? 'BE'),
                'pl_level' => $g?->grade_point ?? ($mark->pl_level ?? 1.0),
                'subject_remark' => $hasRemark ? trim((string) $remarks) : $mark->subject_remark,
                'status' => 'submitted',
                'teacher_id' => optional($user->staff)->id,
            ])->save();
            $saved++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => "Saved {$saved} mark entries.".($skipped > 0 ? " Skipped {$skipped} invalid or unauthorized entries." : ''),
                'count' => $saved,
                'skipped' => $skipped,
            ],
        ]);
    }

    /**
     * @param  \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    protected function teacherAllowedClassroomIds($user): array
    {
        if (! $user) {
            return [];
        }

        return array_values(array_map('intval', $user->getDashboardClassroomIds()));
    }

    protected function teacherCanAccessExam($user, Exam $exam): bool
    {
        $allowed = $this->teacherAllowedClassroomIds($user);
        if ($allowed === []) {
            return false;
        }
        if ($exam->classroom_id && in_array((int) $exam->classroom_id, $allowed, true)) {
            return true;
        }

        return DB::table('exam_class_subject')
            ->where('exam_id', $exam->id)
            ->whereIn('classroom_id', $allowed)
            ->exists();
    }

    protected function canAccessClassroom($user, int $classroomId): bool
    {
        if (!$user || !$user->hasTeacherLikeRole()) {
            return true;
        }

        $allowedClassrooms = array_map('intval', $user->getDashboardClassroomIds());
        return in_array($classroomId, $allowedClassrooms, true);
    }

    protected function canAccessClassSubject($user, int $classroomId, int $subjectId, ?int $streamId): bool
    {
        if (!$user || !$user->hasTeacherLikeRole()) {
            return true;
        }

        if ($user->isSeniorTeacherUser() && in_array($classroomId, array_map('intval', $user->getSupervisedClassroomIds()), true)) {
            return true;
        }

        $staffId = optional($user->staff)->id;
        if (!$staffId) {
            return false;
        }

        return DB::table('classroom_subjects')
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('staff_id', $staffId)
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($subQ) use ($streamId) {
                    $subQ->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            })
            ->exists();
    }

    protected function formatExam(Exam $e, bool $detail = false): array
    {
        $typeName = null;
        if ($e->exam_type_id && class_exists(\App\Models\Academics\ExamType::class)) {
            $typeName = \App\Models\Academics\ExamType::find($e->exam_type_id)?->name;
        }

        $out = [
            'id' => $e->id,
            'name' => $e->name,
            'exam_type_id' => $e->exam_type_id,
            'exam_type_name' => $typeName ?? $e->type,
            'academic_year_id' => $e->academic_year_id,
            'term_id' => $e->term_id,
            'classroom_id' => $e->classroom_id,
            'stream_id' => $e->stream_id,
            'subject_id' => $e->subject_id,
            'start_date' => $e->starts_on?->toDateString(),
            'end_date' => $e->ends_on?->toDateString(),
            'status' => $e->status,
            'total_marks' => (float) ($e->max_marks ?? 100),
            'created_at' => $e->created_at->toIso8601String(),
            'updated_at' => $e->updated_at->toIso8601String(),
        ];

        if ($detail) {
            $out['classroom_name'] = $e->classroom->name ?? null;
            $out['subject_name'] = $e->subject->name ?? null;
        }

        return $out;
    }
}

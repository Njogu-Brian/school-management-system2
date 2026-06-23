<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Subject;
use App\Services\Academics\ClassroomGradingService;
use App\Services\Academics\ExamMarkEntryService;
use App\Services\Academics\ExamMarkEntryAuditService;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamMarkController extends Controller
{
    public function __construct()
    {
        // Support both permission names for backward compatibility
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user->hasAnyRole(['Super Admin', 'Admin', 'System Admin'])
                || $user->hasAnyPermission(['exams.enter_marks', 'exam_marks.create'])
                || $user->hasTeacherLikeRole()
                || $user->hasTeachingAssignments()) {
                return $next($request);
            }
            abort(403, 'You do not have permission to enter exam marks.');
        })->only(['bulkForm', 'bulkEdit', 'bulkEditView', 'bulkStore', 'bulkDraftAutosave', 'submitExamMarks', 'matrixEdit', 'matrixView', 'matrixStore', 'edit', 'update']);
        
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user->hasAnyRole(['Super Admin', 'Admin', 'System Admin'])
                || $user->hasAnyPermission(['exams.view', 'exam_marks.view'])
                || $user->hasTeacherLikeRole()
                || $user->hasTeachingAssignments()) {
                return $next($request);
            }
            abort(403, 'You do not have permission to view exam marks.');
        })->only(['index']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ExamMark::with(['student','subject','exam']);

        // Teachers and Senior Teachers see marks for their assigned/supervised classes
        if ($user->hasTeacherLikeRole()) {
            $assignedClassroomIds = $user->isSeniorTeacherUser()
                ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
                : $user->getAssignedClassroomIds();
            
            if (!empty($assignedClassroomIds)) {
                $query->whereHas('student', function($q) use ($assignedClassroomIds) {
                    $q->whereIn('classroom_id', $assignedClassroomIds)
                      ->where('archive', 0)
                      ->where('is_alumni', false);
                });
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        $examId = $request->query('exam_id');
        if ($examId) {
            $query->where('exam_id', $examId);
        }

        $marks = $query->latest()->paginate(50)->withQueryString();

        // Filter exams based on user role
        if ($user->hasTeacherLikeRole()) {
            $staff = $user->staff;
            if ($staff) {
                $classIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $classIds = $user->isSeniorTeacherUser()
                    ? array_unique(array_merge($classIds, $user->getSupervisedClassroomIds()))
                    : $classIds;
                $exams = Exam::whereIn('classroom_id', $classIds)->latest()->take(30)->get();
            } else {
                $exams = collect();
            }
        } else {
            $exams = Exam::latest()->take(30)->get();
        }

        return view('academics.exam_marks.index', compact('marks','examId','exams'));
    }

    /** STEP 1: Selector */
    public function bulkForm()
    {
        $user = Auth::user();
        // Filter exams and classrooms based on user role
        $isTeacher = $user->hasTeacherLikeRole() || $user->hasTeachingAssignments();
        if ($isTeacher) {
            $assignedClassroomIds = $user->isSeniorTeacherUser()
                ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
                : $user->getAssignedClassroomIds();
            
            if (!empty($assignedClassroomIds)) {
                // Filter classrooms to only assigned ones
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
        }

        return view('academics.exam_marks.bulk_form', [
            'classrooms' => $classrooms,
            'types'      => ExamType::orderBy('name')->get(),
            'streams'    => Stream::orderBy('name')->get(),
        ]);
    }

    /**
     * New matrix flow: choose exam type + class + optional stream.
     */
    public function matrixEdit(Request $request)
    {
        $v = $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        return $this->renderMatrixEditor((int) $v['exam_type_id'], (int) $v['classroom_id'], isset($v['stream_id']) ? (int) $v['stream_id'] : null);
    }

    public function matrixView(Request $request)
    {
        $examTypeId = (int) $request->query('exam_type_id');
        $classroomId = (int) $request->query('classroom_id');
        $streamId = $request->query('stream_id');

        abort_unless($examTypeId > 0 && $classroomId > 0, 404);

        return $this->renderMatrixEditor($examTypeId, $classroomId, is_null($streamId) || $streamId === '' ? null : (int) $streamId);
    }

    public function matrixStore(Request $request)
    {
        $v = $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'rows' => 'required|array',
        ]);

        $examTypeId = (int) $v['exam_type_id'];
        $classroomId = (int) $v['classroom_id'];
        $streamId = isset($v['stream_id']) ? (int) $v['stream_id'] : null;
        $rows = $request->input('rows', []);

        $authUser = Auth::user();
        if (! $this->userCanAccessClassroomForMarks($authUser, $classroomId)) {
            return back()->withInput()->with('error', 'You do not have access to this classroom.');
        }

        $studentsQuery = Student::query()
            ->where('classroom_id', $classroomId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId));
        if ($authUser->hasTeacherLikeRole() && ! $authUser->hasAnyRole(['Super Admin', 'Admin'])) {
            $authUser->applyTeacherStudentFilter($studentsQuery);
        }
        $allowedStudentIds = $studentsQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedStudentIdSet = array_flip($allowedStudentIds);

        $examCandidates = Exam::query()
            ->with(['examType'])
            ->where('exam_type_id', $examTypeId)
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['open', 'marking', 'moderation'])
            ->whereNotNull('subject_id')
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($subQ) use ($streamId) {
                    $subQ->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            })
            ->get();

        $allowedExams = $examCandidates->filter(function (Exam $exam) use ($authUser, $classroomId, $streamId) {
            return $this->userCanAccessClassSubjectForMarks($authUser, $classroomId, (int) $exam->subject_id, $streamId);
        })->keyBy('id');
        $allowedExamIdSet = array_flip($allowedExams->keys()->map(fn ($id) => (int) $id)->all());

        $entries = [];
        foreach ($rows as $studentId => $examRows) {
            $studentId = (int) $studentId;
            if (! isset($allowedStudentIdSet[$studentId]) || ! is_array($examRows)) {
                continue;
            }
            foreach ($examRows as $examId => $payload) {
                $examId = (int) $examId;
                if (! isset($allowedExamIdSet[$examId])) {
                    continue;
                }
                $entries[] = [
                    'student_id' => $studentId,
                    'exam_id' => $examId,
                    'score' => data_get($payload, 'score'),
                    'subject_remark' => data_get($payload, 'subject_remark'),
                    'is_absent' => filter_var(data_get($payload, 'is_absent', false), FILTER_VALIDATE_BOOLEAN),
                ];
            }
        }

        $finalizeExamIds = $request->boolean('submit_for_review')
            ? array_values(array_filter(array_map('intval', (array) $request->input('submit_exam_ids', []))))
            : [];

        try {
            $result = app(ExamMarkEntryService::class)->saveDraftMatrixEntries(
                $examTypeId,
                $classroomId,
                $streamId,
                $entries,
                $authUser,
                $finalizeExamIds
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = ! empty($finalizeExamIds)
            ? "Submitted marks for review (".count($finalizeExamIds)." exam(s))."
            : "Saved {$result['saved']} mark(s) as draft.";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']} invalid entries.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved' => $result['saved'],
                'submitted_exam_ids' => $result['submitted_exam_ids'],
            ]);
        }

        return redirect()->route('academics.exam-marks.matrix.view', [
            'exam_type_id' => $examTypeId,
            'classroom_id' => $classroomId,
            'stream_id' => $streamId,
        ])->with('success', $message);
    }

    private function renderMatrixEditor(int $examTypeId, int $classroomId, ?int $streamId)
    {
        $authUser = Auth::user();
        if (! $this->userCanAccessClassroomForMarks($authUser, $classroomId)) {
            abort(403, 'You do not have access to this classroom.');
        }

        $examType = ExamType::findOrFail($examTypeId);
        $classroom = Classroom::findOrFail($classroomId);
        $stream = $streamId ? Stream::find($streamId) : null;

        $studentsQuery = Student::query()
            ->where('classroom_id', $classroomId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId));
        if ($authUser->hasTeacherLikeRole() && ! $authUser->hasAnyRole(['Super Admin', 'Admin'])) {
            $authUser->applyTeacherStudentFilter($studentsQuery);
        }
        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get();

        $examCandidates = Exam::query()
            ->with(['subject', 'examType'])
            ->where('exam_type_id', $examTypeId)
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['open', 'marking', 'moderation'])
            ->whereNotNull('subject_id')
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($subQ) use ($streamId) {
                    $subQ->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            })
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();

        $exams = $examCandidates->filter(function (Exam $exam) use ($authUser, $classroomId, $streamId) {
            return $this->userCanAccessClassSubjectForMarks($authUser, $classroomId, (int) $exam->subject_id, $streamId);
        })->values();

        $existing = collect();
        if ($students->isNotEmpty() && $exams->isNotEmpty()) {
            $existing = ExamMark::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('exam_id', $exams->pluck('id'))
                ->get()
                ->keyBy(fn ($m) => $m->student_id.'-'.$m->exam_id);
        }

        $entryService = app(ExamMarkEntryService::class);
        $examMeta = $exams->mapWithKeys(fn (Exam $exam) => [
            $exam->id => [
                'status' => $exam->status,
                'can_edit' => $entryService->examAcceptsTeacherEntry($exam, $authUser),
                'marking_submitted_at' => $exam->marking_submitted_at?->format('d M Y H:i'),
                'marking_submitted_by' => $exam->marking_submitted_by
                    ? \App\Models\User::query()->where('id', $exam->marking_submitted_by)->value('name')
                    : null,
            ],
        ]);

        $examAudits = app(ExamMarkEntryAuditService::class)->summariesForExams($exams);

        return view('academics.exam_marks.matrix_edit', [
            'examType' => $examType,
            'classroom' => $classroom,
            'stream' => $stream,
            'students' => $students,
            'exams' => $exams,
            'existing' => $existing,
            'examMeta' => $examMeta,
            'examAudits' => $examAudits,
        ]);
    }

    private function userCanAccessClassroomForMarks($user, int $classroomId): bool
    {
        if (!$user->hasTeacherLikeRole()) {
            return true;
        }
        $allowedClassroomIds = $user->isSeniorTeacherUser()
            ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
            : $user->getAssignedClassroomIds();

        return in_array($classroomId, array_map('intval', $allowedClassroomIds), true);
    }

    private function userCanAccessClassSubjectForMarks($user, int $classroomId, int $subjectId, ?int $streamId): bool
    {
        if (!$user->hasTeacherLikeRole()) {
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

    /** STEP 2 (POST): Build editor with validation */
   public function bulkEdit(Request $request)
    {
        $v = $request->validate([
            'exam_id'      => 'required|exists:exams,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id'   => 'required|exists:subjects,id',
        ]);

        $exam = Exam::findOrFail($v['exam_id']);

        // Check if teacher/senior teacher has access to this exam's classroom
        $authUser = Auth::user();
        if ($authUser->hasTeacherLikeRole()) {
            $user = $authUser;
            $assignedClassroomIds = $user->isSeniorTeacherUser()
                ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
                : $user->getAssignedClassroomIds();
            
            if (!in_array($v['classroom_id'], $assignedClassroomIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to enter marks for this classroom.');
            }
            
            // Check if teacher teaches this subject in this classroom
            $staff = $user->staff;
            $hasSubjectAccess = false;
            $isDirectlyAssigned = false;
            
            // Check if teacher is directly assigned to this classroom (can enter marks for any subject)
            $isDirectlyAssigned = DB::table('classroom_teacher')
                ->where('teacher_id', $user->id)
                ->where('classroom_id', $v['classroom_id'])
                ->exists();
            
            if ($staff && !$isDirectlyAssigned) {
                // Check if teacher teaches this specific subject in this classroom
                $hasSubjectAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $v['classroom_id'])
                    ->where('subject_id', $v['subject_id'])
                    ->exists();
            }
            
            // Allow access if:
            // 1. Teacher is directly assigned to the classroom (can enter marks for any subject), OR
            // 2. Teacher teaches this specific subject in this classroom, OR
            // 3. Senior Teacher supervises this classroom (can enter marks for any subject)
            $isLeadershipScope = ($user->isSeniorTeacherUser() || $user->isDeputySeniorTeacherUser())
                && $user->canTeacherAccessClassroom((int) $v['classroom_id']);
            if (!$isDirectlyAssigned && !$hasSubjectAccess && !$isLeadershipScope) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to enter marks for this subject in this classroom.');
            }
        }

        // Check if exam allows mark entry
        if (!in_array($exam->status, ['open', 'marking'])) {
            return back()
                ->withInput()
                ->with('error', 'Cannot enter marks for this exam. Exam status must be "Open" or "Marking".');
        }

        return $this->renderBulkEditor($v['exam_id'], $v['classroom_id'], $v['subject_id']);
    }

    /** STEP 3 (GET): View editor without validation (for redirects / reloads) */
    public function bulkEditView(Request $request)
    {
        $examId  = $request->query('exam_id');
        $classId = $request->query('classroom_id');
        $subId   = $request->query('subject_id');

        abort_unless($examId && $classId && $subId, 404);

        // Check if teacher/senior teacher has access
        $authUser = Auth::user();
        if ($authUser->hasTeacherLikeRole()) {
            $user = $authUser;
            $assignedClassroomIds = $user->isSeniorTeacherUser()
                ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
                : $user->getAssignedClassroomIds();
            
            if (!in_array($classId, $assignedClassroomIds)) {
                abort(403, 'You do not have access to enter marks for this classroom.');
            }
            
            // Check if teacher teaches this subject in this classroom
            $staff = $user->staff;
            $hasSubjectAccess = false;
            $isDirectlyAssigned = false;
            
            // Check if teacher is directly assigned to this classroom (can enter marks for any subject)
            $isDirectlyAssigned = DB::table('classroom_teacher')
                ->where('teacher_id', $user->id)
                ->where('classroom_id', $classId)
                ->exists();
            
            if ($staff && !$isDirectlyAssigned) {
                // Check if teacher teaches this specific subject in this classroom
                $hasSubjectAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $classId)
                    ->where('subject_id', $subId)
                    ->exists();
            }
            
            // Allow access if:
            // 1. Teacher is directly assigned to the classroom (can enter marks for any subject), OR
            // 2. Teacher teaches this specific subject in this classroom, OR
            // 3. Senior Teacher supervises this classroom
            $isLeadershipScope = ($user->isSeniorTeacherUser() || $user->isDeputySeniorTeacherUser())
                && $user->canTeacherAccessClassroom((int) $classId);
            if (!$isDirectlyAssigned && !$hasSubjectAccess && !$isLeadershipScope) {
                abort(403, 'You do not have access to enter marks for this subject in this classroom.');
            }
        }

        return $this->renderBulkEditor($examId, $classId, $subId);
    }

    /** Shared renderer */
    private function renderBulkEditor($examId, $classId, $subjectId)
    {
        $exam    = Exam::with(['stream', 'examType'])->findOrFail($examId);
        $class   = Classroom::findOrFail($classId);
        $subjectId = (int) ($exam->subject_id ?: $subjectId);
        $subject = Subject::findOrFail($subjectId);
        $classroomId = (int) $class->id;
        $maxMarks = (float) ($exam->max_marks ?? optional($exam->examType)->default_max_mark ?? 100);
        $streamLabel = $exam->stream?->name;

        $entryService = app(ExamMarkEntryService::class);
        $authUser = Auth::user();
        $canEdit = $entryService->examAcceptsTeacherEntry($exam, $authUser);

        // Enforce single-exam workflow: marks are per (exam, student, subject)
        // Exclude alumni and archived students; scope to teacher streams when applicable
        $studentsQuery = Student::where('classroom_id', $class->id)
            ->with('stream:id,name')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($exam->stream_id, fn ($q) => $q->where('stream_id', $exam->stream_id));

        if ($authUser->hasTeacherLikeRole() && ! $authUser->hasAnyRole(['Super Admin', 'Admin'])) {
            $authUser->applyTeacherStudentFilter($studentsQuery);
        }

        $students = $studentsQuery->orderBy('last_name')->get();

        $existing = ExamMark::where('exam_id', $exam->id)
            ->where('subject_id', $subjectId)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        $entryAudit = app(ExamMarkEntryAuditService::class)->summaryForExam(
            $exam,
            $subjectId,
            $students->pluck('id')
        );

        return view('academics.exam_marks.bulk_edit', compact(
            'exam',
            'class',
            'subject',
            'students',
            'existing',
            'canEdit',
            'entryAudit',
            'maxMarks',
            'streamLabel',
            'classroomId'
        ));
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
            'rows.*.is_absent'     => 'nullable|boolean',
        ]);

        $exam = Exam::findOrFail($data['exam_id']);

        // Check if teacher/senior teacher has access to this exam's classroom
        $authUser = Auth::user();
        if ($authUser->hasTeacherLikeRole()) {
            $user = $authUser;
            $assignedClassroomIds = $user->isSeniorTeacherUser()
                ? array_unique(array_merge($user->getAssignedClassroomIds(), $user->getSupervisedClassroomIds()))
                : $user->getAssignedClassroomIds();
            
            if (!in_array($data['classroom_id'], $assignedClassroomIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to enter marks for this classroom.');
            }
            
            // Check if teacher teaches this subject in this classroom
            $staff = $user->staff;
            if ($staff) {
                $hasSubjectAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $data['classroom_id'])
                    ->where('subject_id', $data['subject_id'])
                    ->exists();
                
                // If no subject-specific assignment, check direct class assignment or senior teacher supervision
                $isDirectOrSupervised = $user->isAssignedToClassroom($data['classroom_id'])
                    || (($user->isSeniorTeacherUser() || $user->isDeputySeniorTeacherUser()) && $user->canTeacherAccessClassroom((int) $data['classroom_id']));
                if (!$hasSubjectAccess && !$isDirectOrSupervised) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to enter marks for this subject in this classroom.');
                }
            } else {
                $canAccess = $user->isAssignedToClassroom($data['classroom_id'])
                    || (($user->isSeniorTeacherUser() || $user->isDeputySeniorTeacherUser()) && $user->canTeacherAccessClassroom((int) $data['classroom_id']));
                if (!$canAccess) {
                    abort(403, 'You do not have access to enter marks.');
                }
            }
        }

        if ($authUser->hasTeacherLikeRole() && ! $authUser->hasAnyRole(['Super Admin', 'Admin'])) {
            $allowedIds = Student::query()
                ->where('classroom_id', $data['classroom_id'])
                ->tap(fn ($q) => $authUser->applyTeacherStudentFilter($q))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($data['rows'] as $row) {
                if (! in_array((int) $row['student_id'], $allowedIds, true)) {
                    return back()
                        ->withInput()
                        ->with('error', 'One or more students are not in your teaching scope for this class.');
                }
            }
        }

        // Check if exam allows mark entry
        if (! app(ExamMarkEntryService::class)->examAcceptsTeacherEntry($exam, $authUser)) {
            return back()
                ->withInput()
                ->with('error', 'Cannot enter marks for this exam. It may be under review — only Senior Teachers and Admins can edit now.');
        }

        $finalize = $request->boolean('submit_for_review');
        $rows = collect($data['rows'])->map(fn ($row) => [
            'student_id' => (int) $row['student_id'],
            'score' => $row['score'] ?? null,
            'subject_remark' => $row['subject_remark'] ?? null,
            'is_absent' => filter_var($row['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ])->all();

        try {
            $result = app(ExamMarkEntryService::class)->saveDraftForExam(
                $exam,
                (int) $data['classroom_id'],
                $rows,
                $authUser,
                $finalize
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $finalize
            ? "Submitted {$result['saved']} mark(s) for review. This exam is now under moderation."
            : "Saved {$result['saved']} mark(s) as draft.";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']} invalid entries.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved' => $result['saved'],
                'exam_status' => $exam->fresh()->status,
            ]);
        }

        return redirect()
            ->route('academics.exam-marks.bulk.edit.view', [
                'exam_id'      => $exam->id,
                'classroom_id' => $data['classroom_id'],
                'subject_id'   => (int) ($exam->subject_id ?: $data['subject_id']),
            ])
            ->with('success', $message);
    }

    public function bulkDraftAutosave(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'rows' => 'required|array',
            'rows.*.student_id' => 'required|exists:students,id',
            'rows.*.score' => 'nullable|numeric',
            'rows.*.subject_remark' => 'nullable|string|max:500',
            'rows.*.is_absent' => 'nullable|boolean',
        ]);

        $exam = Exam::findOrFail($data['exam_id']);

        try {
            $result = app(ExamMarkEntryService::class)->saveDraftForExam(
                $exam,
                (int) $data['classroom_id'],
                $data['rows'],
                Auth::user(),
                false
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'saved' => $result['saved'],
            'skipped' => $result['skipped'],
            'exam_status' => $exam->status,
        ]);
    }

    public function submitExamMarks(Request $request, Exam $exam)
    {
        $authUser = Auth::user();
        $entryService = app(ExamMarkEntryService::class);

        if (! $entryService->examAcceptsTeacherEntry($exam, $authUser)) {
            $msg = 'This exam cannot be submitted.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        try {
            $entryService->submitExam($exam, $authUser);
        } catch (\RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        $message = 'Marks submitted for review. Only Senior Teachers and Admins can edit this exam now.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'exam_status' => $exam->fresh()->status])
            : back()->with('success', $message);
    }

    /** Individual edit/update */
    public function edit(ExamMark $exam_mark)
    {
        // Check if teacher has access
        if (Auth::user()->hasTeacherLikeRole()) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $exam = $exam_mark->exam;
                if ($exam && $exam->classroom_id) {
                    $hasAccess = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                        ->where('staff_id', $staff->id)
                        ->where('classroom_id', $exam->classroom_id)
                        ->where('subject_id', $exam_mark->subject_id)
                        ->exists();
                    
                    if (!$hasAccess && !((Auth::user()->isSeniorTeacherUser() || Auth::user()->isDeputySeniorTeacherUser()) && Auth::user()->canTeacherAccessClassroom((int) $exam->classroom_id))) {
                        abort(403, 'You do not have access to edit this mark.');
                    }
                }
            }
        }

        // Check if exam allows mark entry
        $exam = $exam_mark->exam;
        if ($exam && !in_array($exam->status, ['open', 'marking'])) {
            return back()
                ->with('error', 'Cannot edit marks for this exam. Exam status must be "Open" or "Marking".');
        }

        return view('academics.exam_marks.edit', compact('exam_mark'));
    }

    public function update(Request $request, ExamMark $exam_mark)
    {
        // Check if teacher has access
        if (Auth::user()->hasTeacherLikeRole()) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $exam = $exam_mark->exam;
                if ($exam && $exam->classroom_id) {
                    $hasAccess = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                        ->where('staff_id', $staff->id)
                        ->where('classroom_id', $exam->classroom_id)
                        ->where('subject_id', $exam_mark->subject_id)
                        ->exists();
                    
                    if (!$hasAccess && !((Auth::user()->isSeniorTeacherUser() || Auth::user()->isDeputySeniorTeacherUser()) && Auth::user()->canTeacherAccessClassroom((int) $exam->classroom_id))) {
                        abort(403, 'You do not have access to update this mark.');
                    }
                }
            }
        }

        // Check if exam allows mark entry
        $exam = $exam_mark->exam;
        if ($exam && !in_array($exam->status, ['open', 'marking'])) {
            return back()
                ->withInput()
                ->with('error', 'Cannot update marks for this exam. Exam status must be "Open" or "Marking".');
        }

        $examType = $exam->examType;
        $maxMarks = (float) ($examType?->default_max_mark ?? $exam->max_marks ?? 100);
        $minMarks = (float) ($examType?->default_min_mark ?? 0);

        $v = $request->validate([
            'opener_score'  => "nullable|numeric|min:{$minMarks}|max:{$maxMarks}",
            'midterm_score' => "nullable|numeric|min:{$minMarks}|max:{$maxMarks}",
            'endterm_score' => "nullable|numeric|min:{$minMarks}|max:{$maxMarks}",
            'subject_remark'=> 'nullable|string|max:500',
            'remark'        => 'nullable|string|max:500',
        ]);

        $scores = collect([
            $v['opener_score']  ?? null,
            $v['midterm_score'] ?? null,
            $v['endterm_score'] ?? null,
        ])->filter(fn($val) => $val !== null && $val !== '' && is_numeric($val));

        $finalScore = $scores->count() ? $scores->avg() : null;

        $g = ['label' => null, 'points' => null];
        if (! is_null($finalScore)) {
            if ($finalScore < $minMarks || $finalScore > $maxMarks) {
                return back()
                    ->withInput()
                    ->with('error', "Final score must be between {$minMarks} and {$maxMarks}.");
            }

            $classroomId = (int) ($exam->classroom_id ?? 0);
            if ($classroomId > 0) {
                $g = app(ClassroomGradingService::class)->gradeForRawScore($finalScore, $maxMarks, $classroomId);
            }
        }

        $exam_mark->update(array_merge($v, [
            'score_raw'   => $finalScore,
            'grade_label' => $g['label'] ?? null,
            'pl_level'    => $g['points'] ?? null,
            'status'      => 'submitted',
            'teacher_id'  => optional(Auth::user()->staff)->id,
        ]));

        return redirect()
            ->route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id])
            ->with('success','Mark updated.');
    }
}

<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;
use App\Models\Academics\Subject;
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
        })->only(['bulkForm', 'bulkEdit', 'bulkEditView', 'bulkStore', 'matrixEdit', 'matrixView', 'matrixStore', 'edit', 'update']);
        
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
            $staff = $user->staff;
            
            if (!empty($assignedClassroomIds)) {
                // Filter exams to only assigned classrooms
                $exams = Exam::whereIn('classroom_id', $assignedClassroomIds)->latest()->get();
                
                // Filter classrooms to only assigned ones
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
                
                // Get subjects that the teacher teaches in their assigned classrooms
                $subjectIds = [];
                if ($staff) {
                    // Get subjects from classroom_subjects where this teacher is assigned
                    $subjectIds = DB::table('classroom_subjects')
                        ->where('staff_id', $staff->id)
                        ->whereIn('classroom_id', $assignedClassroomIds)
                        ->distinct()
                        ->pluck('subject_id')
                        ->toArray();
                }
                
                // Also check if teacher is directly assigned to any classrooms (via classroom_teacher)
                // If directly assigned, they can enter marks for all subjects in those classrooms
                $directlyAssignedClassroomIds = DB::table('classroom_teacher')
                    ->where('teacher_id', $user->id)
                    ->pluck('classroom_id')
                    ->toArray();
                
                if (!empty($directlyAssignedClassroomIds)) {
                    // Get all subjects for directly assigned classrooms
                    $directSubjectIds = DB::table('classroom_subjects')
                        ->whereIn('classroom_id', $directlyAssignedClassroomIds)
                        ->distinct()
                        ->pluck('subject_id')
                        ->toArray();
                    
                    $subjectIds = array_unique(array_merge($subjectIds, $directSubjectIds));
                }
                
                // Filter subjects to only those the teacher can teach
                if (!empty($subjectIds)) {
                    $subjects = Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get();
                } else {
                    $subjects = collect();
                }
            } else {
                $exams = collect();
                $classrooms = collect();
                $subjects = collect();
            }
        } else {
            $exams = Exam::with('classrooms')->latest()->get();
            $classrooms = Classroom::orderBy('name')->get();
            $subjects = Subject::active()->orderBy('name')->get();
        }

        return view('academics.exam_marks.bulk_form', [
            'exams'      => $exams,
            'classrooms' => $classrooms,
            'subjects'   => $subjects,
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
            ->whereIn('status', ['open', 'marking'])
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

        $saved = 0;
        $skipped = 0;

        foreach ($rows as $studentId => $examRows) {
            $studentId = (int) $studentId;
            if (!isset($allowedStudentIdSet[$studentId]) || !is_array($examRows)) {
                $skipped++;
                continue;
            }

            foreach ($examRows as $examId => $payload) {
                $examId = (int) $examId;
                if (!isset($allowedExamIdSet[$examId])) {
                    $skipped++;
                    continue;
                }

                $scoreInput = data_get($payload, 'score');
                $remarkInput = data_get($payload, 'subject_remark');

                $hasScore = !is_null($scoreInput) && $scoreInput !== '';
                $hasRemark = !is_null($remarkInput) && trim((string) $remarkInput) !== '';
                if (!$hasScore && !$hasRemark) {
                    continue;
                }

                $exam = $allowedExams[$examId];
                $examType = $exam->examType;
                $maxMarks = (float) ($examType?->default_max_mark ?? $exam->max_marks ?? 100);
                $minMarks = (float) ($examType?->default_min_mark ?? 0);

                $score = null;
                if ($hasScore) {
                    if (!is_numeric($scoreInput)) {
                        $skipped++;
                        continue;
                    }
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
                    'grade_label' => $g?->grade_name ?? ($mark->grade_label ?? 'BE'),
                    'pl_level' => $g?->grade_point ?? ($mark->pl_level ?? 1.0),
                    'subject_remark' => $hasRemark ? trim((string) $remarkInput) : $mark->subject_remark,
                    'status' => 'submitted',
                    'teacher_id' => optional($authUser->staff)->id,
                ])->save();
                $saved++;
            }
        }

        $message = "Saved {$saved} mark entries.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid or unauthorized entries.";
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

        return view('academics.exam_marks.matrix_edit', [
            'examType' => $examType,
            'classroom' => $classroom,
            'stream' => $stream,
            'students' => $students,
            'exams' => $exams,
            'existing' => $existing,
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
            $isSupervised = $user->isSeniorTeacherUser() && in_array($v['classroom_id'], $user->getSupervisedClassroomIds(), true);
            if (!$isDirectlyAssigned && !$hasSubjectAccess && !$isSupervised) {
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
            $isSupervised = $user->isSeniorTeacherUser() && in_array($classId, $user->getSupervisedClassroomIds(), true);
            if (!$isDirectlyAssigned && !$hasSubjectAccess && !$isSupervised) {
                abort(403, 'You do not have access to enter marks for this subject in this classroom.');
            }
        }

        return $this->renderBulkEditor($examId, $classId, $subId);
    }

    /** Shared renderer */
    private function renderBulkEditor($examId, $classId, $subjectId)
    {
        $exam    = Exam::findOrFail($examId);
        $class   = Classroom::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        // Enforce single-exam workflow: marks are per (exam, student, subject)
        // Exclude alumni and archived students; scope to teacher streams when applicable
        $studentsQuery = Student::where('classroom_id', $class->id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($exam->stream_id, fn ($q) => $q->where('stream_id', $exam->stream_id));

        $authUser = Auth::user();
        if ($authUser->hasTeacherLikeRole() && ! $authUser->hasAnyRole(['Super Admin', 'Admin'])) {
            $authUser->applyTeacherStudentFilter($studentsQuery);
        }

        $students = $studentsQuery->orderBy('last_name')->get();

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
                    || ($user->isSeniorTeacherUser() && in_array($data['classroom_id'], $user->getSupervisedClassroomIds(), true));
                if (!$hasSubjectAccess && !$isDirectOrSupervised) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to enter marks for this subject in this classroom.');
                }
            } else {
                $canAccess = $user->isAssignedToClassroom($data['classroom_id'])
                    || ($user->isSeniorTeacherUser() && in_array($data['classroom_id'], $user->getSupervisedClassroomIds(), true));
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
        if (!in_array($exam->status, ['open', 'marking'])) {
            return back()
                ->withInput()
                ->with('error', 'Cannot enter marks for this exam. Exam status must be "Open" or "Marking".');
        }

        // Marks boundaries come from exam type defaults when available.
        $examType = $exam->examType;
        $maxMarks = (float) ($examType?->default_max_mark ?? $exam->max_marks ?? 100);
        $minMarks = (float) ($examType?->default_min_mark ?? 0);

        // Validate scores against max marks
        foreach ($data['rows'] as $i => $row) {
            if (isset($row['score']) && $row['score'] !== '' && is_numeric($row['score'])) {
                $score = (float)$row['score'];
                if ($score < $minMarks || $score > $maxMarks) {
                    return back()
                        ->withInput()
                        ->with('error', "Score for student #{$i} must be between {$minMarks} and {$maxMarks}.");
                }
            }
        }

        foreach ($data['rows'] as $row) {
            // Validate student is not alumni or archived
            $student = Student::withAlumni()->find($row['student_id']);
            if ($student && ($student->is_alumni || $student->archive)) {
                continue; // Skip alumni/archived students
            }
            
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

            // If no direct score, calculate from component scores
            if (is_null($score) && $scores->count() > 0) {
                $score = $scores->avg();
            }

            $g = null;
            if (!is_null($score)) {
                // Validate score is within range
                if ($score < $minMarks || $score > $maxMarks) {
                    continue; // Skip invalid scores
                }

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
                    
                    if (!$hasAccess) {
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
                    
                    if (!$hasAccess) {
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

        $g = null;
        if (!is_null($finalScore)) {
            // Validate final score is within range
            if ($finalScore < $minMarks || $finalScore > $maxMarks) {
                return back()
                    ->withInput()
                    ->with('error', "Final score must be between {$minMarks} and {$maxMarks}.");
            }

            $g = ExamGrade::where('exam_type',$exam_mark->exam->type)
                ->where('percent_from','<=',$finalScore)
                ->where('percent_upto','>=',$finalScore)
                ->first();
        }

        $exam_mark->update(array_merge($v, [
            'score_raw'   => $finalScore,
            'grade_label' => $g?->grade_name ?? 'BE',
            'pl_level'    => $g?->grade_point ?? 1.0,
            'status'      => 'submitted',
            'teacher_id'  => optional(Auth::user()->staff)->id,
        ]));

        return redirect()
            ->route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id])
            ->with('success','Mark updated.');
    }
}

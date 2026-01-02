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
use Illuminate\Support\Facades\DB;

class ExamMarkController extends Controller
{
    public function __construct()
    {
        // Support both permission names for backward compatibility
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasAnyPermission(['exams.enter_marks', 'exam_marks.create'])
                && !$user->hasAnyRole(['Super Admin','Admin','System Admin'])
            ) {
                abort(403, 'You do not have permission to enter exam marks.');
            }
            return $next($request);
        })->only(['bulkForm', 'bulkEdit', 'bulkEditView', 'bulkStore', 'edit', 'update']);
        
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->hasAnyPermission(['exams.view', 'exam_marks.view'])
                && !$user->hasAnyRole(['Super Admin','Admin','System Admin'])
            ) {
                abort(403, 'You do not have permission to view exam marks.');
            }
            return $next($request);
        })->only(['index']);
    }

    public function index(Request $request)
    {
        $query = ExamMark::with(['student','subject','exam']);

        // Teachers can only see marks for their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $assignedClassroomIds = Auth::user()->getAssignedClassroomIds();
            
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
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $exams = Exam::whereIn('classroom_id', $assignedClassroomIds)->latest()->take(30)->get();
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
        // Filter exams and classrooms based on user role
        if (Auth::user()->hasRole('Teacher') || Auth::user()->hasRole('teacher')) {
            $user = Auth::user();
            $assignedClassroomIds = $user->getAssignedClassroomIds();
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

        $exam = Exam::findOrFail($v['exam_id']);

        // Check if teacher has access to this exam's classroom
        if (Auth::user()->hasRole('Teacher') || Auth::user()->hasRole('teacher')) {
            $user = Auth::user();
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            
            // Check if teacher is assigned to this classroom
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
            // 2. Teacher teaches this specific subject in this classroom
            if (!$isDirectlyAssigned && !$hasSubjectAccess) {
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

        // Check if teacher has access
        if (Auth::user()->hasRole('Teacher') || Auth::user()->hasRole('teacher')) {
            $user = Auth::user();
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            
            // Check if teacher is assigned to this classroom
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
            // 2. Teacher teaches this specific subject in this classroom
            if (!$isDirectlyAssigned && !$hasSubjectAccess) {
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
        // Exclude alumni and archived students
        $students = Student::where('classroom_id',$class->id)
            ->where('archive', 0)
            ->where('is_alumni', false)
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

        $exam = Exam::findOrFail($data['exam_id']);

        // Check if teacher has access to this exam's classroom
        if (Auth::user()->hasRole('Teacher')) {
            $user = Auth::user();
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            
            // Check if teacher is assigned to this classroom
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
                
                // If no subject-specific assignment, but teacher is assigned to class, allow access
                if (!$hasSubjectAccess && !$user->isAssignedToClassroom($data['classroom_id'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to enter marks for this subject in this classroom.');
                }
            } else {
                if (!$user->isAssignedToClassroom($data['classroom_id'])) {
                    abort(403, 'You do not have access to enter marks.');
                }
            }
        }

        // Check if exam allows mark entry
        if (!in_array($exam->status, ['open', 'marking'])) {
            return back()
                ->withInput()
                ->with('error', 'Cannot enter marks for this exam. Exam status must be "Open" or "Marking".');
        }

        // Get max marks from exam
        $maxMarks = $exam->max_marks ?? 100;
        $minMarks = 0;

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
        if (Auth::user()->hasRole('Teacher')) {
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
        if (Auth::user()->hasRole('Teacher')) {
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

        $maxMarks = $exam->max_marks ?? 100;
        $minMarks = 0;

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

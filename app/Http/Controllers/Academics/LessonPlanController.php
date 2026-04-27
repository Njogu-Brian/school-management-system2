<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\LessonPlan;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\LearningArea;
use App\Models\Academics\Homework;
use App\Models\Academics\HomeworkDiary;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\PDFExportService;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LessonPlanController extends Controller
{
    protected $pdfService;
    protected $excelService;

    public function __construct(PDFExportService $pdfService, ExcelExportService $excelService)
    {
        $this->pdfService = $pdfService;
        $this->excelService = $excelService;

        $this->middleware('permission:lesson_plans.view')->only(['index', 'show']);
        $this->middleware('permission:lesson_plans.create')->only(['create', 'store']);
        $this->middleware('permission:lesson_plans.edit')->only(['edit', 'update']);
        $this->middleware('permission:lesson_plans.delete')->only(['destroy']);
        $this->middleware('permission:lesson_plans.export_pdf')->only(['exportPdf']);
        $this->middleware('permission:lesson_plans.export_excel')->only(['exportExcel']);
        $this->middleware('permission:homework.create')->only(['assignHomeworkForm', 'assignHomework']);
    }

    public function index(Request $request)
    {
        $query = LessonPlan::with(['subject', 'classroom', 'academicYear', 'term', 'substrand', 'creator']);

        // Teachers can only see their assigned classes
        if (Auth::user()->hasTeacherLikeRole() && !is_supervisor()) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        
        // Supervisors can see their subordinates' lesson plans
        if (is_supervisor() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            if (!empty($subordinateClassroomIds)) {
                $query->whereIn('classroom_id', $subordinateClassroomIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filters
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('planned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('planned_date', '<=', $request->date_to);
        }

        $lessonPlans = $query->latest('planned_date')->paginate(20)->withQueryString();

        $classrooms = $this->getAccessibleClassrooms();
        $subjects = Subject::active()->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.lesson_plans.index', compact('lessonPlans', 'classrooms', 'subjects', 'years', 'terms'));
    }

    public function create(Request $request)
    {
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $schemes = SchemeOfWork::where('status', 'active')->with('subject', 'classroom')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        
        // Filter substrands based on selected subject and classroom
        $substrands = collect();
        if ($request->filled('subject_id') && $request->filled('classroom_id')) {
            $subject = Subject::find($request->subject_id);
            $classroom = Classroom::find($request->classroom_id);
            
            if ($subject && $classroom) {
                // Try to find learning area by code or name
                $learningArea = null;
                if ($subject->learning_area) {
                    $learningArea = LearningArea::where('code', $subject->learning_area)
                        ->orWhere('name', $subject->learning_area)
                        ->first();
                }

                $strandQuery = CBCStrand::where('is_active', true);

                if ($learningArea) {
                    // Use learning_area_id if available
                    $strandQuery->where('learning_area_id', $learningArea->id);
                } else {
                    // Fallback to old method using learning_area string
                    $strandQuery->where('learning_area', $subject->learning_area ?? $subject->name);
                }

                // Filter by level
                $level = $subject->level ?? $classroom->level ?? '';
                if ($level) {
                    $strandQuery->where('level', $level);
                }

                $strandIds = $strandQuery->orderBy('display_order')->orderBy('name')->pluck('id');
                
                if (!empty($strandIds)) {
                    $substrands = CBCSubstrand::where('is_active', true)
                        ->whereIn('strand_id', $strandIds)
                        ->with('strand.learningArea')
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        return view('academics.lesson_plans.create', compact('subjects', 'classrooms', 'schemes', 'years', 'terms', 'substrands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_of_work_id' => 'nullable|exists:schemes_of_work,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'substrand_id' => 'nullable|exists:cbc_substrands,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|string|max:50',
            'planned_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'learning_objectives' => 'nullable|array',
            'learning_outcomes' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'values' => 'nullable|array',
            'pclc' => 'nullable|array',
            'learning_resources' => 'nullable|array',
            'introduction' => 'nullable|string',
            'lesson_development' => 'nullable|string',
            'activities' => 'nullable|array',
            'assessment' => 'nullable|string',
            'conclusion' => 'nullable|string',
        ]);

        if (!$this->canAccessClassroom($validated['classroom_id'])) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        $validated['created_by'] = Auth::user()->staff?->id;
        $validated['status'] = 'planned';
        $validated['duration_minutes'] = $validated['duration_minutes'] ?? 40;

        $lessonPlan = LessonPlan::create($validated);

        return redirect()
            ->route('academics.lesson-plans.show', $lessonPlan)
            ->with('success', 'Lesson plan created successfully.');
    }

    public function show(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $lesson_plan->load([
            'subject', 'classroom', 'academicYear', 'term',
            'substrand.strand', 'schemeOfWork', 'creator'
        ]);

        return view('academics.lesson_plans.show', compact('lesson_plan'));
    }

    public function edit(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        if ($lesson_plan->isCompleted() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            return back()->with('error', 'Cannot edit completed lesson plan.');
        }

        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $schemes = SchemeOfWork::where('status', 'active')->with('subject', 'classroom')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $substrands = CBCSubstrand::active()->with('strand')->ordered()->get();

        return view('academics.lesson_plans.edit', compact('lesson_plan', 'subjects', 'classrooms', 'schemes', 'years', 'terms', 'substrands'));
    }

    public function update(Request $request, LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|string|max:50',
            'planned_date' => 'required|date',
            'actual_date' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'learning_objectives' => 'nullable|array',
            'learning_outcomes' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'values' => 'nullable|array',
            'pclc' => 'nullable|array',
            'learning_resources' => 'nullable|array',
            'introduction' => 'nullable|string',
            'lesson_development' => 'nullable|string',
            'activities' => 'nullable|array',
            'assessment' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'reflection' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'execution_status' => 'nullable|in:excellent,good,fair,poor',
            'challenges' => 'nullable|string',
            'improvements' => 'nullable|string',
        ]);

        $lesson_plan->update($validated);

        return redirect()
            ->route('academics.lesson-plans.show', $lesson_plan)
            ->with('success', 'Lesson plan updated successfully.');
    }

    public function approve(Request $request, LessonPlan $lesson_plan)
    {
        // Check if supervisor can approve this lesson plan
        if (!Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Director', 'Academic Administrator'])) {
            $allowedIds = [];
            if (Auth::user()->isSeniorTeacherUser()) {
                $allowedIds = Auth::user()->getSupervisedClassroomIds();
            } elseif (is_supervisor()) {
                $allowedIds = get_subordinate_classroom_ids();
            }
            if (!in_array($lesson_plan->classroom_id, array_map('intval', $allowedIds), true)) {
                abort(403, 'You can only approve lesson plans within your supervision scope.');
            }
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $lesson_plan->update([
            'submission_status' => 'approved',
            'approved_by' => Auth::user()->staff?->id,
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);

        return back()->with('success', 'Lesson plan approved successfully.');
    }

    public function reject(Request $request, LessonPlan $lesson_plan)
    {
        if (!Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Director', 'Academic Administrator'])) {
            $allowedIds = [];
            if (Auth::user()->isSeniorTeacherUser()) {
                $allowedIds = Auth::user()->getSupervisedClassroomIds();
            } elseif (is_supervisor()) {
                $allowedIds = get_subordinate_classroom_ids();
            }
            if (!in_array($lesson_plan->classroom_id, array_map('intval', $allowedIds), true)) {
                abort(403, 'You can only reject lesson plans within your supervision scope.');
            }
        }

        $request->validate([
            'rejection_notes' => 'required|string|max:1000',
        ]);

        $lesson_plan->update([
            'submission_status' => 'rejected',
            'rejected_by' => Auth::user()->staff?->id,
            'rejected_at' => now(),
            'rejection_notes' => $request->rejection_notes,
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);

        return back()->with('success', 'Lesson plan rejected.');
    }

    public function reviewQueue(Request $request)
    {
        $query = LessonPlan::with(['subject', 'classroom', 'academicYear', 'term', 'substrand', 'creator', 'approver', 'rejector'])
            ->where('submission_status', 'submitted');

        if (Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Director', 'Academic Administrator'])) {
            // global scope
        } elseif (Auth::user()->isSeniorTeacherUser()) {
            $ids = array_map('intval', Auth::user()->getSupervisedClassroomIds());
            $query->whereIn('classroom_id', $ids ?: [-1]);
        } elseif (is_supervisor()) {
            $ids = array_map('intval', get_subordinate_classroom_ids());
            $query->whereIn('classroom_id', $ids ?: [-1]);
        } else {
            abort(403);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->classroom_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', (int) $request->subject_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('planned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_date', '<=', $request->date_to);
        }

        $lessonPlans = $query->orderBy('planned_date')->paginate(20)->withQueryString();
        $classrooms = $this->getAccessibleClassrooms();
        $subjects = Subject::active()->orderBy('name')->get();

        return view('academics.lesson_plans.review_queue', compact('lessonPlans', 'classrooms', 'subjects'));
    }

    public function analytics(Request $request)
    {
        $days = (int) ($request->input('days', 7));
        $days = max(3, min(30, $days));
        $end = now()->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $teacherIds = \App\Models\Academics\Timetable::query()
            ->whereNotNull('staff_id')
            ->where('is_break', false)
            ->distinct()
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $rows = collect();
        foreach ($teacherIds as $staffId) {
            $expected = 0;
            $cursor = $start->copy();
            $occ = [];
            while ($cursor->lte($end)) {
                $occ[$cursor->dayName] = ($occ[$cursor->dayName] ?? 0) + 1;
                $cursor->addDay();
            }

            $byDay = \App\Models\Academics\Timetable::query()
                ->where('staff_id', $staffId)
                ->where('is_break', false)
                ->whereIn('day', array_keys($occ))
                ->selectRaw('day, count(*) as c')
                ->groupBy('day')
                ->get();
            foreach ($byDay as $r) {
                $expected += (int) $r->c * (int) ($occ[(string) $r->day] ?? 0);
            }
            if ($expected <= 0) {
                continue;
            }

            $submitted = LessonPlan::query()
                ->where('created_by', $staffId)
                ->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('submission_status', ['submitted', 'approved'])
                ->count();

            $staff = \App\Models\Staff::find($staffId);
            $rows->push([
                'staff_id' => $staffId,
                'teacher_name' => $staff?->full_name ?? ('Staff #'.$staffId),
                'expected' => $expected,
                'submitted' => $submitted,
                'consistency' => $expected ? round($submitted / $expected * 100, 1) : 0,
            ]);
        }

        $rows = $rows->sortBy('consistency')->values();

        return view('academics.lesson_plans.analytics', [
            'rows' => $rows,
            'days' => $days,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function destroy(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id) && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403);
        }

        $lesson_plan->delete();

        return redirect()
            ->route('academics.lesson-plans.index')
            ->with('success', 'Lesson plan deleted successfully.');
    }

    private function getAccessibleClassrooms()
    {
        if (Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Secretary'])) {
            return Classroom::orderBy('name')->get();
        }

        $staff = Auth::user()->staff;
        if (!$staff) {
            return collect();
        }

        // Supervisors can access their subordinates' classrooms
        if (is_supervisor()) {
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            $ownClassroomIds = DB::table('classroom_subjects')
                ->where('staff_id', $staff->id)
                ->distinct()
                ->pluck('classroom_id')
                ->toArray();
            
            $allClassroomIds = array_unique(array_merge($ownClassroomIds, $subordinateClassroomIds));
            return Classroom::whereIn('id', $allClassroomIds)->orderBy('name')->get();
        }

        // Regular teachers see only their own classrooms
        $classroomIds = DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();

        return Classroom::whereIn('id', $classroomIds)->orderBy('name')->get();
    }

    private function canAccessClassroom(int $classroomId): bool
    {
        if (Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Secretary'])) {
            return true;
        }

        $staff = Auth::user()->staff;
        if (!$staff) {
            return false;
        }

        // Supervisors can access their subordinates' classrooms
        if (is_supervisor()) {
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            $ownAccess = DB::table('classroom_subjects')
                ->where('staff_id', $staff->id)
                ->where('classroom_id', $classroomId)
                ->exists();
            
            return $ownAccess || in_array($classroomId, $subordinateClassroomIds);
        }

        return DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    /**
     * AJAX endpoint to fetch substrands based on subject and classroom
     */
    public function getSubstrands(Request $request)
    {
        $subjectId = $request->get('subject_id');
        $classroomId = $request->get('classroom_id');

        if (!$subjectId || !$classroomId) {
            return response()->json([]);
        }

        $subject = Subject::find($subjectId);
        $classroom = Classroom::find($classroomId);

        if (!$subject || !$classroom) {
            return response()->json([]);
        }

        // Try to find learning area by code or name
        $learningArea = null;
        if ($subject->learning_area) {
            $learningArea = LearningArea::where('code', $subject->learning_area)
                ->orWhere('name', $subject->learning_area)
                ->first();
        }

        $strandQuery = CBCStrand::where('is_active', true);

        if ($learningArea) {
            // Use learning_area_id if available
            $strandQuery->where('learning_area_id', $learningArea->id);
        } else {
            // Fallback to old method using learning_area string
            $strandQuery->where('learning_area', $subject->learning_area ?? $subject->name);
        }

        // Filter by level
        $level = $subject->level ?? $classroom->level ?? '';
        if ($level) {
            $strandQuery->where('level', $level);
        }

        $strandIds = $strandQuery->orderBy('display_order')->orderBy('name')->pluck('id');
        
        if (empty($strandIds)) {
            return response()->json([]);
        }

        $substrands = CBCSubstrand::where('is_active', true)
            ->whereIn('strand_id', $strandIds)
            ->with('strand.learningArea')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function($substrand) {
                return [
                    'id' => $substrand->id,
                    'name' => $substrand->name,
                    'code' => $substrand->code,
                    'strand_name' => $substrand->strand->name ?? '',
                    'strand_id' => $substrand->strand_id,
                    'learning_area' => $substrand->strand->learningArea->name ?? '',
                ];
            });

        return response()->json($substrands);
    }

    /**
     * Show form to assign homework from lesson plan
     */
    public function assignHomeworkForm(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $lesson_plan->load(['subject', 'classroom']);

        return view('academics.lesson_plans.assign_homework', compact('lesson_plan'));
    }

    /**
     * Assign homework from lesson plan
     */
    public function assignHomework(Request $request, LessonPlan $lesson_plan)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'due_date' => 'required|date|after:today',
            'max_score' => 'nullable|integer|min:1',
            'allow_late_submission' => 'boolean',
            'target_scope' => 'required|in:class,students',
            'student_ids' => 'nullable|array|required_if:target_scope,students',
            'student_ids.*' => 'exists:students,id',
        ]);

        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        DB::beginTransaction();
        try {
            // Create homework
            $homework = Homework::create([
                'assigned_by' => Auth::user()->id,
                'teacher_id' => Auth::user()->staff?->id,
                'classroom_id' => $lesson_plan->classroom_id,
                'subject_id' => $lesson_plan->subject_id,
                'lesson_plan_id' => $lesson_plan->id,
                'scheme_of_work_id' => $lesson_plan->scheme_of_work_id,
                'title' => $validated['title'],
                'instructions' => $validated['instructions'],
                'due_date' => $validated['due_date'],
                'max_score' => $validated['max_score'] ?? null,
                'allow_late_submission' => $validated['allow_late_submission'] ?? true,
                'target_scope' => $validated['target_scope'],
            ]);

            // Assign to students
            if ($validated['target_scope'] === 'students' && !empty($validated['student_ids'])) {
                $homework->students()->sync($validated['student_ids']);
                
                // Create homework diary entries for selected students
                foreach ($validated['student_ids'] as $studentId) {
                    HomeworkDiary::create([
                        'homework_id' => $homework->id,
                        'student_id' => $studentId,
                        'lesson_plan_id' => $lesson_plan->id,
                        'max_score' => $homework->max_score,
                        'status' => 'pending',
                    ]);
                }
            } else {
                // Assign to all students in classroom
                $students = \App\Models\Student::where('classroom_id', $lesson_plan->classroom_id)->get();
                foreach ($students as $student) {
                    HomeworkDiary::create([
                        'homework_id' => $homework->id,
                        'student_id' => $student->id,
                        'lesson_plan_id' => $lesson_plan->id,
                        'max_score' => $homework->max_score,
                        'status' => 'pending',
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('academics.homework.show', $homework)
                ->with('success', 'Homework assigned successfully from lesson plan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign homework: ' . $e->getMessage());
        }
    }

    /**
     * Export lesson plan as PDF
     */
    public function exportPdf(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $lesson_plan->load([
            'subject', 'classroom', 'academicYear', 'term',
            'substrand.strand', 'schemeOfWork', 'creator', 'homework'
        ]);

        $data = [
            'lesson_plan' => $lesson_plan,
        ];

        try {
            return $this->pdfService->generatePDF(
                'academics.lesson_plans.pdf',
                $data,
                [
                    'filename' => 'lesson_plan_' . $lesson_plan->id . '_' . date('Y-m-d') . '.pdf',
                    'paper_size' => 'A4',
                    'orientation' => 'portrait',
                ]
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export lesson plan as Excel
     */
    public function exportExcel(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $lesson_plan->load([
            'subject', 'classroom', 'academicYear', 'term',
            'substrand.strand', 'schemeOfWork', 'creator'
        ]);

        return $this->excelService->exportLessonPlans(
            collect([$lesson_plan]),
            'lesson_plan_' . $lesson_plan->id . '_' . date('Y-m-d') . '.xlsx'
        );
    }
}

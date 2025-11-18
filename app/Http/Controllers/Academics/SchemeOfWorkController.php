<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\LearningArea;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\SchemeOfWorkAutoGenerationService;
use App\Services\PDFExportService;
use App\Services\ExcelExportService;
use App\Jobs\GeneratePDFJob;
use App\Jobs\GenerateExcelJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SchemeOfWorkController extends Controller
{
    protected $autoGenerationService;
    protected $pdfService;
    protected $excelService;

    public function __construct(
        SchemeOfWorkAutoGenerationService $autoGenerationService,
        PDFExportService $pdfService,
        ExcelExportService $excelService
    ) {
        $this->autoGenerationService = $autoGenerationService;
        $this->pdfService = $pdfService;
        $this->excelService = $excelService;

        $this->middleware('permission:schemes_of_work.view')->only(['index', 'show']);
        $this->middleware('permission:schemes_of_work.create')->only(['create', 'store', 'generate']);
        $this->middleware('permission:schemes_of_work.edit')->only(['edit', 'update']);
        $this->middleware('permission:schemes_of_work.delete')->only(['destroy']);
        $this->middleware('permission:schemes_of_work.approve')->only(['approve']);
        $this->middleware('permission:schemes_of_work.export_pdf')->only(['exportPdf']);
        $this->middleware('permission:schemes_of_work.export_excel')->only(['exportExcel']);
        $this->middleware('permission:schemes_of_work.generate')->only(['generate']);
    }

    public function index(Request $request)
    {
        $query = SchemeOfWork::with(['subject', 'classroom', 'academicYear', 'term', 'creator']);

        // Teachers can only see their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = \DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
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

        $schemes = $query->latest()->paginate(20)->withQueryString();

        $classrooms = Classroom::orderBy('name')->get();
        $subjects = Subject::active()->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.schemes_of_work.index', compact('schemes', 'classrooms', 'subjects', 'years', 'terms'));
    }

    public function create(Request $request)
    {
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        
        // Get learning areas for selection
        $learningAreas = LearningArea::active()->ordered()->get();
        
        // Filter strands based on selected subject and classroom
        $strands = collect();
        $learningArea = null;
        
        if ($request->filled('subject_id') && $request->filled('classroom_id')) {
            $subject = Subject::find($request->subject_id);
            $classroom = Classroom::find($request->classroom_id);
            
            if ($subject && $classroom) {
                // Try to get learning area
                if ($subject->learning_area) {
                    $learningArea = LearningArea::where('code', $subject->learning_area)
                        ->orWhere('name', $subject->learning_area)
                        ->first();
                }

                if ($learningArea) {
                    $strands = CBCStrand::where('learning_area_id', $learningArea->id)
                        ->when($subject->level ?? $classroom->level, function($q) use ($subject, $classroom) {
                            $q->where('level', $subject->level ?? $classroom->level);
                        })
                        ->active()
                        ->ordered()
                        ->get();
                } else {
                    // Fallback to old method
                    $strands = CBCStrand::active()
                        ->where('learning_area', $subject->learning_area ?? $subject->name)
                        ->where('level', $subject->level ?? $classroom->level ?? '')
                        ->ordered()
                        ->get();
                }
            }
        }

        return view('academics.schemes_of_work.create', compact(
            'subjects', 
            'classrooms', 
            'years', 
            'terms', 
            'strands',
            'learningAreas',
            'learningArea'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strands_coverage' => 'nullable|array',
            'substrands_coverage' => 'nullable|array',
            'general_remarks' => 'nullable|string',
        ]);

        // Check authorization
        if (!$this->canAccessClassroom($validated['classroom_id'])) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        $validated['created_by'] = Auth::user()->staff?->id;
        $scheme = SchemeOfWork::create($validated);

        return redirect()
            ->route('academics.schemes-of-work.show', $scheme)
            ->with('success', 'Scheme of work created successfully.');
    }

    public function show(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403, 'You do not have access to this scheme of work.');
        }

        $schemes_of_work->load([
            'subject', 'classroom', 'academicYear', 'term',
            'creator', 'approver', 'lessonPlans'
        ]);

        return view('academics.schemes_of_work.show', compact('schemes_of_work'));
    }

    public function edit(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        if ($schemes_of_work->isApproved() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            return back()->with('error', 'Cannot edit approved scheme of work.');
        }

        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $strands = CBCStrand::active()->ordered()->get();

        return view('academics.schemes_of_work.edit', compact('schemes_of_work', 'subjects', 'classrooms', 'years', 'terms', 'strands'));
    }

    public function update(Request $request, SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strands_coverage' => 'nullable|array',
            'substrands_coverage' => 'nullable|array',
            'general_remarks' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,archived',
        ]);

        $schemes_of_work->update($validated);

        return redirect()
            ->route('academics.schemes-of-work.show', $schemes_of_work)
            ->with('success', 'Scheme of work updated successfully.');
    }

    public function destroy(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id) && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403);
        }

        if ($schemes_of_work->isApproved()) {
            return back()->with('error', 'Cannot delete approved scheme of work.');
        }

        $schemes_of_work->delete();

        return redirect()
            ->route('academics.schemes-of-work.index')
            ->with('success', 'Scheme of work deleted successfully.');
    }

    public function approve(Request $request, SchemeOfWork $schemes_of_work)
    {
        if (!Auth::user()->hasPermissionTo('schemes_of_work.approve')) {
            abort(403);
        }

        $schemes_of_work->update([
            'approved_at' => now(),
            'approved_by' => Auth::user()->staff?->id,
            'status' => 'active',
        ]);

        return back()->with('success', 'Scheme of work approved successfully.');
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

        $classroomIds = \DB::table('classroom_subjects')
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

        return \DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    /**
     * AJAX endpoint to fetch strands based on subject and classroom
     */
    public function getStrands(Request $request)
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

        $strands = $strandQuery->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function($strand) {
                return [
                    'id' => $strand->id,
                    'name' => $strand->name,
                    'code' => $strand->code,
                    'level' => $strand->level,
                ];
            });

        return response()->json($strands);
    }

    /**
     * Auto-generate scheme of work from CBC curriculum
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'strand_ids' => 'nullable|array',
            'strand_ids.*' => 'exists:cbc_strands,id',
            'total_lessons' => 'nullable|integer|min:1',
            'lessons_multiplier' => 'nullable|numeric|min:0.1|max:2',
            'description' => 'nullable|string',
            'general_remarks' => 'nullable|string',
            'status' => 'nullable|in:draft,active',
            'generate_lesson_plans' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'lessons_per_week' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            // Check authorization
            if (!$this->canAccessClassroom($validated['classroom_id'])) {
                return back()->with('error', 'You do not have access to this classroom.');
            }

            // Auto-generate scheme of work
            $schemeOfWork = $this->autoGenerationService->generate($validated);

            // Generate lesson plans if requested
            if ($request->boolean('generate_lesson_plans')) {
                $this->autoGenerationService->generateLessonPlans($schemeOfWork, [
                    'start_date' => $validated['start_date'] ?? now()->toDateString(),
                    'lessons_per_week' => $validated['lessons_per_week'] ?? 5,
                    'duration_minutes' => 40,
                ]);
            }

            return redirect()
                ->route('academics.schemes-of-work.show', $schemeOfWork)
                ->with('success', 'Scheme of work auto-generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate scheme of work: ' . $e->getMessage());
        }
    }

    /**
     * Export scheme of work as PDF
     */
    public function exportPdf(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        $schemes_of_work->load([
            'subject', 'classroom', 'academicYear', 'term',
            'creator', 'approver', 'lessonPlans'
        ]);

        $data = [
            'scheme' => $schemes_of_work,
        ];

        return $this->pdfService->generatePDF(
            'academics.schemes_of_work.pdf',
            $data,
            [
                'filename' => 'scheme_of_work_' . $schemes_of_work->id . '.pdf',
                'paper_size' => 'A4',
                'orientation' => 'portrait',
            ]
        );
    }

    /**
     * Export scheme of work as Excel
     */
    public function exportExcel(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        $schemes_of_work->load([
            'subject', 'classroom', 'academicYear', 'term',
            'creator', 'approver', 'lessonPlans'
        ]);

        return $this->excelService->exportSchemesOfWork(
            collect([$schemes_of_work]),
            'scheme_of_work_' . $schemes_of_work->id . '_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Bulk export schemes of work
     */
    public function bulkExport(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,excel',
            'scheme_ids' => 'required|array',
            'scheme_ids.*' => 'exists:schemes_of_work,id',
            'use_queue' => 'nullable|boolean',
        ]);

        $schemes = SchemeOfWork::whereIn('id', $validated['scheme_ids'])
            ->with(['subject', 'classroom', 'academicYear', 'term', 'creator', 'approver'])
            ->get();

        // Filter by access
        $schemes = $schemes->filter(function($scheme) {
            return $this->canAccessClassroom($scheme->classroom_id);
        });

        if ($schemes->isEmpty()) {
            return back()->with('error', 'No schemes selected for export.');
        }

        // Use queue for bulk exports if requested or if more than 10 items
        $useQueue = $validated['use_queue'] ?? ($schemes->count() > 10);

        if ($validated['format'] === 'pdf') {
            if ($useQueue) {
                // Queue PDF generation for bulk export
                foreach ($schemes as $scheme) {
                    GeneratePDFJob::dispatch(
                        'academics.schemes_of_work.pdf',
                        ['scheme' => $scheme],
                        [
                            'filename' => 'scheme_of_work_' . $scheme->id . '.pdf',
                            'save' => true,
                            'notify' => true,
                        ],
                        Auth::id()
                    );
                }
                return back()->with('success', 'PDF generation queued. You will be notified when complete.');
            } else {
                // For bulk PDF, we might want to create a zip file
                // For now, export first scheme
                return $this->exportPdf($schemes->first());
            }
        } else {
            // Excel export
            if ($useQueue) {
                // Queue Excel generation
                GenerateExcelJob::dispatch(
                    $schemes,
                    [],
                    'schemes_of_work_' . date('Y-m-d') . '_' . time() . '.xlsx',
                    [
                        'save' => true,
                        'notify' => true,
                    ],
                    Auth::id(),
                    'schemes_of_work'
                );
                return back()->with('success', 'Excel generation queued. You will be notified when complete.');
            } else {
                return $this->excelService->exportSchemesOfWork(
                    $schemes,
                    'schemes_of_work_' . date('Y-m-d') . '.xlsx'
                );
            }
        }
    }
}

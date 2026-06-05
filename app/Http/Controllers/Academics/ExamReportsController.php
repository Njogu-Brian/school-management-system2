<?php

namespace App\Http\Controllers\Academics;

use App\Exports\ClassSheetExport;
use App\Exports\ClassSheetsWorkbookExport;
use App\Exports\TermWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\ExamReports\AnalyticsService;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use App\Services\Academics\ExamReports\ClassSheetSubjectResolver;
use App\Services\Academics\ExamReports\ExamReportsAccess;
use App\Services\Academics\ExamReports\ExamScopeResolver;
use App\Services\Academics\ExamReports\ReportCache;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use App\Services\Academics\ExamReports\TermScopeResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ExamReportsController extends Controller
{
    public function __construct(
        private readonly TermScopeResolver $termScope = new TermScopeResolver(),
        private readonly ExamScopeResolver $examScope = new ExamScopeResolver(),
    ) {}

    public function classSheet(Request $request)
    {
        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($u);

        $sheetFlow = $request->input('sheet_flow', 'by_exam_type');

        if ($request->filled('load')) {
            $this->validateClassSheetRequest($request);
            $bundles = $this->buildClassSheetBundles($request, $u);
        } else {
            $bundles = [];
        }

        $examTypes = ExamType::orderBy('name')->get();
        $subjects = Subject::query()->where('is_active', true)->orderBy('name')->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $allowedClassIds = $classrooms->pluck('id')->all();

        $classroomExamTypeIds = $allowedClassIds === [] ? [] : Exam::query()
            ->whereNotNull('exam_type_id')
            ->whereNotNull('classroom_id')
            ->whereIn('classroom_id', $allowedClassIds)
            ->select('classroom_id', 'exam_type_id')
            ->distinct()
            ->get()
            ->groupBy('classroom_id')
            ->map(fn ($g) => $g->pluck('exam_type_id')->unique()->values()->all())
            ->toArray();

        $filterScopes = $this->buildFilterScopes($allowedClassIds);
        $subjectExamScopes = $filterScopes['subjectExamScopes'];
        $termYearClassScopes = $filterScopes['termYearClassScopes'];

        $streamsByClassroom = $allowedClassIds === [] ? [] : Stream::query()
            ->whereIn('classroom_id', $allowedClassIds)
            ->orderBy('name')
            ->get()
            ->groupBy('classroom_id')
            ->map(fn ($rows) => $rows->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values()->all())
            ->toArray();

        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get();

        return view('academics.exam_reports.class_sheet', compact(
            'sheetFlow',
            'bundles',
            'examTypes',
            'subjects',
            'classrooms',
            'classroomExamTypeIds',
            'subjectExamScopes',
            'termYearClassScopes',
            'streamsByClassroom',
            'academicYears',
            'terms',
            'examReportsFullAccess'
        ));
    }

    public function exportClassSheet(Request $request)
    {
        $this->validateClassSheetRequest($request);
        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $bundles = $this->buildClassSheetBundles($request, $u);
        $sheets = [];
        foreach ($bundles as $b) {
            if (empty($b['payload'])) {
                continue;
            }
            $cls = $b['classroom']->name ?? 'Class';
            $sheets[] = [
                'title' => $this->excelSheetTitle($b['payload'], $cls),
                'payload' => $b['payload'],
            ];
        }

        if ($sheets === []) {
            abort(422, 'Nothing to export. Check filters or ensure mark data exists.');
        }

        $flow = $request->input('sheet_flow');
        $filename = 'class-mark-sheets-'.$flow.'-'.now()->format('Y-m-d-His').'.xlsx';
        $generatedBy = $user?->name ?? 'System';

        if (count($sheets) === 1) {
            return Excel::download(new ClassSheetExport($sheets[0]['payload'], $sheets[0]['title'], $generatedBy), $filename);
        }

        return Excel::download(new ClassSheetsWorkbookExport($sheets, $generatedBy), $filename);
    }

    public function exportClassSheetPdf(Request $request)
    {
        $this->validateClassSheetRequest($request);
        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $bundles = $this->buildClassSheetBundles($request, $u);
        $pdfBundles = [];
        foreach ($bundles as $b) {
            if (empty($b['payload'])) {
                continue;
            }
            $pdfBundles[] = [
                'classroom' => $b['classroom'],
                'payload' => $b['payload'],
                'notice' => $b['notice'],
            ];
        }

        if ($pdfBundles === []) {
            abort(422, 'Nothing to export. Check filters or ensure mark data exists.');
        }

        $pdf = Pdf::loadView('academics.exam_reports.class_sheet_pdf', [
            'bundles' => $pdfBundles,
            'sheetFlow' => $request->input('sheet_flow'),
            'generatedAt' => now(),
            'generatedBy' => $user?->name ?? 'System',
        ])->setPaper('a4', 'landscape');

        $filename = 'class-mark-sheets-'.$request->input('sheet_flow').'-'.now()->format('Y-m-d-His').'.pdf';

        return $pdf->download($filename);
    }

    private function validateClassSheetRequest(Request $request): void
    {
        $flow = $request->input('sheet_flow', 'by_exam_type');

        if ($flow === 'by_exam_type') {
            Validator::make($request->all(), [
                'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
                'exam_type_id' => 'required|exists:exam_types,id',
                'classroom_id' => 'required|exists:classrooms,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => 'required|exists:terms,id',
                'stream_id' => 'nullable|exists:streams,id',
            ])->validate();
        } elseif ($flow === 'by_subject') {
            Validator::make($request->all(), [
                'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
                'subject_id' => 'required|exists:subjects,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => 'required|exists:terms,id',
                'stream_id' => 'nullable|exists:streams,id',
                'classroom_ids' => 'required|array|min:1',
                'classroom_ids.*' => 'exists:classrooms,id',
            ])->validate();
        } else {
            Validator::make($request->all(), [
                'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
                'classroom_id' => 'required|exists:classrooms,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => 'required|exists:terms,id',
                'stream_id' => 'nullable|exists:streams,id',
            ])->validate();
        }
    }

    /**
     * @return list<array{classroom: Classroom, payload: ?array, notice: ?string}>
     */
    private function buildClassSheetBundles(Request $request, ?User $user): array
    {
        $flow = $request->input('sheet_flow', 'by_exam_type');
        $streamId = $request->integer('stream_id') ?: null;
        $cache = new ReportCache();
        $builder = new ClassSheetBuilder();
        $resolver = new ClassSheetSubjectResolver();

        if ($flow === 'by_exam_type') {
            $classroom = Classroom::findOrFail((int) $request->classroom_id);
            ExamReportsAccess::assertClassroomAccess($user, $classroom->id);

            $session = $this->examScope->findExamSession(
                (int) $request->exam_type_id,
                (int) $request->academic_year_id,
                (int) $request->term_id,
                $classroom->id,
                $streamId
            );

            if (! $session) {
                return [[
                    'classroom' => $classroom,
                    'payload' => null,
                    'notice' => 'No exam sitting found for this exam type, class, academic year, and term. Create exams for this combination or adjust the stream filter.',
                ]];
            }

            $payload = $cache->rememberExamSessionClassSheet(
                $session,
                $classroom,
                $streamId,
                fn () => $builder->buildForExamSession($session, $classroom, $streamId)
            );

            return [['classroom' => $classroom, 'payload' => $payload, 'notice' => null]];
        }

        if ($flow === 'by_subject') {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('classroom_ids', [])))));
            $out = [];
            foreach ($ids as $cid) {
                $classroom = Classroom::find($cid);
                if (! $classroom) {
                    continue;
                }
                ExamReportsAccess::assertClassroomAccess($user, $cid);
                $exam = $resolver->resolveExam(
                    (int) $request->subject_id,
                    $cid,
                    (int) $request->academic_year_id,
                    (int) $request->term_id,
                    $streamId
                );
                if (! $exam) {
                    $out[] = [
                        'classroom' => $classroom,
                        'payload' => null,
                        'notice' => 'No exam paper found for this subject, class, year, and term.',
                    ];

                    continue;
                }
                $payload = $cache->rememberExamClassSheet(
                    $exam,
                    $classroom,
                    $streamId,
                    fn () => $builder->buildForSingleSubjectExam($exam, $classroom, $streamId)
                );
                $out[] = ['classroom' => $classroom, 'payload' => $payload, 'notice' => null];
            }

            return $out;
        }

        $classroom = Classroom::findOrFail((int) $request->classroom_id);
        ExamReportsAccess::assertClassroomAccess($user, $classroom->id);
        $ay = (int) $request->academic_year_id;
        $termId = (int) $request->term_id;
        $payload = $cache->rememberTermClassSheet(
            $ay,
            $termId,
            $classroom,
            $streamId,
            fn () => $builder->buildForTerm($ay, $termId, $classroom, $streamId)
        );

        return [['classroom' => $classroom, 'payload' => $payload, 'notice' => null]];
    }

    private function excelSheetTitle(array $payload, string $classLabel): string
    {
        $meta = $payload['meta'] ?? [];
        $mode = $meta['mode'] ?? '';

        if ($mode === 'term') {
            return mb_substr($classLabel.' Term', 0, 31);
        }
        if ($mode === 'exam_session') {
            $name = $meta['exam_session']['name'] ?? 'Sitting';

            return mb_substr($classLabel.' '.$name, 0, 31);
        }
        if ($mode === 'subject_paper') {
            $sub = $meta['subject']['name'] ?? 'Subject';

            return mb_substr($classLabel.' '.$sub, 0, 31);
        }

        return mb_substr($classLabel.' Sheet', 0, 31);
    }

    public function exportTermWorkbook(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $ay = (int) $request->academic_year_id;
        $termId = (int) $request->term_id;

        $classroomIds = [];
        if (! ExamReportsAccess::userHasFullAccess($u)) {
            $classroomIds = ExamReportsAccess::allowedClassroomIdsFor($u);
            if ($classroomIds === []) {
                abort(403, 'No classes are assigned for exam reports.');
            }
        }

        $filename = "term-workbook-{$ay}-{$termId}.xlsx";

        return Excel::download(new TermWorkbookExport($ay, $termId, $classroomIds), $filename);
    }

    public function teacherPerformance(Request $request, AnalyticsService $analytics, SchoolWideTeacherRankingService $schoolRankings)
    {
        $request->validate([
            'scope' => 'nullable|in:class,school',
            'analysis_flow' => 'nullable|in:by_exam_type,term',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'exam_type_id' => 'nullable|exists:exam_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'exam_id' => 'nullable|exists:exams,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($u);
        $subjectScopedTeacher = ExamReportsAccess::userIsSubjectScoped($u);

        $scope = $request->input('scope', 'class');
        if (! $examReportsFullAccess) {
            $scope = 'class';
        }

        $analysisFlow = $request->input('analysis_flow', 'by_exam_type');
        $filterData = $this->analysisFilterViewData($u);
        $payload = null;
        $notice = null;

        if ($request->filled('load')) {
            if ($scope === 'school' && $request->filled('exam_id')) {
                ExamReportsAccess::assertSchoolWideReportsAllowed($u);
                $exam = Exam::find($request->exam_id);
                if ($exam) {
                    $cache = new ReportCache();
                    $subjectId = $request->integer('subject_id') ?: null;
                    $payload = $cache->rememberSchoolTeacherPerformance($exam, $subjectId, fn () => $schoolRankings->rankingsForExam($exam, $subjectId));
                }
            } else {
                [$payload, $notice] = $this->buildTeacherPerformancePayload($request, $u, $analytics, $analysisFlow);
            }
        }

        return view('academics.exam_reports.teacher_performance', array_merge($filterData, compact(
            'scope',
            'analysisFlow',
            'payload',
            'notice',
            'examReportsFullAccess',
            'subjectScopedTeacher'
        )));
    }

    public function subjectPerformance(Request $request, AnalyticsService $analytics)
    {
        $request->validate([
            'analysis_flow' => 'nullable|in:by_exam_type,term',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'exam_type_id' => 'nullable|exists:exam_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $subjectScopedTeacher = ExamReportsAccess::userIsSubjectScoped($u);
        $analysisFlow = $request->input('analysis_flow', 'by_exam_type');
        $filterData = $this->analysisFilterViewData($u);

        $payload = null;
        $notice = null;
        if ($request->filled('load')) {
            [$payload, $notice] = $this->buildSubjectPerformancePayload($request, $u, $analytics, $analysisFlow);
        }

        return view('academics.exam_reports.subject_performance', array_merge($filterData, compact(
            'analysisFlow',
            'payload',
            'notice',
            'subjectScopedTeacher'
        )));
    }

    public function studentInsights(Request $request, AnalyticsService $analytics)
    {
        $request->validate([
            'mode' => 'nullable|in:exam,term',
            'exam_id' => 'nullable|exists:exams,id',
            'academic_year_id' => 'nullable|integer',
            'term_id' => 'nullable|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $mode = $request->input('mode', 'exam');
        $exams = Exam::with(['academicYear', 'term'])->orderByDesc('created_at')->limit(60)->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $streams = Stream::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get();

        $payload = null;
        if ($request->filled('classroom_id')) {
            ExamReportsAccess::assertClassroomAccess($u, (int) $request->classroom_id);
            $classroom = Classroom::find($request->classroom_id);
            if ($classroom) {
                $cache = new ReportCache();
                $streamId = $request->integer('stream_id') ?: null;

                if ($mode === 'term' && $request->filled('academic_year_id') && $request->filled('term_id')) {
                    $ay = (int) $request->academic_year_id;
                    $termId = (int) $request->term_id;
                    $payload = $cache->rememberTermStudentInsights($ay, $termId, $classroom, $streamId, fn () => $analytics->studentInsightsForTerm($ay, $termId, $classroom, $streamId));
                }

                if ($mode === 'exam' && $request->filled('exam_id')) {
                    $exam = Exam::find($request->exam_id);
                    if ($exam) {
                        $payload = $cache->rememberExamStudentInsights($exam, $classroom, $streamId, fn () => $analytics->studentInsightsForExam($exam, $classroom, $streamId));
                    }
                }
            }
        }

        return view('academics.exam_reports.student_insights', compact('mode', 'exams', 'classrooms', 'streams', 'academicYears', 'terms', 'payload'));
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisFilterViewData(?User $user): array
    {
        $classrooms = ExamReportsAccess::classroomsQueryFor($user)->get();
        $allowedClassIds = $classrooms->pluck('id')->all();
        $filterScopes = $this->buildFilterScopes($allowedClassIds);

        return [
            'examTypes' => ExamType::orderBy('name')->get(),
            'classrooms' => $classrooms,
            'classroomExamTypeIds' => $filterScopes['classroomExamTypeIds'],
            'termYearClassScopes' => $filterScopes['termYearClassScopes'],
            'streamsByClassroom' => $allowedClassIds === [] ? [] : Stream::query()
                ->whereIn('classroom_id', $allowedClassIds)
                ->orderBy('name')
                ->get()
                ->groupBy('classroom_id')
                ->map(fn ($rows) => $rows->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values()->all())
                ->toArray(),
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'terms' => Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get(),
        ];
    }

    /**
     * @param  int[]  $allowedClassIds
     * @return array{classroomExamTypeIds: array, subjectExamScopes: list<array>, termYearClassScopes: list<array>}
     */
    private function buildFilterScopes(array $allowedClassIds): array
    {
        if ($allowedClassIds === []) {
            return [
                'classroomExamTypeIds' => [],
                'subjectExamScopes' => [],
                'termYearClassScopes' => [],
            ];
        }

        $classroomExamTypeIds = Exam::query()
            ->whereNotNull('exam_type_id')
            ->whereNotNull('classroom_id')
            ->whereIn('classroom_id', $allowedClassIds)
            ->select('classroom_id', 'exam_type_id')
            ->distinct()
            ->get()
            ->groupBy('classroom_id')
            ->map(fn ($g) => $g->pluck('exam_type_id')->unique()->values()->all())
            ->toArray();

        $rawSubjectScopes = Exam::query()
            ->whereNotNull('subject_id')
            ->whereNotNull('classroom_id')
            ->whereIn('classroom_id', $allowedClassIds)
            ->select('subject_id', 'classroom_id', 'academic_year_id', 'term_id')
            ->distinct()
            ->get()
            ->map(fn ($e) => [
                'subject_id' => (int) $e->subject_id,
                'classroom_id' => (int) $e->classroom_id,
                'academic_year_id' => (int) $e->academic_year_id,
                'term_id' => (int) $e->term_id,
            ])
            ->values()
            ->all();

        $rawTermScopes = Exam::query()
            ->whereNotNull('classroom_id')
            ->whereIn('classroom_id', $allowedClassIds)
            ->select('academic_year_id', 'term_id', 'classroom_id')
            ->distinct()
            ->get()
            ->map(fn ($e) => [
                'academic_year_id' => (int) $e->academic_year_id,
                'term_id' => (int) $e->term_id,
                'classroom_id' => (int) $e->classroom_id,
            ])
            ->values()
            ->all();

        return [
            'classroomExamTypeIds' => $classroomExamTypeIds,
            'subjectExamScopes' => $this->termScope->expandSubjectExamScopes($rawSubjectScopes),
            'termYearClassScopes' => $this->termScope->expandTermClassScopes($rawTermScopes),
        ];
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function buildSubjectPerformancePayload(Request $request, ?User $user, AnalyticsService $analytics, string $analysisFlow): array
    {
        $streamId = $request->integer('stream_id') ?: null;
        $classroomId = $request->integer('classroom_id');
        if (! $classroomId) {
            return [null, 'Select a class.'];
        }

        ExamReportsAccess::assertClassroomAccess($user, $classroomId);
        $classroom = Classroom::findOrFail($classroomId);
        $subjectLimit = ExamReportsAccess::subjectIdsForUserInClass($user, $classroomId, $streamId);
        $cache = new ReportCache();

        if ($analysisFlow === 'term') {
            $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => 'required|exists:terms,id',
            ]);
            $ay = (int) $request->academic_year_id;
            $termId = (int) $request->term_id;
            $payload = $cache->rememberTermSubjectPerformance($ay, $termId, $classroom, $streamId, fn () => $analytics->subjectPerformanceForTerm($ay, $termId, $classroom, $streamId, $subjectLimit));
            if (empty($payload['subjects'])) {
                return [$payload, 'No mark data found for this class and term. Check that exams exist and marks have been entered.'];
            }

            return [$payload, null];
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'exam_type_id' => 'required|exists:exam_types,id',
        ]);

        $session = $this->examScope->findExamSession(
            (int) $request->exam_type_id,
            (int) $request->academic_year_id,
            (int) $request->term_id,
            $classroomId,
            $streamId
        );

        if (! $session) {
            return [null, 'No exam sitting found for this exam type, class, year, and term.'];
        }

        $payload = $cache->rememberExamSessionSubjectPerformance($session, $classroom, $streamId, fn () => $analytics->subjectPerformanceForExamSession($session, $classroom, $streamId, $subjectLimit));
        if (empty($payload['subjects'])) {
            return [$payload, 'No mark data found for this exam sitting. Ensure marks have been entered for this class.'];
        }

        return [$payload, null];
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function buildTeacherPerformancePayload(Request $request, ?User $user, AnalyticsService $analytics, string $analysisFlow): array
    {
        $streamId = $request->integer('stream_id') ?: null;
        $classroomId = $request->integer('classroom_id');
        if (! $classroomId) {
            return [null, 'Select a class.'];
        }

        ExamReportsAccess::assertClassroomAccess($user, $classroomId);
        $classroom = Classroom::findOrFail($classroomId);
        $subjectLimit = ExamReportsAccess::subjectIdsForUserInClass($user, $classroomId, $streamId);
        $cache = new ReportCache();

        if ($analysisFlow === 'term') {
            $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => 'required|exists:terms,id',
            ]);
            $ay = (int) $request->academic_year_id;
            $termId = (int) $request->term_id;
            $payload = $cache->rememberTermTeacherPerformance($ay, $termId, $classroom, $streamId, fn () => $analytics->teacherPerformanceForTerm($ay, $termId, $classroom, $streamId, $subjectLimit));
            if (empty($payload['per_teacher']) && empty($payload['per_subject'])) {
                return [$payload, 'No teacher performance data for this class and term. Check subject assignments and marks.'];
            }

            return [$payload, null];
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'exam_type_id' => 'required|exists:exam_types,id',
        ]);

        $session = $this->examScope->findExamSession(
            (int) $request->exam_type_id,
            (int) $request->academic_year_id,
            (int) $request->term_id,
            $classroomId,
            $streamId
        );

        if (! $session) {
            return [null, 'No exam sitting found for this exam type, class, year, and term.'];
        }

        $payload = $cache->rememberExamSessionTeacherPerformance($session, $classroom, $streamId, fn () => $analytics->teacherPerformanceForExamSession($session, $classroom, $streamId, $subjectLimit));
        if (empty($payload['per_teacher']) && empty($payload['per_subject'])) {
            return [$payload, 'No teacher performance data for this exam sitting. Check subject assignments and marks.'];
        }

        return [$payload, null];
    }
}

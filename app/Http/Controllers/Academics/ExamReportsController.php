<?php

namespace App\Http\Controllers\Academics;

use App\Exports\ClassSheetExport;
use App\Exports\ClassSheetsWorkbookExport;
use App\Exports\TeacherPerformanceExport;
use App\Exports\TermWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Support\AcademicContext;
use App\Models\User;
use App\Services\Academics\ExamReports\AnalyticsService;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use App\Services\Academics\ExamReports\ClassSheetSubjectResolver;
use App\Services\Academics\ExamReports\ExamReportsAccess;
use App\Services\Academics\ExamReports\ExamScopeResolver;
use App\Services\Academics\ExamReports\ReportCache;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use App\Services\Academics\ExamReports\TermScopeResolver;
use App\Services\Pdf\ChunkedDompdfExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $bundles = [];
        $loadErrors = null;

        // GET validation must not redirect to the bare URL — that re-defaults to the
        // calendar "current" year (often next-term prep) and looks like filters reset.
        if ($request->filled('load')) {
            $validator = $this->classSheetValidator($request);
            if ($validator->fails()) {
                $loadErrors = $validator->errors();
            } else {
                $bundles = $this->buildClassSheetBundles($request, $u);
            }
        }

        $examTypes = $this->analysisExamTypes();
        $subjects = Subject::query()->where('is_active', true)->orderBy('name')->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $allowedClassIds = $classrooms->pluck('id')->all();
        $coreExamTypeIds = $examTypes->pluck('id')->all();

        $classroomExamTypeIds = $allowedClassIds === [] ? [] : Exam::query()
            ->whereNotNull('exam_type_id')
            ->whereNotNull('classroom_id')
            ->whereIn('classroom_id', $allowedClassIds)
            ->when($coreExamTypeIds !== [], fn ($q) => $q->whereIn('exam_type_id', $coreExamTypeIds))
            ->select('classroom_id', 'exam_type_id')
            ->distinct()
            ->get()
            ->groupBy('classroom_id')
            ->map(fn ($g) => $g->pluck('exam_type_id')->unique()->values()->all())
            ->toArray();

        $filterScopes = $this->buildFilterScopes($allowedClassIds);
        $subjectExamScopes = $filterScopes['subjectExamScopes'];
        $termYearClassScopes = $filterScopes['termYearClassScopes'];

        $streamsByClassroom = $this->streamsByClassroomForFilters($allowedClassIds);

        $academic = AcademicContext::forFilterRequest(
            hasYearParam: $request->has('academic_year_id'),
            yearValue: $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            hasTermParam: $request->has('term_id'),
            termValue: $request->filled('term_id') ? (int) $request->term_id : null,
        );

        $view = view('academics.exam_reports.class_sheet', array_merge(compact(
            'sheetFlow',
            'bundles',
            'examTypes',
            'subjects',
            'classrooms',
            'classroomExamTypeIds',
            'subjectExamScopes',
            'termYearClassScopes',
            'streamsByClassroom',
            'examReportsFullAccess'
        ), $academic, [
            'academicYears' => $academic['years'],
        ]));

        return $loadErrors ? $view->withErrors($loadErrors) : $view;
    }

    public function exportClassSheet(Request $request)
    {
        $this->validateClassSheetRequest($request);
        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $bundles = $this->buildClassSheetBundles($request, $u);
        $sheets = [];
        $usedTitles = [];
        foreach ($bundles as $b) {
            if (empty($b['payload'])) {
                continue;
            }
            $cls = $b['classroom']->name ?? 'Class';
            $sheets[] = [
                'title' => $this->uniqueExcelSheetTitle($this->excelSheetTitle($b['payload'], $cls), $usedTitles),
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

        $sheetFlow = $request->input('sheet_flow');
        $generatedAt = now();
        $generatedBy = $user?->name ?? 'System';
        $filename = 'class-mark-sheets-'.$sheetFlow.'-'.now()->format('Y-m-d-His').'.pdf';

        try {
            return app(ChunkedDompdfExporter::class)->download(
                $pdfBundles,
                function (Collection $chunk) use ($sheetFlow, $generatedAt, $generatedBy) {
                    return app(ChunkedDompdfExporter::class)->renderView('academics.exam_reports.class_sheet_pdf', [
                        'bundles' => $chunk->values()->all(),
                        'sheetFlow' => $sheetFlow,
                        'generatedAt' => $generatedAt,
                        'generatedBy' => $generatedBy,
                    ], 'a4', 'landscape');
                },
                [
                    'filename' => $filename,
                    'chunk_size' => 1,
                    'allow_over_sync' => true,
                ]
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function validateClassSheetRequest(Request $request): void
    {
        $this->classSheetValidator($request)->validate();
    }

    private function classSheetValidator(Request $request): \Illuminate\Validation\Validator
    {
        $flow = $request->input('sheet_flow', 'by_exam_type');
        $termBelongsToYear = function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
            if (! $request->filled('academic_year_id') || ! $value) {
                return;
            }
            if (! AcademicContext::termBelongsToYear((int) $value, (int) $request->academic_year_id)) {
                $fail('The selected term does not belong to the selected academic year. Pick the year again, then the matching term.');
            }
        };

        if ($flow === 'by_exam_type') {
            return Validator::make($request->all(), [
                'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
                'exam_type_id' => 'required|exists:exam_types,id',
                'classroom_id' => 'required|exists:classrooms,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => ['required', 'exists:terms,id', $termBelongsToYear],
                'stream_id' => 'nullable|exists:streams,id',
            ]);
        }

        if ($flow === 'by_subject') {
            return Validator::make($request->all(), [
                'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
                'subject_id' => 'required|exists:subjects,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'term_id' => ['required', 'exists:terms,id', $termBelongsToYear],
                'stream_id' => 'nullable|exists:streams,id',
                'classroom_ids' => 'required|array|min:1',
                'classroom_ids.*' => 'exists:classrooms,id',
            ]);
        }

        return Validator::make($request->all(), [
            'sheet_flow' => 'required|in:by_exam_type,by_subject,term',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => ['required', 'exists:terms,id', $termBelongsToYear],
            'stream_id' => 'nullable|exists:streams,id',
        ]);
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

            $session->loadMissing('examType');
            if (ExamScopeResolver::isSideExamType($session->examType?->name, $session->examType?->code)) {
                return [[
                    'classroom' => $classroom,
                    'payload' => null,
                    'notice' => 'KNEC CBA is excluded from exam report analysis. Choose Mid Term, End Term, or another core exam type.',
                ]];
            }

            $payload['most_improved'] = (new AnalyticsService())->mostImprovedForExamSession(
                $session,
                $classroom,
                $streamId,
                $payload
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

        $papers = $this->examScope->papersForTerm($ay, $termId, $classroom->id, $streamId);
        if ($papers->isEmpty()) {
            return [[
                'classroom' => $classroom,
                'payload' => null,
                'notice' => 'No exam papers found for this class, academic year, and term. Open Manage Exams for that year/term, or pick a different year and matching term.',
            ]];
        }

        $analytics = new AnalyticsService();
        $bundles = [];

        $sessions = $this->examScope->coreExamSessionsForTerm($ay, $termId, $classroom->id, $streamId);
        foreach ($sessions as $session) {
            $session->loadMissing('examType');
            $sittingPayload = $cache->rememberExamSessionClassSheet(
                $session,
                $classroom,
                $streamId,
                fn () => $builder->buildForExamSession($session, $classroom, $streamId)
            );
            $sittingPayload['most_improved'] = $analytics->mostImprovedForExamSession(
                $session,
                $classroom,
                $streamId,
                $sittingPayload
            );
            $bundles[] = ['classroom' => $classroom, 'payload' => $sittingPayload, 'notice' => null];
        }

        $payload = $cache->rememberTermClassSheet(
            $ay,
            $termId,
            $classroom,
            $streamId,
            fn () => $builder->buildForTerm($ay, $termId, $classroom, $streamId)
        );

        $hasScores = collect($payload['rows'] ?? [])->contains(
            fn ($row) => (int) ($row['subjects_taken'] ?? 0) > 0
        );
        $notice = $hasScores
            ? null
            : 'Exam papers exist for this class/year/term, but no marks have been entered yet.';

        $payload['most_improved'] = $analytics->mostImprovedForTermScope(
            $ay,
            $termId,
            $classroom,
            $streamId,
            null,
            $request->integer('improvement_exam_type_id') ?: null
        );

        $bundles[] = ['classroom' => $classroom, 'payload' => $payload, 'notice' => $notice];

        return $bundles;
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

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueExcelSheetTitle(string $base, array &$used): string
    {
        $title = mb_substr(trim($base), 0, 31);
        if ($title === '') {
            $title = 'Sheet';
        }

        $n = 2;
        while (isset($used[$title])) {
            $suffix = ' '.$n;
            $title = mb_substr(trim($base), 0, max(1, 31 - mb_strlen($suffix))).$suffix;
            $n++;
        }

        $used[$title] = true;

        return $title;
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
        $generatedBy = (string) ($u?->name ?? 'System');

        return Excel::download(
            new TermWorkbookExport($ay, $termId, $classroomIds, $generatedBy),
            $filename
        );
    }

    public function teacherPerformance(Request $request, AnalyticsService $analytics, SchoolWideTeacherRankingService $schoolRankings)
    {
        $request->validate([
            'view' => 'nullable|in:class,school,teacher',
            'scope' => 'nullable|in:class,school', // legacy alias
            'analysis_flow' => 'nullable|in:by_exam_type,term',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'exam_type_id' => 'nullable|exists:exam_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'teacher_id' => 'nullable|exists:staff,id',
            'exam_id' => 'nullable|exists:exams,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($u);
        $subjectScopedTeacher = ExamReportsAccess::userIsSubjectScoped($u);

        $reportView = $this->resolveTeacherReportView($request, $examReportsFullAccess);
        $analysisFlow = $request->input('analysis_flow', 'by_exam_type');
        $filterData = $this->analysisFilterViewData($u, $request);
        $teachersForFilter = $this->teachersForTeacherPerformanceFilter($request, $schoolRankings, $analysisFlow);
        $payload = null;
        $notice = null;

        if ($request->filled('load')) {
            [$payload, $notice] = $this->buildTeacherPerformanceByView(
                $request,
                $u,
                $analytics,
                $schoolRankings,
                $reportView,
                $analysisFlow
            );
        }

        $showTeacherViews = true;
        $scope = $reportView === 'school' ? 'school' : 'class';

        return view('academics.exam_reports.teacher_performance', array_merge($filterData, compact(
            'scope',
            'reportView',
            'analysisFlow',
            'payload',
            'notice',
            'examReportsFullAccess',
            'subjectScopedTeacher',
            'showTeacherViews',
            'teachersForFilter'
        )));
    }

    public function exportTeacherPerformance(Request $request, AnalyticsService $analytics, SchoolWideTeacherRankingService $schoolRankings)
    {
        $request->merge(['load' => 1]);
        $request->validate([
            'view' => 'nullable|in:class,school,teacher',
            'scope' => 'nullable|in:class,school',
            'analysis_flow' => 'nullable|in:by_exam_type,term',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'exam_type_id' => 'nullable|exists:exam_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'teacher_id' => 'nullable|exists:staff,id',
            'exam_id' => 'nullable|exists:exams,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($u);
        $reportView = $this->resolveTeacherReportView($request, $examReportsFullAccess);
        $analysisFlow = $request->input('analysis_flow', 'by_exam_type');

        [$payload, $notice] = $this->buildTeacherPerformanceByView(
            $request,
            $u,
            $analytics,
            $schoolRankings,
            $reportView,
            $analysisFlow
        );

        $hasRows = $payload && (
            ! empty($payload['per_teacher'])
            || ! empty($payload['per_subject'])
            || ! empty($payload['per_subject_teacher'])
            || ! empty($payload['rows'])
            || ! empty($payload['by_category'])
        );
        if (! $hasRows) {
            abort(422, $notice ?: 'Nothing to export. Generate teacher performance first.');
        }

        $title = match ($reportView) {
            'school' => 'All Teachers',
            'teacher' => 'Individual Teacher',
            default => 'Class Teachers',
        };
        $filename = 'teacher-performance-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new TeacherPerformanceExport($payload, $title, (string) ($u?->name ?? 'System')),
            $filename
        );
    }

    private function resolveTeacherReportView(Request $request, bool $examReportsFullAccess): string
    {
        $view = $request->input('view');
        if (! $view) {
            $view = $request->input('scope', 'class') === 'school' ? 'school' : 'class';
        }
        if (! $examReportsFullAccess && $view === 'school') {
            return 'class';
        }

        return in_array($view, ['class', 'school', 'teacher'], true) ? $view : 'class';
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function buildTeacherPerformanceByView(
        Request $request,
        ?User $user,
        AnalyticsService $analytics,
        SchoolWideTeacherRankingService $schoolRankings,
        string $reportView,
        string $analysisFlow
    ): array {
        if ($reportView === 'school') {
            ExamReportsAccess::assertSchoolWideReportsAllowed($user);

            return $this->buildSchoolTeacherPerformancePayload($request, $schoolRankings, $analysisFlow);
        }

        if ($reportView === 'teacher') {
            return $this->buildIndividualTeacherPerformancePayload($request, $schoolRankings, $analysisFlow);
        }

        return $this->buildTeacherPerformancePayload($request, $user, $analytics, $analysisFlow);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string}>
     */
    private function teachersForTeacherPerformanceFilter(
        Request $request,
        SchoolWideTeacherRankingService $schoolRankings,
        string $analysisFlow
    ) {
        if (! $request->filled('academic_year_id') || ! $request->filled('term_id')) {
            return collect();
        }

        $examTypeId = ($analysisFlow === 'by_exam_type' && $request->filled('exam_type_id'))
            ? (int) $request->exam_type_id
            : null;

        try {
            return $schoolRankings->teachersForScope(
                (int) $request->academic_year_id,
                (int) $request->term_id,
                $examTypeId
            );
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function buildSchoolTeacherPerformancePayload(
        Request $request,
        SchoolWideTeacherRankingService $schoolRankings,
        string $analysisFlow
    ): array {
        $subjectId = $request->integer('subject_id') ?: null;

        if ($request->filled('exam_id')) {
            $exam = Exam::find($request->exam_id);
            if (! $exam) {
                return [null, 'Exam not found.'];
            }
            $payload = $schoolRankings->rankingsForExam($exam, $subjectId);
            if (empty($payload['per_teacher']) && empty($payload['per_subject_teacher'])) {
                return [$payload, 'No teacher marks found for this exam.'];
            }

            return [$payload, null];
        }

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $ay = (int) $request->academic_year_id;
        $termId = (int) $request->term_id;

        if ($analysisFlow === 'term') {
            $payload = $schoolRankings->rankingsForTermWithImprovement($ay, $termId, $subjectId);
        } else {
            $request->validate(['exam_type_id' => 'required|exists:exam_types,id']);
            $payload = $schoolRankings->rankingsForExamTypeWithImprovement(
                (int) $request->exam_type_id,
                $ay,
                $termId,
                $subjectId
            );
        }

        if (empty($payload['per_teacher']) && empty($payload['per_subject_teacher']) && empty($payload['by_category'])) {
            return [$payload, 'No school-wide teacher marks found for these filters.'];
        }

        return [$payload, null];
    }

    /**
     * @return array{0: ?array, 1: ?string}
     */
    private function buildIndividualTeacherPerformancePayload(
        Request $request,
        SchoolWideTeacherRankingService $schoolRankings,
        string $analysisFlow
    ): array {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'teacher_id' => 'required|exists:staff,id',
        ]);

        $ay = (int) $request->academic_year_id;
        $termId = (int) $request->term_id;
        $teacherId = (int) $request->teacher_id;
        $examTypeId = null;

        if ($analysisFlow !== 'term') {
            $request->validate(['exam_type_id' => 'required|exists:exam_types,id']);
            $examTypeId = (int) $request->exam_type_id;
        }

        $payload = $schoolRankings->detailForTeacher($teacherId, $ay, $termId, $examTypeId);
        if (empty($payload['rows']) && empty($payload['per_subject_teacher'])) {
            return [$payload, 'No performance data found for this teacher with the selected filters.'];
        }

        return [$payload, null];
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
        $filterData = $this->analysisFilterViewData($u, $request);

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
        $exams = Exam::with(['academicYear', 'term', 'examType'])
            ->orderByDesc('created_at')
            ->limit(80)
            ->get()
            ->reject(fn (Exam $exam) => ExamScopeResolver::isSideExamType(
                $exam->examType?->name,
                $exam->examType?->code
            ) || ExamScopeResolver::isSideExamType($exam->name, null))
            ->take(60)
            ->values();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $streams = Stream::orderBy('name')->get();
        $academic = AcademicContext::forFilterRequest(
            hasYearParam: $request->has('academic_year_id'),
            yearValue: $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            hasTermParam: $request->has('term_id'),
            termValue: $request->filled('term_id') ? (int) $request->term_id : null,
        );

        $payload = null;
        if ($request->filled('classroom_id')) {
            ExamReportsAccess::assertClassroomAccess($u, (int) $request->classroom_id);
            $classroom = Classroom::find($request->classroom_id);
            if ($classroom) {
                $cache = new ReportCache();
                $streamId = $request->integer('stream_id') ?: null;
                $improvementExamTypeId = $request->integer('improvement_exam_type_id') ?: null;

                if ($mode === 'term' && $request->filled('academic_year_id') && $request->filled('term_id')) {
                    $ay = (int) $request->academic_year_id;
                    $termId = (int) $request->term_id;
                    // Top students are cacheable; improvement scale depends on selected sitting.
                    $payload = $cache->rememberTermStudentInsights($ay, $termId, $classroom, $streamId, fn () => $analytics->studentInsightsForTerm($ay, $termId, $classroom, $streamId));
                    $payload['most_improved'] = $analytics->mostImprovedForTermScope(
                        $ay,
                        $termId,
                        $classroom,
                        $streamId,
                        null,
                        $improvementExamTypeId
                    );
                }

                if ($mode === 'exam' && $request->filled('exam_id')) {
                    $exam = Exam::with('examType')->find($request->exam_id);
                    if ($exam && ! ExamScopeResolver::isSideExamType($exam->examType?->name, $exam->examType?->code)
                        && ! ExamScopeResolver::isSideExamType($exam->name, null)) {
                        $payload = $cache->rememberExamStudentInsights($exam, $classroom, $streamId, fn () => $analytics->studentInsightsForExam($exam, $classroom, $streamId));
                    }
                }
            }
        }

        return view('academics.exam_reports.student_insights', array_merge(compact('mode', 'exams', 'classrooms', 'streams', 'payload'), $academic, [
            'academicYears' => $academic['years'],
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisFilterViewData(?User $user, ?Request $request = null): array
    {
        $classrooms = ExamReportsAccess::classroomsQueryFor($user)->get();
        $allowedClassIds = $classrooms->pluck('id')->all();
        $filterScopes = $this->buildFilterScopes($allowedClassIds);

        $academic = AcademicContext::forFilterRequest(
            hasYearParam: $request ? $request->has('academic_year_id') : false,
            yearValue: $request && $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            hasTermParam: $request ? $request->has('term_id') : false,
            termValue: $request && $request->filled('term_id') ? (int) $request->term_id : null,
        );

        $examTypes = $this->analysisExamTypes();
        $coreExamTypeIds = $examTypes->pluck('id')->all();
        $classroomExamTypeIds = $filterScopes['classroomExamTypeIds'];
        if ($coreExamTypeIds !== []) {
            $allowed = array_flip($coreExamTypeIds);
            $classroomExamTypeIds = collect($classroomExamTypeIds)
                ->map(fn ($ids) => array_values(array_filter($ids, fn ($id) => isset($allowed[(int) $id]))))
                ->all();
        }

        return array_merge([
            'examTypes' => $examTypes,
            'classrooms' => $classrooms,
            'classroomExamTypeIds' => $classroomExamTypeIds,
            'termYearClassScopes' => $filterScopes['termYearClassScopes'],
            'streamsByClassroom' => $this->streamsByClassroomForFilters($allowedClassIds),
        ], $academic, [
            'academicYears' => $academic['years'],
        ]);
    }

    /**
     * Exam types available in exam-report analysis (excludes KNEC CBA / side sittings).
     *
     * @return Collection<int, ExamType>
     */
    private function analysisExamTypes(): Collection
    {
        return ExamType::orderBy('name')->get()
            ->reject(fn (ExamType $type) => ExamScopeResolver::isSideExamType($type->name, $type->code))
            ->values();
    }

    /**
     * Streams available per class for filters — classes with one or more streams.
     *
     * @param  int[]  $allowedClassIds
     * @return array<int|string, list<array{id: int, name: string}>>
     */
    private function streamsByClassroomForFilters(array $allowedClassIds): array
    {
        if ($allowedClassIds === []) {
            return [];
        }

        $out = [];

        $primaryStreams = Stream::query()
            ->whereIn('classroom_id', $allowedClassIds)
            ->orderBy('name')
            ->get(['id', 'name', 'classroom_id']);

        foreach ($primaryStreams->groupBy('classroom_id') as $classroomId => $classStreams) {
            $out[(int) $classroomId] = $classStreams
                ->map(fn (Stream $s) => ['id' => (int) $s->id, 'name' => $s->name])
                ->values()
                ->all();
        }

        $pivotStreams = Stream::query()
            ->whereHas('classrooms', fn ($q) => $q->whereIn('classrooms.id', $allowedClassIds))
            ->with(['classrooms' => fn ($q) => $q->whereIn('classrooms.id', $allowedClassIds)->select('classrooms.id')])
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($pivotStreams as $stream) {
            foreach ($stream->classrooms as $classroom) {
                $cid = (int) $classroom->id;
                $entry = ['id' => (int) $stream->id, 'name' => $stream->name];
                $existing = collect($out[$cid] ?? []);
                if (! $existing->contains(fn ($s) => $s['id'] === $entry['id'])) {
                    $out[$cid] = $existing->push($entry)->sortBy('name')->values()->all();
                }
            }
        }

        return array_filter($out, fn (array $streams) => $streams !== []);
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
            $payload['most_improved'] = $analytics->mostImprovedForTermScope(
                $ay,
                $termId,
                $classroom,
                $streamId,
                null,
                $request->integer('improvement_exam_type_id') ?: null
            );
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

        $session->loadMissing('examType');
        if (ExamScopeResolver::isSideExamType($session->examType?->name, $session->examType?->code)) {
            return [null, 'KNEC CBA is excluded from exam report analysis. Choose Mid Term, End Term, or another core exam type.'];
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
            $payload['most_improved'] = $analytics->mostImprovedForTermScope(
                $ay,
                $termId,
                $classroom,
                $streamId,
                null,
                $request->integer('improvement_exam_type_id') ?: null
            );
            $payload = $analytics->attachPriorTermTeacherImprovement(
                $payload,
                $classroom,
                $streamId,
                $ay,
                $termId,
                null,
                $subjectLimit
            );
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

        $session->loadMissing('examType');
        if (ExamScopeResolver::isSideExamType($session->examType?->name, $session->examType?->code)) {
            return [null, 'KNEC CBA is excluded from exam report analysis. Choose Mid Term, End Term, or another core exam type.'];
        }

        $payload = $cache->rememberExamSessionTeacherPerformance($session, $classroom, $streamId, fn () => $analytics->teacherPerformanceForExamSession($session, $classroom, $streamId, $subjectLimit));
        $payload = $analytics->attachPriorTermTeacherImprovement(
            $payload,
            $classroom,
            $streamId,
            (int) $request->academic_year_id,
            (int) $request->term_id,
            (int) $request->exam_type_id,
            $subjectLimit
        );
        if (empty($payload['per_teacher']) && empty($payload['per_subject'])) {
            return [$payload, 'No teacher performance data for this exam sitting. Check subject assignments and marks.'];
        }

        return [$payload, null];
    }
}

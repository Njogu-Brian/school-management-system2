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
use App\Services\Academics\ExamReports\ReportCache;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ExamReportsController extends Controller
{
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

        $classroomId = $request->integer('classroom_id') ?: null;
        if (! $classroomId && $request->filled('classroom_ids')) {
            $ids = array_filter(array_map('intval', (array) $request->input('classroom_ids', [])));
            $classroomId = $ids[0] ?? null;
        }
        $streamsForClass = $classroomId
            ? Stream::query()->where('classroom_id', $classroomId)->orderBy('name')->get()
            : collect();

        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get();
        $yearId = $request->integer('academic_year_id') ?: null;
        $termsForYear = $yearId
            ? $terms->where('academic_year_id', $yearId)->values()
            : collect();

        return view('academics.exam_reports.class_sheet', compact(
            'sheetFlow',
            'bundles',
            'examTypes',
            'subjects',
            'classrooms',
            'streamsForClass',
            'academicYears',
            'terms',
            'termsForYear',
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

        if (count($sheets) === 1) {
            return Excel::download(new ClassSheetExport($sheets[0]['payload'], $sheets[0]['title']), $filename);
        }

        return Excel::download(new ClassSheetsWorkbookExport($sheets), $filename);
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

            $session = ExamSession::query()
                ->forScope(
                    (int) $request->exam_type_id,
                    (int) $request->academic_year_id,
                    (int) $request->term_id,
                    $classroom->id,
                    $streamId
                )->first();

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
            'exam_id' => 'nullable|exists:exams,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($u);

        $scope = $request->input('scope', 'class');
        if (! $examReportsFullAccess) {
            $scope = 'class';
        }

        $exams = Exam::with(['academicYear', 'term'])->orderByDesc('created_at')->limit(60)->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $streams = Stream::orderBy('name')->get();

        $payload = null;
        if ($request->filled('exam_id')) {
            $exam = Exam::find($request->exam_id);
            if ($exam) {
                $cache = new ReportCache();
                $subjectId = $request->integer('subject_id') ?: null;

                if ($scope === 'school') {
                    ExamReportsAccess::assertSchoolWideReportsAllowed($u);
                    $payload = $cache->rememberSchoolTeacherPerformance($exam, $subjectId, fn () => $schoolRankings->rankingsForExam($exam, $subjectId));
                } else {
                    if ($request->filled('classroom_id')) {
                        ExamReportsAccess::assertClassroomAccess($u, (int) $request->classroom_id);
                        $classroom = Classroom::find($request->classroom_id);
                        if ($classroom) {
                            $streamId = $request->integer('stream_id') ?: null;
                            $payload = $cache->rememberExamTeacherPerformance(
                                exam: $exam,
                                classroom: $classroom,
                                streamId: $streamId,
                                subjectId: $subjectId,
                                build: fn () => $analytics->teacherPerformanceForExam($exam, $classroom, $streamId, $subjectId)
                            );
                        }
                    }
                }
            }
        }

        return view('academics.exam_reports.teacher_performance', compact('scope', 'exams', 'classrooms', 'streams', 'payload', 'examReportsFullAccess'));
    }

    public function subjectPerformance(Request $request, AnalyticsService $analytics)
    {
        $request->validate([
            'exam_id' => 'nullable|exists:exams,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $exams = Exam::with(['academicYear', 'term'])->orderByDesc('created_at')->limit(60)->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($u)->get();
        $streams = Stream::orderBy('name')->get();

        $payload = null;
        if ($request->filled('exam_id') && $request->filled('classroom_id')) {
            ExamReportsAccess::assertClassroomAccess($u, (int) $request->classroom_id);
            $exam = Exam::find($request->exam_id);
            $classroom = Classroom::find($request->classroom_id);
            if ($exam && $classroom) {
                $cache = new ReportCache();
                $streamId = $request->integer('stream_id') ?: null;
                $payload = $cache->rememberExamSubjectPerformance($exam, $classroom, $streamId, fn () => $analytics->subjectPerformanceForExam($exam, $classroom, $streamId));
            }
        }

        return view('academics.exam_reports.subject_performance', compact('exams', 'classrooms', 'streams', 'payload'));
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
}

<?php

namespace App\Http\Controllers\Academics;

use App\Exports\ClassSheetExport;
use App\Exports\TermWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use App\Models\Academics\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\ExamReports\AnalyticsService;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use App\Services\Academics\ExamReports\ExamReportsAccess;
use App\Services\Academics\ExamReports\ReportCache;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExamReportsController extends Controller
{
    public function classSheet(Request $request)
    {
        $request->validate([
            'mode' => 'nullable|in:exam,exam_session,term',
            'exam_id' => 'nullable|required_if:mode,exam|exists:exams,id',
            'exam_session_id' => 'nullable|required_if:mode,exam_session|exists:exam_sessions,id',
            'academic_year_id' => 'nullable|required_if:mode,term|integer',
            'term_id' => 'nullable|required_if:mode,term|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'session_filter_exam_type_id' => 'nullable|exists:exam_types,id',
            'session_filter_year_id' => 'nullable|exists:academic_years,id',
            'session_filter_term_id' => 'nullable|exists:terms,id',
        ]);

        $user = $request->user();
        $examReportsFullAccess = ExamReportsAccess::userHasFullAccess($user instanceof User ? $user : null);

        $mode = $request->input('mode', 'exam');
        $examTypes = ExamType::orderBy('name')->get();
        $exams = Exam::with(['academicYear', 'term'])
            ->whereNotNull('subject_id')
            ->orderByDesc('created_at')
            ->limit(60)
            ->get();
        $examSessions = ExamSession::query()
            ->with(['examType', 'academicYear', 'term', 'classroom'])
            ->when($request->filled('session_filter_exam_type_id'), fn ($q) => $q->where('exam_type_id', $request->session_filter_exam_type_id))
            ->when($request->filled('session_filter_year_id'), fn ($q) => $q->where('academic_year_id', $request->session_filter_year_id))
            ->when($request->filled('session_filter_term_id'), fn ($q) => $q->where('term_id', $request->session_filter_term_id))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->classroom_id))
            ->orderByDesc('id')
            ->limit(120)
            ->get();
        $classrooms = ExamReportsAccess::classroomsQueryFor($user instanceof User ? $user : null)->get();
        $streams = Stream::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get();

        $payload = null;
        $selectedExam = null;
        $selectedExamSession = null;
        $selectedClassroom = null;

        if ($request->filled('classroom_id')) {
            ExamReportsAccess::assertClassroomAccess($user instanceof User ? $user : null, (int) $request->classroom_id);
            $selectedClassroom = Classroom::find($request->classroom_id);
        }

        if ($mode === 'exam_session' && $request->filled('exam_session_id') && $selectedClassroom) {
            $selectedExamSession = ExamSession::find($request->exam_session_id);
            if ($selectedExamSession && (int) $selectedExamSession->classroom_id === (int) $selectedClassroom->id) {
                $cache = new ReportCache();
                $builder = new ClassSheetBuilder();
                $streamId = $request->integer('stream_id') ?: null;
                $payload = $cache->rememberExamSessionClassSheet(
                    session: $selectedExamSession,
                    classroom: $selectedClassroom,
                    streamId: $streamId,
                    build: fn () => $builder->buildForExamSession($selectedExamSession, $selectedClassroom, $streamId)
                );
            }
        }

        if ($mode === 'exam' && $request->filled('exam_id') && $selectedClassroom) {
            $selectedExam = Exam::find($request->exam_id);
            if ($selectedExam) {
                $cache = new ReportCache();
                $builder = new ClassSheetBuilder();
                $streamId = $request->integer('stream_id') ?: null;
                $payload = $cache->rememberExamClassSheet(
                    exam: $selectedExam,
                    classroom: $selectedClassroom,
                    streamId: $streamId,
                    build: fn () => $builder->buildForExam($selectedExam, $selectedClassroom, $streamId)
                );
            }
        }

        if ($mode === 'term' && $selectedClassroom && $request->filled('term_id') && $request->filled('academic_year_id')) {
            $cache = new ReportCache();
            $builder = new ClassSheetBuilder();
            $streamId = $request->integer('stream_id') ?: null;
            $ay = (int) $request->academic_year_id;
            $termId = (int) $request->term_id;
            $payload = $cache->rememberTermClassSheet(
                academicYearId: $ay,
                termId: $termId,
                classroom: $selectedClassroom,
                streamId: $streamId,
                build: fn () => $builder->buildForTerm($ay, $termId, $selectedClassroom, $streamId)
            );
        }

        return view('academics.exam_reports.class_sheet', compact(
            'mode',
            'examTypes',
            'examSessions',
            'exams',
            'classrooms',
            'streams',
            'academicYears',
            'terms',
            'selectedExam',
            'selectedExamSession',
            'selectedClassroom',
            'payload',
            'examReportsFullAccess'
        ));
    }

    public function exportClassSheet(Request $request)
    {
        $request->validate([
            'mode' => 'nullable|in:exam,exam_session,term',
            'exam_id' => 'nullable|required_if:mode,exam|exists:exams,id',
            'exam_session_id' => 'nullable|required_if:mode,exam_session|exists:exam_sessions,id',
            'academic_year_id' => 'nullable|required_if:mode,term|integer',
            'term_id' => 'nullable|required_if:mode,term|exists:terms,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        ExamReportsAccess::assertClassroomAccess($user instanceof User ? $user : null, (int) $request->classroom_id);

        $mode = $request->input('mode', 'exam');
        $classroom = Classroom::findOrFail($request->classroom_id);
        $streamId = $request->integer('stream_id') ?: null;

        $builder = new ClassSheetBuilder();
        if ($mode === 'term') {
            $cache = new ReportCache();
            $ay = (int) $request->academic_year_id;
            $termId = (int) $request->term_id;
            $payload = $cache->rememberTermClassSheet($ay, $termId, $classroom, $streamId, fn () => $builder->buildForTerm($ay, $termId, $classroom, $streamId));
            $title = ($classroom->name ?? 'Class') . ' Term Sheet';
            $filename = 'term-sheet-' . $classroom->id . '.xlsx';
        } elseif ($mode === 'exam_session') {
            $session = ExamSession::findOrFail((int) $request->exam_session_id);
            if ((int) $session->classroom_id !== (int) $classroom->id) {
                abort(422, 'The selected class does not match this exam sitting.');
            }
            $cache = new ReportCache();
            $payload = $cache->rememberExamSessionClassSheet($session, $classroom, $streamId, fn () => $builder->buildForExamSession($session, $classroom, $streamId));
            $title = ($classroom->name ?? 'Class').' '.$session->name;
            $filename = 'class-sheet-session-'.$session->id.'-'.$classroom->id.'.xlsx';
        } else {
            $exam = Exam::findOrFail($request->exam_id);
            $cache = new ReportCache();
            $payload = $cache->rememberExamClassSheet($exam, $classroom, $streamId, fn () => $builder->buildForExam($exam, $classroom, $streamId));
            $title = ($classroom->name ?? 'Class') . ' ' . ($exam->name ?? 'Exam');
            $filename = 'class-sheet-' . $exam->id . '-' . $classroom->id . '.xlsx';
        }

        return Excel::download(new ClassSheetExport($payload, $title), $filename);
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

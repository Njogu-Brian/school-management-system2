<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClassSheetExport;
use App\Exports\TermWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\User;
use App\Services\Academics\ExamReports\AnalyticsService;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use App\Services\Academics\ExamReports\ExamReportsAccess;
use App\Services\Academics\ExamReports\ReportCache;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use App\Services\Academics\ExamReports\TrendsService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ApiExamReportsController extends Controller
{
    public function classSheet(Request $request)
    {
        $data = $request->validate([
            'mode' => 'nullable|in:exam,term',
            'exam_id' => 'nullable|required_if:mode,exam|exists:exams,id',
            'academic_year_id' => 'nullable|required_if:mode,term|integer',
            'term_id' => 'nullable|required_if:mode,term|exists:terms,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        ExamReportsAccess::assertClassroomAccess($u, (int) $data['classroom_id']);

        $mode = $data['mode'] ?? 'exam';
        $classroom = Classroom::findOrFail($data['classroom_id']);
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;

        $builder = new ClassSheetBuilder();
        if ($mode === 'term') {
            $cache = new ReportCache();
            $ay = (int) $data['academic_year_id'];
            $termId = (int) $data['term_id'];
            $payload = $cache->rememberTermClassSheet($ay, $termId, $classroom, $streamId, fn () => $builder->buildForTerm($ay, $termId, $classroom, $streamId));
        } else {
            $exam = Exam::findOrFail($data['exam_id']);
            $cache = new ReportCache();
            $payload = $cache->rememberExamClassSheet($exam, $classroom, $streamId, fn () => $builder->buildForExam($exam, $classroom, $streamId));
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function teacherPerformance(Request $request, AnalyticsService $analytics, SchoolWideTeacherRankingService $schoolRankings)
    {
        $data = $request->validate([
            'scope' => 'nullable|in:class,school',
            'exam_id' => 'required|exists:exams,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $exam = Exam::findOrFail($data['exam_id']);
        $cache = new ReportCache();
        $scope = $data['scope'] ?? 'class';
        if (! ExamReportsAccess::userHasFullAccess($u)) {
            $scope = 'class';
        }
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;
        $subjectId = isset($data['subject_id']) ? (int) $data['subject_id'] : null;

        if ($scope === 'school') {
            ExamReportsAccess::assertSchoolWideReportsAllowed($u);
            $payload = $cache->rememberSchoolTeacherPerformance($exam, $subjectId, fn () => $schoolRankings->rankingsForExam($exam, $subjectId));
        } else {
            if (empty($data['classroom_id'])) {
                return response()->json(['success' => false, 'message' => 'classroom_id is required for class scope.'], 422);
            }
            $classroom = Classroom::findOrFail($data['classroom_id']);
            ExamReportsAccess::assertClassroomAccess($u, (int) $classroom->id);
            $payload = $cache->rememberExamTeacherPerformance($exam, $classroom, $streamId, $subjectId, fn () => $analytics->teacherPerformanceForExam($exam, $classroom, $streamId, $subjectId));
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function subjectPerformance(Request $request, AnalyticsService $analytics)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        ExamReportsAccess::assertClassroomAccess($u, (int) $data['classroom_id']);

        $exam = Exam::findOrFail($data['exam_id']);
        $classroom = Classroom::findOrFail($data['classroom_id']);

        $cache = new ReportCache();
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;
        $payload = $cache->rememberExamSubjectPerformance($exam, $classroom, $streamId, fn () => $analytics->subjectPerformanceForExam($exam, $classroom, $streamId));

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function studentInsights(Request $request, AnalyticsService $analytics)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        ExamReportsAccess::assertClassroomAccess($u, (int) $data['classroom_id']);

        $exam = Exam::findOrFail($data['exam_id']);
        $classroom = Classroom::findOrFail($data['classroom_id']);

        $cache = new ReportCache();
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;
        $payload = $cache->rememberExamStudentInsights($exam, $classroom, $streamId, fn () => $analytics->studentInsightsForExam($exam, $classroom, $streamId));

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function exportClassSheet(Request $request)
    {
        $data = $request->validate([
            'mode' => 'nullable|in:exam,term',
            'exam_id' => 'nullable|required_if:mode,exam|exists:exams,id',
            'academic_year_id' => 'nullable|required_if:mode,term|integer',
            'term_id' => 'nullable|required_if:mode,term|exists:terms,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        ExamReportsAccess::assertClassroomAccess($u, (int) $data['classroom_id']);

        $mode = $data['mode'] ?? 'exam';
        $classroom = Classroom::findOrFail($data['classroom_id']);
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;

        $builder = new ClassSheetBuilder();
        if ($mode === 'term') {
            $cache = new ReportCache();
            $ay = (int) $data['academic_year_id'];
            $termId = (int) $data['term_id'];
            $payload = $cache->rememberTermClassSheet($ay, $termId, $classroom, $streamId, fn () => $builder->buildForTerm($ay, $termId, $classroom, $streamId));
            $title = ($classroom->name ?? 'Class') . ' Term Sheet';
            $filename = 'term-sheet-' . $classroom->id . '.xlsx';
        } else {
            $exam = Exam::findOrFail($data['exam_id']);
            $cache = new ReportCache();
            $payload = $cache->rememberExamClassSheet($exam, $classroom, $streamId, fn () => $builder->buildForExam($exam, $classroom, $streamId));
            $title = ($classroom->name ?? 'Class') . ' ' . ($exam->name ?? 'Exam');
            $filename = 'class-sheet-' . $exam->id . '-' . $classroom->id . '.xlsx';
        }

        return Excel::download(new ClassSheetExport($payload, $title), $filename);
    }

    public function exportTermWorkbook(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;

        $ay = (int) $data['academic_year_id'];
        $termId = (int) $data['term_id'];
        $filename = "term-workbook-{$ay}-{$termId}.xlsx";

        $classroomIds = [];
        if (! ExamReportsAccess::userHasFullAccess($u)) {
            $classroomIds = ExamReportsAccess::allowedClassroomIdsFor($u);
            if ($classroomIds === []) {
                abort(403, 'No classes are assigned for exam reports.');
            }
        }

        return Excel::download(new TermWorkbookExport($ay, $termId, $classroomIds), $filename);
    }

    public function masteryProfile(Request $request, AnalyticsService $analytics)
    {
        $data = $request->validate([
            'mode' => 'required|in:exam,term',
            'exam_id' => 'nullable|required_if:mode,exam|exists:exams,id',
            'academic_year_id' => 'nullable|required_if:mode,term|integer',
            'term_id' => 'nullable|required_if:mode,term|exists:terms,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'top_n' => 'nullable|integer|min:1|max:10',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        ExamReportsAccess::assertClassroomAccess($u, (int) $data['classroom_id']);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        $streamId = isset($data['stream_id']) ? (int) $data['stream_id'] : null;
        $topN = isset($data['top_n']) ? (int) $data['top_n'] : 3;

        $builder = new ClassSheetBuilder();
        if ($data['mode'] === 'term') {
            $sheet = $builder->buildForTerm((int) $data['academic_year_id'], (int) $data['term_id'], $classroom, $streamId);
        } else {
            $exam = Exam::findOrFail($data['exam_id']);
            $sheet = $builder->buildForExam($exam, $classroom, $streamId);
        }

        $payload = $analytics->masteryProfile($sheet, $topN);

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function trends(Request $request, TrendsService $trends)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:staff,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $classroomId = isset($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        ExamReportsAccess::assertTrendsClassroomScope($u, $classroomId);

        $payload = $trends->examSeriesForTerm(
            academicYearId: (int) $data['academic_year_id'],
            termId: (int) $data['term_id'],
            classroomId: $classroomId,
            streamId: isset($data['stream_id']) ? (int) $data['stream_id'] : null,
            subjectId: isset($data['subject_id']) ? (int) $data['subject_id'] : null,
            teacherId: isset($data['teacher_id']) ? (int) $data['teacher_id'] : null,
        );

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function insights(Request $request, TrendsService $trends)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer',
            'term_id' => 'required|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:staff,id',
        ]);

        $user = $request->user();
        $u = $user instanceof User ? $user : null;
        $classroomId = isset($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        ExamReportsAccess::assertTrendsClassroomScope($u, $classroomId);

        $series = $trends->examSeriesForTerm(
            academicYearId: (int) $data['academic_year_id'],
            termId: (int) $data['term_id'],
            classroomId: $classroomId,
            streamId: isset($data['stream_id']) ? (int) $data['stream_id'] : null,
            subjectId: isset($data['subject_id']) ? (int) $data['subject_id'] : null,
            teacherId: isset($data['teacher_id']) ? (int) $data['teacher_id'] : null,
        );

        $payload = $trends->insightsFromSeries($series);

        return response()->json(['success' => true, 'data' => $payload]);
    }
}

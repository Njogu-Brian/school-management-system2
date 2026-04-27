<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Student;
use App\Services\ReportCardBatchService;
use Illuminate\Http\Request;

class ApiReportCardController extends Controller
{
    /**
     * Paginated report cards for one student (mobile Academics tab).
     */
    public function index(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $studentId = (int) $request->student_id;
        $student = Student::findOrFail($studentId);
        $user = $request->user();

        $this->assertUserCanAccessStudent($user, $student);

        $perPage = (int) $request->input('per_page', 15);
        $query = ReportCard::query()
            ->with(['term', 'academicYear', 'classroom'])
            ->where('student_id', $studentId)
            ->orderByDesc('id');

        if ($user && $user->hasAnyRole(['Parent', 'Guardian'])) {
            $query->whereNotNull('published_at');
        }

        $paginated = $query->paginate($perPage);

        $rows = $paginated->getCollection()->map(function (ReportCard $rc) use ($student) {
            return [
                'id' => $rc->id,
                'student_id' => $rc->student_id,
                'student_name' => $student->full_name,
                'class_id' => (int) $rc->classroom_id,
                'class_name' => $rc->classroom?->name,
                'term_id' => (int) $rc->term_id,
                'academic_year_id' => (int) $rc->academic_year_id,
                'overall_marks' => 0,
                'overall_percentage' => 0,
                'overall_grade' => null,
                'status' => $rc->published_at ? 'published' : 'draft',
                'generated_at' => $rc->published_at?->toIso8601String(),
                'created_at' => $rc->created_at?->toIso8601String() ?? '',
                'updated_at' => $rc->updated_at?->toIso8601String() ?? '',
                'subjects' => [],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Full report card payload for mobile (mapped from {@see ReportCardBatchService::build}).
     */
    public function show(Request $request, int $id)
    {
        $rc = ReportCard::with('student')->findOrFail($id);
        $user = $request->user();
        $this->assertUserCanAccessStudent($user, $rc->student);

        if ($user && $user->hasAnyRole(['Parent', 'Guardian']) && ! $rc->published_at) {
            return response()->json([
                'success' => false,
                'message' => 'This report card is not published yet.',
            ], 403);
        }

        $dto = ReportCardBatchService::build($rc->id);

        $subjects = [];
        $sid = 0;
        foreach ($dto['subjects'] ?? [] as $row) {
            $sid++;
            $avg = $row['term_avg'] ?? null;
            $pct = is_numeric($avg) ? (float) $avg : 0.0;
            $subjects[] = [
                'subject_id' => $sid,
                'subject_name' => (string) ($row['subject_name'] ?? 'Subject'),
                'marks' => is_numeric($avg) ? round((float) $avg, 2) : 0,
                'total_marks' => 100,
                'percentage' => round($pct, 2),
                'grade' => (string) ($row['grade_label'] ?? '—'),
                'remarks' => $row['teacher_remark'] ?? null,
                'position' => null,
            ];
        }

        $skills = [];
        foreach ($dto['skills'] ?? [] as $s) {
            $rating = strtolower((string) ($s['grade'] ?? 'average'));
            $normalized = match ($rating) {
                'excellent', 'good', 'average', 'needs_improvement' => $rating,
                default => 'average',
            };
            $skills[] = [
                'skill_name' => (string) ($s['skill'] ?? ''),
                'rating' => $normalized,
                'comment' => $s['comment'] ?? null,
            ];
        }

        $cbc = is_array($dto['cbc'] ?? null) ? $dto['cbc'] : [];

        $payload = [
            'id' => $rc->id,
            'student_id' => $rc->student_id,
            'student_name' => $dto['student']['name'] ?? $rc->student?->full_name,
            'class_id' => (int) $rc->classroom_id,
            'class_name' => $dto['student']['class'] ?? $rc->classroom?->name,
            'term_id' => (int) $rc->term_id,
            'academic_year_id' => (int) $rc->academic_year_id,
            'exam_id' => null,
            'overall_marks' => 0,
            'overall_percentage' => count($subjects)
                ? round(collect($subjects)->avg('percentage'), 2)
                : 0.0,
            'overall_grade' => $cbc['overall_performance_level_name']
                ?? $cbc['overall_performance_level']
                ?? null,
            'overall_position' => null,
            'class_position' => null,
            'stream_position' => null,
            'subjects' => $subjects,
            'skills' => $skills,
            'teacher_comment' => $rc->teacher_remark,
            'principal_comment' => $rc->headteacher_remark,
            'status' => $rc->published_at ? 'published' : 'draft',
            'generated_at' => $rc->published_at?->toIso8601String(),
            'created_at' => $rc->created_at?->toIso8601String() ?? '',
            'updated_at' => $rc->updated_at?->toIso8601String() ?? '',
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    protected function assertUserCanAccessStudent(?\App\Models\User $user, ?Student $student): void
    {
        if (! $user || ! $student) {
            abort(403, 'Forbidden.');
        }

        if ($user->hasTeacherLikeRole()) {
            $query = Student::where('id', $student->id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        if ($user->hasAnyRole(['Parent', 'Guardian'])) {
            if (! $user->canAccessStudent((int) $student->id)) {
                abort(403, 'You do not have access to this student.');
            }
        }
    }
}

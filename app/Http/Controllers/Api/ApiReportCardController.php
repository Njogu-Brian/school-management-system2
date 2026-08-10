<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Student;
use App\Services\ReportCardAccessService;
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

        if ($this->isParentAppViewer($user)) {
            $query->whereNotNull('published_at');
        }

        $paginated = $query->paginate($perPage);

        $rows = $paginated->getCollection()->map(function (ReportCard $rc) use ($student, $user) {
            [$canView, $feeBalance] = ReportCardAccessService::canViewPublicReportCard($rc);
            $parentLocked = $this->shouldEnforceFeeLock($user, $rc) && ! $canView;

            return [
                'id' => $rc->id,
                'student_id' => $rc->student_id,
                'student_name' => $student->full_name,
                'class_id' => (int) $rc->classroom_id,
                'class_name' => $rc->classroom?->name,
                'term_id' => (int) $rc->term_id,
                'term_name' => $rc->term?->name,
                'academic_year_id' => (int) $rc->academic_year_id,
                'academic_year_name' => $rc->academicYear?->year ?? $rc->academicYear?->name,
                'overall_marks' => 0,
                'overall_percentage' => 0,
                'overall_grade' => null,
                'status' => $rc->published_at ? 'published' : 'draft',
                'generated_at' => $rc->published_at?->toIso8601String(),
                'created_at' => $rc->created_at?->toIso8601String() ?? '',
                'updated_at' => $rc->updated_at?->toIso8601String() ?? '',
                'subjects' => [],
                'can_view_report' => $parentLocked ? false : true,
                'fee_balance' => round((float) $feeBalance, 2),
                'access_locked' => $parentLocked,
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
     * Parents with outstanding report-term fees receive a locked payload (no scores / URLs).
     */
    public function show(Request $request, int $id)
    {
        $rc = ReportCard::with(['student', 'term', 'academicYear', 'classroom'])->findOrFail($id);
        $user = $request->user();
        $this->assertUserCanAccessStudent($user, $rc->student);

        if ($this->shouldEnforceFeeLock($user, $rc) && ! $rc->published_at) {
            return response()->json([
                'success' => false,
                'message' => 'This report card is not published yet.',
            ], 403);
        }

        [$canView, $feeBalance] = ReportCardAccessService::canViewPublicReportCard($rc);
        $billing = ReportCardAccessService::billingContextForReportCard($rc);
        $parentLocked = $this->shouldEnforceFeeLock($user, $rc) && ! $canView;

        if (empty($rc->public_token)) {
            $rc->public_token = \Illuminate\Support\Str::random(40);
            $rc->save();
        }

        $viewUrl = null;
        $pdfUrl = null;
        $portalUrl = null;
        $student = $rc->student;
        if ($student && $rc->public_token && ! $parentLocked) {
            $portalLink = family_report_portal_link_for_student(
                $student,
                (int) $rc->academic_year_id,
                (int) $rc->term_id
            );
            if ($portalLink) {
                $portalUrl = $portalLink->getUrl();
                $viewUrl = route('family.reports.show', [$portalLink->token, $rc->public_token]);
                $pdfUrl = route('family.reports.pdf', [$portalLink->token, $rc->public_token]);
            }
        }

        $subjects = [];
        $skills = [];
        $overallPercentage = 0.0;
        $overallGrade = null;
        $teacherComment = null;
        $principalComment = null;
        $studentName = $rc->student?->full_name;
        $className = $rc->classroom?->name;

        if (! $parentLocked) {
            $dto = ReportCardBatchService::build($rc->id);
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
            $overallPercentage = count($subjects)
                ? round(collect($subjects)->avg('percentage'), 2)
                : 0.0;
            $overallGrade = $cbc['overall_performance_level_name']
                ?? $cbc['overall_performance_level']
                ?? null;
            $teacherComment = $rc->teacher_remark;
            $principalComment = $rc->headteacher_remark;
            $studentName = $dto['student']['name'] ?? $studentName;
            $className = $dto['student']['class'] ?? $className;
        }

        $payload = [
            'id' => $rc->id,
            'student_id' => $rc->student_id,
            'student_name' => $studentName,
            'class_id' => (int) $rc->classroom_id,
            'class_name' => $className,
            'term_id' => (int) $rc->term_id,
            'term_name' => $rc->term?->name,
            'academic_year_id' => (int) $rc->academic_year_id,
            'academic_year_name' => $rc->academicYear?->year ?? $rc->academicYear?->name,
            'exam_id' => null,
            'overall_marks' => 0,
            'overall_percentage' => $overallPercentage,
            'overall_grade' => $overallGrade,
            'overall_position' => null,
            'class_position' => null,
            'stream_position' => null,
            'subjects' => $subjects,
            'skills' => $skills,
            'teacher_comment' => $teacherComment,
            'principal_comment' => $principalComment,
            'status' => $rc->published_at ? 'published' : 'draft',
            'generated_at' => $rc->published_at?->toIso8601String(),
            'created_at' => $rc->created_at?->toIso8601String() ?? '',
            'updated_at' => $rc->updated_at?->toIso8601String() ?? '',
            'public_token' => $parentLocked ? null : $rc->public_token,
            'view_url' => $viewUrl,
            'pdf_url' => $pdfUrl,
            'portal_url' => $portalUrl,
            'can_view_report' => ! $parentLocked,
            'access_locked' => $parentLocked,
            'fee_balance' => round((float) $feeBalance, 2),
            'fee_lock_message' => $parentLocked
                ? 'Clear outstanding school fees for this report term before you can view or download the report form.'
                : null,
            'display_term_label' => $billing['display_term_label'] ?? null,
            'invoice_total_balance' => round((float) ($billing['invoice_total_balance'] ?? $feeBalance), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * Parent / Guardian role, or any account linked to parent_info (Users Home mode).
     */
    protected function isParentAppViewer(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Parent', 'Guardian']) || (bool) $user->parent_id;
    }

    /**
     * Fee gate applies when the viewer is acting as the child's parent/guardian.
     */
    protected function shouldEnforceFeeLock(?\App\Models\User $user, ReportCard $rc): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['Parent', 'Guardian'])) {
            return true;
        }

        return (bool) $user->parent_id && $user->canAccessStudent((int) $rc->student_id);
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

            return;
        }

        if ($this->isParentAppViewer($user)) {
            if (! $user->canAccessStudent((int) $student->id)) {
                abort(403, 'You do not have access to this student.');
            }
        }
    }
}

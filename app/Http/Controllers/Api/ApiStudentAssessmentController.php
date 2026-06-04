<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Term;
use App\Services\Academics\AssessmentReadFacade;
use Illuminate\Http\Request;

class ApiStudentAssessmentController extends Controller
{
    public function __construct(
        protected AssessmentReadFacade $facade,
    ) {
    }

    /**
     * Unified assessment history for one student (Phase 0 read facade).
     */
    public function assessmentHistory(Request $request, Student $student)
    {
        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'academic_year_id' => ['sometimes', 'integer'],
            'term_id' => ['sometimes', 'integer'],
            'subject_id' => ['sometimes', 'integer'],
            'type' => ['sometimes'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        $this->assertUserCanAccessStudent($user, $student);

        $guardianPublishedOnly = $user && $user->hasAnyRole(['Parent', 'Guardian']);

        $paginator = $this->facade->history(
            $student,
            $request->only([
                'page',
                'per_page',
                'academic_year_id',
                'term_id',
                'subject_id',
                'type',
                'from',
                'to',
            ]),
            $guardianPublishedOnly,
        );

        $rows = collect($paginator->items())
            ->map(fn ($item) => $item->toArray())
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'meta' => [
                'student_id' => $student->id,
                'current_term_id' => Term::query()->where('is_current', true)->value('id'),
            ],
        ]);
    }

    /**
     * Academic KPI summary for one student (Phase 0 read facade).
     */
    public function academicSummary(Request $request, Student $student)
    {
        $request->validate([
            'academic_year_id' => ['sometimes', 'integer'],
            'term_id' => ['sometimes', 'integer'],
        ]);

        $user = $request->user();
        $this->assertUserCanAccessStudent($user, $student);

        $guardianPublishedOnly = $user && $user->hasAnyRole(['Parent', 'Guardian']);

        $summary = $this->facade->academicSummary(
            $student,
            $request->only(['academic_year_id', 'term_id']),
            $guardianPublishedOnly,
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
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

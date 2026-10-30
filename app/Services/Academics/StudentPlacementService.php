<?php

namespace App\Services\Academics;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Services\FamilyArchiveService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentPlacementService
{
    /**
     * Calendar year used for live membership and promotion apply timing.
     */
    public function calendarYear(?Carbon $now = null): int
    {
        return (int) ($now ?? now())->year;
    }

    /**
     * Default deferred promotion effective date: 1 January of the next calendar year.
     */
    public function defaultEffectiveDate(?Carbon $from = null): Carbon
    {
        $from = $from ?? now();

        return Carbon::create($this->calendarYear($from) + 1, 1, 1)->startOfDay();
    }

    public function isFutureYear(int $year, ?Carbon $now = null): bool
    {
        return $year > $this->calendarYear($now);
    }

    /**
     * Resolve classroom/stream for a student in a given calendar/billing year.
     *
     * @return array{classroom_id:?int,stream_id:?int,classroom_name:string,source:string,used_next_class:bool}
     */
    public function forYear(Student $student, int $year, ?Carbon $now = null): array
    {
        $current = $this->calendarYear($now);

        if ($year > $current) {
            return $this->resolveFuturePlacement($student, $year);
        }

        if ($year < $current) {
            return $this->resolvePastPlacement($student, $year);
        }

        return $this->resolveLivePlacement($student);
    }

    /**
     * @return array{classroom_id:?int,stream_id:?int,classroom_name:string,source:string,used_next_class:bool}
     */
    public function resolveLivePlacement(Student $student): array
    {
        $student->loadMissing(['classroom']);
        $current = $student->classroom;

        if (! $current) {
            return [
                'classroom_id' => null,
                'stream_id' => $student->stream_id ? (int) $student->stream_id : null,
                'classroom_name' => '',
                'source' => 'live',
                'used_next_class' => false,
            ];
        }

        return [
            'classroom_id' => (int) $current->id,
            'stream_id' => $student->stream_id ? (int) $student->stream_id : null,
            'classroom_name' => (string) $current->name,
            'source' => 'live',
            'used_next_class' => false,
        ];
    }

    /**
     * Future-year placement: pending promotion next class, else mapped next class.
     *
     * @return array{classroom_id:?int,stream_id:?int,classroom_name:string,source:string,used_next_class:bool}
     *
     * @throws \InvalidArgumentException
     */
    public function resolveFuturePlacement(Student $student, int $year): array
    {
        $name = trim((string) ($student->full_name ?? (($student->first_name ?? '').' '.($student->last_name ?? '')))) ?: ('#'.$student->id);

        if ($student->is_alumni) {
            throw new \InvalidArgumentException(
                "Cannot resolve {$year} placement for {$name}: student is already alumni."
            );
        }

        $pending = StudentAcademicHistory::query()
            ->where('student_id', $student->id)
            ->whereNull('applied_at')
            ->whereIn('promotion_status', ['promoted', 'graduated'])
            ->whereNotNull('next_classroom_id')
            ->orderByDesc('id')
            ->first();

        if ($pending && $pending->next_classroom_id) {
            $next = Classroom::withoutGlobalScope('offered')->find($pending->next_classroom_id);
            if ($next) {
                return [
                    'classroom_id' => (int) $next->id,
                    'stream_id' => $pending->next_stream_id ? (int) $pending->next_stream_id : null,
                    'classroom_name' => (string) $next->name,
                    'source' => 'pending_promotion',
                    'used_next_class' => true,
                ];
            }
        }

        $student->loadMissing(['classroom.nextClass']);
        $current = $student->classroom;
        if (! $current) {
            throw new \InvalidArgumentException(
                "Cannot resolve {$year} placement for {$name}: no current class assigned."
            );
        }

        $next = $current->nextClass;
        if (! $next) {
            throw new \InvalidArgumentException(
                "Cannot resolve {$year} placement for {$name}: {$current->name} has no next class mapped (leaving / last class)."
            );
        }

        return [
            'classroom_id' => (int) $next->id,
            'stream_id' => $this->matchingStreamId($student->stream_id, (int) $next->id),
            'classroom_name' => (string) $next->name,
            'source' => 'next_class_map',
            'used_next_class' => true,
        ];
    }

    /**
     * Past-year placement from academic history (from-class for that year).
     *
     * @return array{classroom_id:?int,stream_id:?int,classroom_name:string,source:string,used_next_class:bool}
     */
    public function resolvePastPlacement(Student $student, int $year): array
    {
        $history = StudentAcademicHistory::query()
            ->with('classroom')
            ->where('student_id', $student->id)
            ->whereHas('academicYear', fn ($q) => $q->where('year', $year))
            ->whereNotNull('classroom_id')
            ->orderByDesc('promotion_date')
            ->orderByDesc('id')
            ->first();

        if ($history && $history->classroom_id) {
            return [
                'classroom_id' => (int) $history->classroom_id,
                'stream_id' => $history->stream_id ? (int) $history->stream_id : null,
                'classroom_name' => (string) ($history->classroom?->name ?? ''),
                'source' => 'history',
                'used_next_class' => false,
            ];
        }

        // Promotion into a class that took effect in $year+1 means they sat $year in from-class.
        $incoming = StudentAcademicHistory::query()
            ->with('classroom')
            ->where('student_id', $student->id)
            ->whereIn('promotion_status', ['promoted', 'graduated', 'demoted'])
            ->whereNotNull('classroom_id')
            ->where(function ($q) use ($year) {
                $q->whereYear('effective_date', $year + 1)
                    ->orWhere(function ($inner) use ($year) {
                        $inner->whereNull('effective_date')
                            ->whereYear('promotion_date', $year + 1);
                    });
            })
            ->orderByDesc('id')
            ->first();

        if ($incoming && $incoming->classroom_id) {
            return [
                'classroom_id' => (int) $incoming->classroom_id,
                'stream_id' => $incoming->stream_id ? (int) $incoming->stream_id : null,
                'classroom_name' => (string) ($incoming->classroom?->name ?? ''),
                'source' => 'history_inferred',
                'used_next_class' => false,
            ];
        }

        return $this->resolveLivePlacement($student);
    }

    /**
     * Students whose placement for $year is the given classroom.
     */
    public function studentsInClassroom(int $classroomId, int $year, ?int $streamId = null): Collection
    {
        $current = $this->calendarYear();

        if ($year === $current) {
            $query = Student::query()
                ->with('stream:id,name')
                ->where('classroom_id', $classroomId)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->when($streamId, fn ($q) => $q->where('stream_id', $streamId));

            return $query->orderBy('admission_number')->get();
        }

        if ($year > $current) {
            return $this->studentsForFutureClassroom($classroomId, $year, $streamId);
        }

        return $this->studentsForPastClassroom($classroomId, $year, $streamId);
    }

    /**
     * Base query for fee-balance style listing: students matching placement for year (+ optional class).
     */
    public function applyClassroomFilter(Builder $studentsQuery, int $year, ?int $classroomId): Builder
    {
        if (! $classroomId) {
            $ids = $this->studentIdsForYear($year);
            if (empty($ids)) {
                return $studentsQuery->whereRaw('1 = 0');
            }

            return $studentsQuery->whereIn('id', $ids);
        }

        $current = $this->calendarYear();

        if ($year === $current) {
            return $studentsQuery->where('classroom_id', $classroomId);
        }

        $ids = $this->studentsInClassroom($classroomId, $year)->pluck('id')->all();
        if (empty($ids)) {
            return $studentsQuery->whereRaw('1 = 0');
        }

        return $studentsQuery->whereIn('id', $ids);
    }

    /**
     * Student IDs that belong in reports for a billing/academic year.
     * - Current year: live active roster
     * - Future year: continuing students with a resolvable next-class placement (excludes alumni / leavers)
     * - Past year: anyone with academic history for that year (includes later alumni/archived)
     *
     * @return array<int>
     */
    public function studentIdsForYear(int $year, ?Carbon $now = null): array
    {
        $current = $this->calendarYear($now);

        if ($year === $current) {
            return Student::query()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($year > $current) {
            return Student::query()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->with(['classroom.nextClass'])
                ->get()
                ->filter(function (Student $student) use ($year) {
                    try {
                        $placement = $this->forYear($student, $year);

                        return ! empty($placement['classroom_id']);
                    } catch (\InvalidArgumentException) {
                        return false;
                    }
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $historyIds = StudentAcademicHistory::query()
            ->whereHas('academicYear', fn ($q) => $q->where('year', $year))
            ->whereNotNull('classroom_id')
            ->pluck('student_id');

        $inferredIds = StudentAcademicHistory::query()
            ->whereIn('promotion_status', ['promoted', 'graduated', 'demoted'])
            ->whereNotNull('classroom_id')
            ->where(function ($q) use ($year) {
                $q->whereYear('effective_date', $year + 1)
                    ->orWhere(function ($inner) use ($year) {
                        $inner->whereNull('effective_date')
                            ->whereYear('promotion_date', $year + 1);
                    });
            })
            ->pluck('student_id');

        return $historyIds->merge($inferredIds)
            ->unique()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Whether past-year report queries should include alumni/archived students.
     */
    public function includesAlumniForYear(int $year, ?Carbon $now = null): bool
    {
        return $year < $this->calendarYear($now);
    }

    public function matchingStreamId($oldStreamId, int $newClassroomId): ?int
    {
        if (! $oldStreamId) {
            return null;
        }

        $oldStream = Stream::find($oldStreamId);
        if (! $oldStream) {
            return null;
        }

        $match = Stream::where('classroom_id', $newClassroomId)
            ->where('name', $oldStream->name)
            ->first();

        return $match?->id ? (int) $match->id : null;
    }

    /**
     * Apply a pending history row to the live student record.
     */
    public function applyHistory(StudentAcademicHistory $history, ?Carbon $appliedAt = null): void
    {
        $appliedAt = $appliedAt ?? now();
        /** @var Student|null $student */
        $student = Student::withAlumni()->whereKey($history->student_id)->first();
        if (! $student) {
            return;
        }

        if ($history->promotion_status === 'graduated') {
            $student->update([
                'is_alumni' => true,
                'alumni_date' => $history->promotion_date ?? $appliedAt,
                'classroom_id' => null,
                'stream_id' => null,
            ]);
            app(FamilyArchiveService::class)->onStudentArchivedOrAlumni($student->fresh());
        } elseif ($history->next_classroom_id) {
            $student->update([
                'is_alumni' => false,
                'alumni_date' => null,
                'classroom_id' => $history->next_classroom_id,
                'stream_id' => $history->next_stream_id,
            ]);
        }

        $history->update([
            'applied_at' => $appliedAt,
            'effective_date' => $history->effective_date ?? $appliedAt->toDateString(),
        ]);
    }

    /**
     * Whether an effective date is already due (inclusive of today).
     */
    public function isEffectiveDue(?Carbon $effectiveDate, ?Carbon $now = null): bool
    {
        if (! $effectiveDate) {
            return true;
        }

        $now = $now ?? now();

        return $effectiveDate->toDateString() <= $now->toDateString();
    }

    private function studentsForFutureClassroom(int $classroomId, int $year, ?int $streamId): Collection
    {
        $pendingIds = StudentAcademicHistory::query()
            ->whereNull('applied_at')
            ->whereIn('promotion_status', ['promoted', 'graduated'])
            ->where('next_classroom_id', $classroomId)
            ->when($streamId, fn ($q) => $q->where('next_stream_id', $streamId))
            ->pluck('student_id');

        $sourceClassIds = Classroom::withoutGlobalScope('offered')
            ->where('next_class_id', $classroomId)
            ->pluck('id');

        $mappedIds = Student::query()
            ->whereIn('classroom_id', $sourceClassIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->pluck('id');

        $ids = $pendingIds->merge($mappedIds)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $students = Student::query()
            ->with(['stream:id,name', 'classroom.nextClass'])
            ->whereIn('id', $ids)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('admission_number')
            ->get();

        return $students->filter(function (Student $student) use ($classroomId, $year, $streamId) {
            try {
                $placement = $this->forYear($student, $year);
            } catch (\InvalidArgumentException) {
                return false;
            }

            if ((int) ($placement['classroom_id'] ?? 0) !== $classroomId) {
                return false;
            }

            if ($streamId && (int) ($placement['stream_id'] ?? 0) !== $streamId) {
                return false;
            }

            return true;
        })->values();
    }

    private function studentsForPastClassroom(int $classroomId, int $year, ?int $streamId): Collection
    {
        $historyIds = StudentAcademicHistory::query()
            ->where('classroom_id', $classroomId)
            ->whereHas('academicYear', fn ($q) => $q->where('year', $year))
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->pluck('student_id');

        $inferredIds = StudentAcademicHistory::query()
            ->where('classroom_id', $classroomId)
            ->whereIn('promotion_status', ['promoted', 'graduated', 'demoted'])
            ->where(function ($q) use ($year) {
                $q->whereYear('effective_date', $year + 1)
                    ->orWhere(function ($inner) use ($year) {
                        $inner->whereNull('effective_date')
                            ->whereYear('promotion_date', $year + 1);
                    });
            })
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->pluck('student_id');

        $ids = $historyIds->merge($inferredIds)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        // Past years include learners who have since become alumni/archived.
        return Student::withAlumni()
            ->with('stream:id,name')
            ->whereIn('id', $ids)
            ->orderBy('admission_number')
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\ActivityFeeAttendance;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\OptionalFee;
use App\Models\ParentActivityChangeRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\Votehead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ParentCoCurricularService
{
    public function __construct(
        protected ParentAppNotifyService $parentNotify,
        protected AppChannelNotifyService $appChannel,
    ) {}

    /**
     * @return array{year: int, term: int, label: string}
     */
    public function currentPeriod(): array
    {
        $year = (int) setting('current_year', date('Y'));
        $term = (int) setting('current_term', 1);
        if ($term < 1 || $term > 3) {
            $term = 1;
        }

        return [
            'year' => $year,
            'term' => $term,
            'label' => "Term {$term} {$year}",
        ];
    }

    /**
     * Next billing term after the current one (Term 3 rolls into Term 1 of next year).
     *
     * @return array{year: int, term: int, label: string}
     */
    public function upcomingPeriod(?array $current = null): array
    {
        $current ??= $this->currentPeriod();
        $year = (int) $current['year'];
        $term = (int) $current['term'];
        if ($term >= 3) {
            $year++;
            $term = 1;
        } else {
            $term++;
        }

        return [
            'year' => $year,
            'term' => $term,
            'label' => "Term {$term} {$year}",
        ];
    }

    public static function isYogurt(Votehead $votehead): bool
    {
        return (bool) preg_match('/yogh?urt/i', ($votehead->name ?? '').' '.($votehead->code ?? ''));
    }

    public static function iconKey(Votehead $votehead): string
    {
        $n = strtolower(($votehead->name ?? '').' '.($votehead->code ?? ''));
        if (preg_match('/yogh?urt/', $n)) {
            return 'yogurt';
        }
        if (preg_match('/ballet|dance|tutu/', $n)) {
            return 'ballet';
        }
        if (preg_match('/skat/', $n)) {
            return 'skating';
        }
        if (preg_match('/music|piano|song|choir|vocal|guitar/', $n)) {
            return 'music';
        }
        if (preg_match('/swim/', $n)) {
            return 'swimming';
        }

        return 'activities';
    }

    /**
     * Parent-facing snapshot for one child and one year/term.
     *
     * @return array<string, mixed>
     */
    public function snapshotForStudent(Student $student, ?int $year = null, ?int $term = null): array
    {
        $student->loadMissing(['classroom', 'category']);
        $current = $this->currentPeriod();
        $upcoming = $this->upcomingPeriod($current);
        $year = $year ?: $current['year'];
        $term = $term ?: $current['term'];

        $isCurrent = $year === $current['year'] && $term === $current['term'];
        $isUpcoming = $year === $upcoming['year'] && $term === $upcoming['term'];

        $voteheads = Votehead::query()->activityFees()->orderBy('name')->get();
        $enrolledIds = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->pluck('amount', 'votehead_id');

        $pending = ParentActivityChangeRequest::query()
            ->pending()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->get()
            ->keyBy('votehead_id');

        $activities = [];
        $yogurt = [];

        foreach ($voteheads as $votehead) {
            $payload = $this->serializeOffer(
                $student,
                $votehead,
                $year,
                $term,
                $enrolledIds,
                $pending->get($votehead->id),
            );
            if (self::isYogurt($votehead)) {
                $yogurt[] = $payload;
            } else {
                $activities[] = $payload;
            }
        }

        return [
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class_name' => $student->classroom?->name,
                'category_name' => $student->category?->name,
            ],
            'current_term' => $current,
            'upcoming_term' => $upcoming,
            'selected_term' => [
                'year' => $year,
                'term' => $term,
                'label' => "Term {$term} {$year}",
                'is_current' => $isCurrent,
                'is_upcoming' => $isUpcoming,
            ],
            'activities' => $activities,
            'yogurt' => $yogurt,
            'confirmation_message' => 'The school office has been notified and will confirm this change.',
        ];
    }

    /**
     * @param  Collection<int, float|string>  $enrolledIds
     * @return array<string, mixed>
     */
    protected function serializeOffer(
        Student $student,
        Votehead $votehead,
        int $year,
        int $term,
        $enrolledIds,
        ?ParentActivityChangeRequest $pending,
    ): array {
        $enrolled = $enrolledIds->has($votehead->id);
        $amount = $this->amountFor($student, $votehead, $year, $term);
        $billed = $enrolled ? (float) $enrolledIds->get($votehead->id) : null;

        return [
            'votehead_id' => $votehead->id,
            'name' => $votehead->name,
            'kind' => self::isYogurt($votehead) ? 'yogurt' : 'activity',
            'icon' => self::iconKey($votehead),
            'amount' => $amount,
            'enrolled' => $enrolled,
            'billed_amount' => $billed,
            'attendance' => $enrolled ? $this->attendanceSummary($student->id, $votehead->id) : null,
            'pending_request' => $pending ? $this->serializeRequest($pending) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRequest(ParentActivityChangeRequest $request): array
    {
        return [
            'id' => $request->id,
            'student_id' => $request->student_id,
            'student_name' => $request->student?->full_name,
            'admission_number' => $request->student?->admission_number,
            'votehead_id' => $request->votehead_id,
            'activity_name' => $request->votehead?->name,
            'kind' => $request->votehead && self::isYogurt($request->votehead) ? 'yogurt' : 'activity',
            'icon' => $request->votehead ? self::iconKey($request->votehead) : 'activities',
            'year' => $request->year,
            'term' => $request->term,
            'action' => $request->action,
            'status' => $request->status,
            'requested_amount' => (float) $request->requested_amount,
            'parent_note' => $request->parent_note,
            'review_note' => $request->review_note,
            'requested_by_name' => $request->requestedBy?->name,
            'created_at' => optional($request->created_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attendanceSummary(int $studentId, int $voteheadId): array
    {
        $rows = ActivityFeeAttendance::query()
            ->where('votehead_id', $voteheadId)
            ->where('student_id', $studentId)
            ->orderByDesc('attendance_date')
            ->limit(12)
            ->get();

        return [
            'present_count' => $rows->count(),
            'last_date' => optional($rows->first()?->attendance_date)?->toDateString(),
            'recent' => $rows->map(fn (ActivityFeeAttendance $row) => [
                'date' => optional($row->attendance_date)?->toDateString(),
                'attended' => true,
            ])->values()->all(),
        ];
    }

    public function amountFor(Student $student, Votehead $votehead, int $term, int $year): float
    {
        $existing = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('votehead_id', $votehead->id)
            ->where('year', $year)
            ->where('term', $term)
            ->value('amount');
        if ($existing !== null && (float) $existing > 0) {
            return (float) $existing;
        }

        $structure = $this->resolveStructure($student, $year);
        if (! $structure) {
            return 0.0;
        }

        return (float) (FeeCharge::query()
            ->where('fee_structure_id', $structure->id)
            ->where('votehead_id', $votehead->id)
            ->where('term', $term)
            ->value('amount') ?? 0);
    }

    protected function resolveStructure(Student $student, int $year): ?FeeStructure
    {
        if (! $student->classroom_id) {
            return null;
        }

        $base = FeeStructure::query()
            ->where('classroom_id', $student->classroom_id)
            ->where('is_active', true)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                    ->orWhereHas('academicYear', fn ($aq) => $aq->where('year', $year));
            });

        $withCategory = (clone $base);
        if ($student->category_id === null) {
            $withCategory->whereNull('student_category_id');
        } else {
            $withCategory->where('student_category_id', $student->category_id);
        }

        return $withCategory->first() ?? $base->first();
    }

    public function requestChange(
        Student $student,
        User $parent,
        int $voteheadId,
        string $action,
        int $year,
        int $term,
        ?string $note = null,
    ): ParentActivityChangeRequest {
        $action = $action === 'leave' ? 'leave' : 'join';
        $this->assertPeriodAllowed($year, $term);

        $votehead = Votehead::query()
            ->where('id', $voteheadId)
            ->where('is_activity_fee', true)
            ->where('is_active', true)
            ->first();
        if (! $votehead) {
            throw new InvalidArgumentException('That activity is not available.');
        }

        $enrolled = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('votehead_id', $votehead->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->exists();

        if ($action === 'join' && $enrolled) {
            throw new InvalidArgumentException('This child is already enrolled in that activity for the selected term.');
        }
        if ($action === 'leave' && ! $enrolled) {
            throw new InvalidArgumentException('This child is not enrolled in that activity for the selected term.');
        }

        $existing = ParentActivityChangeRequest::query()
            ->pending()
            ->where('student_id', $student->id)
            ->where('votehead_id', $votehead->id)
            ->where('year', $year)
            ->where('term', $term)
            ->first();
        if ($existing) {
            throw new InvalidArgumentException('A change for this activity is already waiting for the school to confirm.');
        }

        $amount = $this->amountFor($student, $votehead, $term, $year);

        $request = ParentActivityChangeRequest::create([
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'year' => $year,
            'term' => $term,
            'action' => $action,
            'status' => 'pending',
            'requested_amount' => $amount,
            'parent_note' => $note,
            'requested_by' => $parent->id,
        ]);

        $this->notifyAdminsOfRequest($request->load(['student', 'votehead', 'requestedBy']));
        $this->parentNotify->notifyActivityChangeSubmitted($student, $votehead->name, $action, $year, $term);

        return $request;
    }

    public function cancelRequest(ParentActivityChangeRequest $request, User $parent): ParentActivityChangeRequest
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('Only a waiting request can be cancelled.');
        }
        if ((int) $request->requested_by !== (int) $parent->id && ! $parent->canAccessStudent((int) $request->student_id)) {
            throw new InvalidArgumentException('You cannot cancel this request.');
        }

        $request->update(['status' => 'cancelled']);

        return $request;
    }

    public function approve(ParentActivityChangeRequest $request, User $admin, ?string $note = null): ParentActivityChangeRequest
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request has already been reviewed.');
        }

        return DB::transaction(function () use ($request, $admin, $note) {
            $request->load(['student', 'votehead']);
            $student = $request->student;
            $votehead = $request->votehead;
            if (! $student || ! $votehead) {
                throw new InvalidArgumentException('This request is missing student or activity data.');
            }

            $amount = $this->amountFor($student, $votehead, (int) $request->term, (int) $request->year);

            if ($request->action === 'join') {
                OptionalFee::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'votehead_id' => $votehead->id,
                        'term' => $request->term,
                        'year' => $request->year,
                    ],
                    [
                        'status' => 'billed',
                        'amount' => $amount > 0 ? $amount : $request->requested_amount,
                        'assigned_by' => $admin->id,
                        'assigned_at' => now(),
                    ]
                );
            } else {
                OptionalFee::query()
                    ->where('student_id', $student->id)
                    ->where('votehead_id', $votehead->id)
                    ->where('term', $request->term)
                    ->where('year', $request->year)
                    ->delete();
            }

            $request->update([
                'status' => 'approved',
                'review_note' => $note,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->parentNotify->notifyActivityChangeReviewed(
                $student,
                $votehead->name,
                $request->action,
                true,
                (int) $request->year,
                (int) $request->term,
            );

            return $request->fresh(['student', 'votehead']);
        });
    }

    public function reject(ParentActivityChangeRequest $request, User $admin, ?string $note = null): ParentActivityChangeRequest
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request has already been reviewed.');
        }

        $request->load(['student', 'votehead']);
        $request->update([
            'status' => 'rejected',
            'review_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        if ($request->student && $request->votehead) {
            $this->parentNotify->notifyActivityChangeReviewed(
                $request->student,
                $request->votehead->name,
                $request->action,
                false,
                (int) $request->year,
                (int) $request->term,
            );
        }

        return $request->fresh(['student', 'votehead']);
    }

    protected function assertPeriodAllowed(int $year, int $term): void
    {
        $current = $this->currentPeriod();
        $upcoming = $this->upcomingPeriod($current);
        $ok = ($year === $current['year'] && $term === $current['term'])
            || ($year === $upcoming['year'] && $term === $upcoming['term']);
        if (! $ok) {
            throw new InvalidArgumentException('Parents can only change activities for the current or upcoming term.');
        }
    }

    protected function notifyAdminsOfRequest(ParentActivityChangeRequest $request): void
    {
        $admins = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Director']);
            })
            ->get();
        if ($admins->isEmpty()) {
            return;
        }

        $child = $request->student?->full_name ?? 'a child';
        $activity = $request->votehead?->name ?? 'an activity';
        $verb = $request->action === 'leave' ? 'leave' : 'join';
        $title = 'Activity change to confirm';
        $body = "{$child} — parent asked to {$verb} {$activity} (Term {$request->term} {$request->year}).";

        $this->appChannel->notifyUsers($admins, $title, $body, [
            'type' => 'parent_activity_request',
            'request_id' => $request->id,
            'student_id' => $request->student_id,
        ]);
    }
}

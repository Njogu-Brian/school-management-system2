<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarkChangeBatch;
use App\Models\Academics\ExamMarkChangeItem;
use App\Models\Student;
use App\Models\User;
use App\Services\SystemAlertService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExamMarkChangeDigestService
{
    public const ALERT_ROLES = [
        'Super Admin', 'Admin', 'System Admin',
        'Senior Teacher', 'senior teacher', 'Senior teacher',
        'Deputy Senior Teacher', 'deputy senior teacher',
    ];

    public const REVIEW_ROLES = [
        'Super Admin', 'Admin', 'System Admin',
        'Senior Teacher', 'senior teacher', 'Senior teacher',
        'Deputy Senior Teacher', 'deputy senior teacher',
        'Academic Administrator', 'Director',
    ];

    public function __construct(private SystemAlertService $alerts) {}

    /**
     * Fold new score edits into the single open exam-mark alert.
     *
     * @param  list<array{
     *     exam_id: int,
     *     student_id: int,
     *     student_name: string,
     *     editor_id: ?int,
     *     editor_name: string,
     *     exam_name: string,
     *     subject_name: string,
     *     classroom_name: string,
     *     score_from: float,
     *     score_to: float
     * }>  $changes
     */
    public function record(array $changes): void
    {
        if ($changes === []) {
            return;
        }

        if (! $this->tablesReady()) {
            $this->raiseIndividualAlerts($changes);

            return;
        }

        $pendingPush = null;
        $remaining = null;
        $ran = $this->withLock(function () use ($changes, &$pendingPush, &$remaining) {
            if (! Cache::get('exam_mark_digest_legacy_clean')) {
                $remaining = $this->collapseLegacyUnlocked();
            }

            $batch = $this->openBatch();
            foreach ($changes as $change) {
                $this->applyChange($batch, $change);
            }
            $pendingPush = $this->publishBatch($batch, true);
        });

        if (! $ran) {
            $this->raiseIndividualAlerts($changes);

            return;
        }

        if ($remaining === 0) {
            Cache::put('exam_mark_digest_legacy_clean', 1, now()->addMinutes(10));
        }

        $this->pushIfNeeded($pendingPush);
    }

    /**
     * Turn the existing one-alert-per-student pile into the open digest.
     */
    public function collapseLegacyAlerts(): void
    {
        if (Cache::get('exam_mark_digest_legacy_clean')) {
            return;
        }

        $remaining = null;
        $ran = $this->withLock(function () use (&$remaining) {
            $remaining = $this->collapseLegacyUnlocked();
        });

        if ($ran && $remaining === 0) {
            Cache::put('exam_mark_digest_legacy_clean', 1, now()->addMinutes(10));
        }
    }

    public function markOpenBatchReviewed(User $user): bool
    {
        return $this->withLock(function () use ($user) {
            $batch = ExamMarkChangeBatch::query()
                ->where('status', 'open')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if (! $batch) {
                return;
            }
            $this->markBatchReviewed((int) $batch->id, $this->fingerprint((int) $batch->id), $user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reviewFromAlert(array $data, User $user): void
    {
        $batchId = (int) data_get($data, 'metadata.batch_id');
        $fingerprint = (string) ($data['fingerprint'] ?? '');
        $ran = $this->withLock(function () use ($batchId, $fingerprint, $user) {
            $this->markBatchReviewed($batchId, $fingerprint, $user);
        });

        if (! $ran && $fingerprint !== '') {
            $this->alerts->acknowledgeByFingerprint($fingerprint, $user);
        }
    }

    public function markBatchReviewed(int $batchId, string $fingerprint, User $user): void
    {
        if ($batchId > 0 && Schema::hasTable('exam_mark_change_batches')) {
            ExamMarkChangeBatch::query()
                ->where('id', $batchId)
                ->where('status', 'open')
                ->update([
                    'status' => 'reviewed',
                    'reviewed_at' => now(),
                    'reviewed_by' => $user->id,
                ]);
        }

        if ($fingerprint !== '') {
            $this->alerts->acknowledgeByFingerprint($fingerprint, $user);
        }
    }

    /**
     * @param  Collection<int, ExamMarkChangeItem>  $items
     * @return array{staff_count: int, paper_count: int, change_count: int, title: string, body: string, staff_names: list<string>}
     */
    public function summarize(Collection $items): array
    {
        $staffNames = $items->pluck('editor_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();
        $staffCount = $staffNames->count();
        $paperCount = $items->pluck('exam_id')->unique()->count();
        $changeCount = $items->count();

        $who = match (true) {
            $staffCount === 1 => (string) $staffNames->first(),
            $staffCount === 0 => 'Staff',
            default => $staffCount.' staff',
        };
        $where = $paperCount === 1 ? 'an exam' : $paperCount.' exam papers';
        $marks = $changeCount === 1 ? '1 mark' : $changeCount.' marks';

        return [
            'staff_count' => $staffCount,
            'paper_count' => $paperCount,
            'change_count' => $changeCount,
            'staff_names' => $staffNames->all(),
            'title' => 'Exam marks changed',
            'body' => $changeCount > 0
                ? "{$who} made changes to {$where} ({$marks})."
                : 'No exam mark changes are waiting for review.',
        ];
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('exam_mark_change_batches') && Schema::hasTable('exam_mark_change_items');
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     */
    private function raiseIndividualAlerts(array $changes): void
    {
        foreach ($changes as $change) {
            $editor = $change['editor_name'] ?? 'A staff member';
            $studentName = $change['student_name'] ?? 'Student';
            $subject = $change['subject_name'] ?? 'Subject';
            $class = $change['classroom_name'] ?? 'Class';
            $from = $change['score_from'] ?? '';
            $to = $change['score_to'] ?? '';
            $examId = (int) ($change['exam_id'] ?? 0);
            $studentId = (int) ($change['student_id'] ?? 0);

            $this->alerts->raiseForRoles(
                self::ALERT_ROLES,
                'Exam mark changed',
                "{$editor} changed {$studentName}'s {$subject} mark ({$change['exam_name']}, {$class}) from {$from} to {$to}.",
                'academics',
                'warning',
                'mark_change_'.sha1($examId.'|'.$studentId.'|'.$from.'|'.$to.'|'.now()->format('YmdHi')),
                '/academics/exams/'.$examId,
                [
                    'exam_id' => $examId,
                    'student_id' => $studentId,
                    'from' => $from,
                    'to' => $to,
                    'edited_by' => $change['editor_id'] ?? null,
                ],
                true,
                false
            );
        }
    }

    public function fingerprint(int $batchId): string
    {
        return 'exam_mark_change_digest_'.$batchId;
    }

    /**
     * @param  array{push?: bool, title?: string, body?: string, data?: array<string, mixed>}|null  $pendingPush
     */
    private function pushIfNeeded(?array $pendingPush): void
    {
        if (! is_array($pendingPush) || empty($pendingPush['push'])) {
            return;
        }

        $this->alerts->pushToRoles(
            self::ALERT_ROLES,
            (string) ($pendingPush['title'] ?? 'Exam marks changed'),
            (string) ($pendingPush['body'] ?? ''),
            is_array($pendingPush['data'] ?? null) ? $pendingPush['data'] : []
        );
    }

    private function withLock(callable $callback): bool
    {
        if (! $this->tablesReady()) {
            if (! Cache::get('exam_mark_digest_schema_warned')) {
                Log::warning('Exam mark change tables are missing. Run migrations to group mark-change alerts.');
                Cache::put('exam_mark_digest_schema_warned', 1, now()->addHour());
            }

            return false;
        }

        $lock = Cache::lock('exam-mark-change-digest', 20);
        try {
            $lock->block(8);
            DB::transaction(function () use ($callback) {
                $callback();
            });

            return true;
        } catch (LockTimeoutException) {
            Log::warning('Exam mark change digest is busy');

            return false;
        } finally {
            $lock->release();
        }
    }

    private function openBatch(): ExamMarkChangeBatch
    {
        $batch = ExamMarkChangeBatch::query()
            ->where('status', 'open')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            return $batch;
        }

        return ExamMarkChangeBatch::query()->create(['status' => 'open']);
    }

    /**
     * @param  array<string, mixed>  $change
     */
    private function applyChange(ExamMarkChangeBatch $batch, array $change): void
    {
        $examId = (int) ($change['exam_id'] ?? 0);
        $studentId = (int) ($change['student_id'] ?? 0);
        if ($examId <= 0 || $studentId <= 0) {
            return;
        }

        $from = (float) ($change['score_from'] ?? 0);
        $to = (float) ($change['score_to'] ?? 0);
        $item = ExamMarkChangeItem::query()
            ->where('batch_id', $batch->id)
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->lockForUpdate()
            ->first();

        if ($item) {
            if (abs((float) $item->score_from - $to) < 0.0001) {
                $item->delete();

                return;
            }

            $item->fill([
                'editor_id' => $change['editor_id'] ?? null,
                'editor_name' => $this->clip((string) ($change['editor_name'] ?? 'A staff member')),
                'student_name' => $this->clip((string) ($change['student_name'] ?? 'Student')),
                'exam_name' => $this->clip((string) ($change['exam_name'] ?? 'Exam')),
                'subject_name' => $this->clip((string) ($change['subject_name'] ?? '')),
                'classroom_name' => $this->clip((string) ($change['classroom_name'] ?? '')),
                'score_to' => $to,
                'changed_at' => $change['changed_at'] ?? now(),
            ])->save();

            return;
        }

        if (abs($from - $to) < 0.0001) {
            return;
        }

        ExamMarkChangeItem::query()->create([
            'batch_id' => $batch->id,
            'exam_id' => $examId,
            'student_id' => $studentId,
            'editor_id' => $change['editor_id'] ?? null,
            'editor_name' => $this->clip((string) ($change['editor_name'] ?? 'A staff member')),
            'student_name' => $this->clip((string) ($change['student_name'] ?? 'Student')),
            'exam_name' => $this->clip((string) ($change['exam_name'] ?? 'Exam')),
            'subject_name' => $this->clip((string) ($change['subject_name'] ?? '')),
            'classroom_name' => $this->clip((string) ($change['classroom_name'] ?? '')),
            'score_from' => $from,
            'score_to' => $to,
            'changed_at' => $change['changed_at'] ?? now(),
        ]);
    }

    /**
     * @return array{push: bool, title: string, body: string, data: array<string, mixed>}|null
     */
    private function publishBatch(ExamMarkChangeBatch $batch, bool $pushIfNew): ?array
    {
        $batch->refresh();
        $items = $batch->items()->get();
        $fingerprint = $this->fingerprint((int) $batch->id);

        if ($items->isEmpty()) {
            $this->alerts->dismissFingerprint($fingerprint);
            $batch->delete();

            return null;
        }

        $summary = $this->summarize($items);
        $metadata = [
            'kind' => 'exam_mark_change_digest',
            'batch_id' => (int) $batch->id,
            'staff_count' => $summary['staff_count'],
            'paper_count' => $summary['paper_count'],
            'change_count' => $summary['change_count'],
        ];
        $created = $this->alerts->syncOpenAlert(
            self::ALERT_ROLES,
            $summary['title'],
            $summary['body'],
            'academics',
            'warning',
            $fingerprint,
            '/academics/exam-marks/changes',
            $metadata
        );

        if (! $pushIfNew || ! $created) {
            return null;
        }

        return [
            'push' => true,
            'title' => $summary['title'],
            'body' => $summary['body'],
            'data' => [
                'type' => 'system_alert',
                'category' => 'academics',
                'severity' => 'warning',
                'deep_link' => '/academics/exam-marks/changes',
                'fingerprint' => $fingerprint,
                'kind' => 'exam_mark_change_digest',
            ],
        ];
    }

    private function collapseLegacyUnlocked(): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $rows = DB::table('notifications')
            ->where('data->category', 'academics')
            ->where('data->title', 'Exam mark changed')
            ->whereNull('data->acknowledged_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(500)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $decoded = [];
        foreach ($rows as $row) {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data)) {
                continue;
            }
            $decoded[] = [
                'id' => (string) $row->id,
                'data' => $data,
                'created_at' => $row->created_at,
            ];
        }

        $examIds = [];
        $studentIds = [];
        $editorIds = [];
        foreach ($decoded as $row) {
            $meta = is_array($row['data']['metadata'] ?? null) ? $row['data']['metadata'] : [];
            if (! empty($meta['exam_id'])) {
                $examIds[] = (int) $meta['exam_id'];
            }
            if (! empty($meta['student_id'])) {
                $studentIds[] = (int) $meta['student_id'];
            }
            if (! empty($meta['edited_by'])) {
                $editorIds[] = (int) $meta['edited_by'];
            }
        }

        $exams = Exam::query()->with(['subject', 'classroom'])->whereIn('id', array_unique($examIds))->get()->keyBy('id');
        $students = Student::query()->whereIn('id', array_unique($studentIds))->get()->keyBy('id');
        $editors = User::query()->whereIn('id', array_unique($editorIds))->get()->keyBy('id');

        $batch = null;
        $ackIds = [];
        foreach ($decoded as $row) {
            $change = $this->changeFromLegacy($row['data'], $row['created_at'], $exams, $students, $editors);
            if ($change === null) {
                continue;
            }
            $batch ??= $this->openBatch();
            $this->applyChange($batch, $change);
            $ackIds[] = $row['id'];
        }

        if ($batch) {
            $this->publishBatch($batch, false);
        }

        if ($ackIds !== []) {
            $this->alerts->acknowledgeIds($ackIds, null, true);
        }

        return $rows->count() >= 500 ? 500 : 0;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int|string, Exam>  $exams
     * @param  Collection<int|string, Student>  $students
     * @param  Collection<int|string, User>  $editors
     * @return array<string, mixed>|null
     */
    private function changeFromLegacy(array $data, mixed $createdAt, Collection $exams, Collection $students, Collection $editors): ?array
    {
        $meta = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $examId = (int) ($meta['exam_id'] ?? 0);
        $studentId = (int) ($meta['student_id'] ?? 0);
        if ($examId <= 0 || $studentId <= 0 || ! isset($meta['from'], $meta['to'])) {
            return null;
        }

        $parsed = $this->parseLegacyMessage((string) ($data['body'] ?? $data['message'] ?? ''));
        $exam = $exams->get($examId);
        $student = $students->get($studentId);
        $editorId = isset($meta['edited_by']) ? (int) $meta['edited_by'] : null;
        $editor = $editorId ? $editors->get($editorId) : null;

        return [
            'exam_id' => $examId,
            'student_id' => $studentId,
            'student_name' => $student
                ? (person_display_name($student, 'full') ?: 'Student')
                : (string) ($parsed['student'] ?? 'Student'),
            'editor_id' => $editorId,
            'editor_name' => $editor?->name ?: (string) ($parsed['editor'] ?? 'A staff member'),
            'exam_name' => $exam?->name ?: (string) ($parsed['paper'] ?? 'Exam'),
            'subject_name' => $exam?->subject?->name ?: (string) ($parsed['subject'] ?? ''),
            'classroom_name' => $exam?->classroom?->name ?: '',
            'score_from' => (float) $meta['from'],
            'score_to' => (float) $meta['to'],
            'changed_at' => $createdAt ? Carbon::parse($createdAt) : now(),
        ];
    }

    /**
     * @return array{editor: string, student: string, subject: string, paper: string}|null
     */
    private function parseLegacyMessage(string $message): ?array
    {
        $pattern = "/^(?<editor>.+) changed (?<student>.+)'s (?<subject>.+) mark \\((?<paper>.+)\\) from -?\\d+(?:\\.\\d+)? to -?\\d+(?:\\.\\d+)?\\.$/u";
        if (! preg_match($pattern, trim($message), $match)) {
            return null;
        }

        return [
            'editor' => trim($match['editor']),
            'student' => trim($match['student']),
            'subject' => trim($match['subject']),
            'paper' => trim($match['paper']),
        ];
    }

    private function clip(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '' : Str::limit($value, 255, '');
    }
}

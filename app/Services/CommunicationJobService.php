<?php

namespace App\Services;

use App\Jobs\BulkSendEmail;
use App\Jobs\BulkSendSMS;
use App\Jobs\BulkSendWhatsAppMessages;
use App\Models\CommunicationJob;
use App\Models\CommunicationJobRecipient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunicationJobService
{
    /**
     * Create a durable job + recipient rows from a recipient list.
     *
     * Each recipient item may include: contact|phone|email, name, recipient_type, recipient_id, entity.
     *
     * @param  array<int, array<string, mixed>>  $recipients
     */
    public function createFromRecipients(
        string $channel,
        string $source,
        array $recipients,
        ?string $title = null,
        ?string $message = null,
        ?string $trackingId = null,
        string $status = 'pending',
        ?\DateTimeInterface $scheduledAt = null,
        ?int $createdBy = null,
        ?Model $sourceRef = null,
        array $meta = []
    ): CommunicationJob {
        $trackingId = $trackingId ?: (string) Str::uuid();

        return DB::transaction(function () use (
            $channel,
            $source,
            $recipients,
            $title,
            $message,
            $trackingId,
            $status,
            $scheduledAt,
            $createdBy,
            $sourceRef,
            $meta
        ) {
            $job = CommunicationJob::create([
                'tracking_id' => $trackingId,
                'source' => $source,
                'channel' => $channel,
                'title' => $title,
                'message' => $message,
                'status' => $status,
                'scheduled_at' => $scheduledAt,
                'created_by' => $createdBy ?? auth()->id(),
                'recipient_count' => count($recipients),
                'source_ref_type' => $sourceRef ? $sourceRef->getMorphClass() : null,
                'source_ref_id' => $sourceRef?->getKey(),
                'meta' => $meta ?: null,
                'started_at' => $status === 'running' ? now() : null,
            ]);

            $rows = [];
            $now = now();
            foreach ($recipients as $item) {
                $rows[] = [
                    'communication_job_id' => $job->id,
                    'contact' => $this->extractContact($item, $channel),
                    'name' => $this->extractName($item),
                    'recipient_type' => $item['recipient_type'] ?? ($item['entity']['type'] ?? ($meta['target'] ?? null)),
                    'recipient_id' => $item['recipient_id']
                        ?? ($item['entity']['id'] ?? null)
                        ?? ($item['student_id'] ?? null),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                CommunicationJobRecipient::insert($chunk);
            }

            return $job->fresh('recipients');
        });
    }

    public function findByTrackingId(string $trackingId): ?CommunicationJob
    {
        return CommunicationJob::where('tracking_id', $trackingId)->latest('id')->first();
    }

    /**
     * Create job if one does not already exist for this tracking id (idempotent for queue retries).
     *
     * @param  array<int, array<string, mixed>>  $recipients
     */
    public function ensureJob(
        string $trackingId,
        string $channel,
        string $source,
        array $recipients,
        ?string $title = null,
        ?string $message = null,
        string $status = 'pending',
        ?int $createdBy = null,
        ?Model $sourceRef = null,
        array $meta = []
    ): CommunicationJob {
        $existing = $this->findByTrackingId($trackingId);
        if ($existing) {
            return $existing;
        }

        return $this->createFromRecipients(
            $channel,
            $source,
            $recipients,
            $title,
            $message,
            $trackingId,
            $status,
            null,
            $createdBy,
            $sourceRef,
            $meta
        );
    }

    public function markRunning(CommunicationJob|string $job): ?CommunicationJob
    {
        $job = $this->resolve($job);
        if (!$job || in_array($job->status, ['cancelled', 'completed'], true)) {
            return $job;
        }

        $job->update([
            'status' => 'running',
            'started_at' => $job->started_at ?? now(),
            'pause_reason' => null,
        ]);

        return $job;
    }

    public function markPaused(CommunicationJob|string $job, string $reason = 'insufficient_sms_credits'): ?CommunicationJob
    {
        $job = $this->resolve($job);
        if (!$job || in_array($job->status, ['cancelled', 'completed'], true)) {
            return $job;
        }

        $job->update([
            'status' => 'paused',
            'pause_reason' => $reason,
        ]);

        return $job;
    }

    public function markCompleted(CommunicationJob|string $job): ?CommunicationJob
    {
        $job = $this->resolve($job);
        if (!$job) {
            return null;
        }

        $this->syncCounts($job);
        $job->update([
            'status' => 'completed',
            'finished_at' => now(),
            'pause_reason' => null,
        ]);

        return $job->fresh();
    }

    public function markFailed(CommunicationJob|string $job, ?string $reason = null): ?CommunicationJob
    {
        $job = $this->resolve($job);
        if (!$job) {
            return null;
        }

        $this->syncCounts($job);
        $meta = $job->meta ?? [];
        if ($reason) {
            $meta['failure_reason'] = $reason;
        }

        $job->update([
            'status' => 'failed',
            'finished_at' => now(),
            'meta' => $meta,
        ]);

        return $job;
    }

    public function cancel(CommunicationJob $job): CommunicationJob
    {
        if (!$job->isCancellable()) {
            return $job;
        }

        CommunicationJobRecipient::where('communication_job_id', $job->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'error_code' => 'CANCELLED',
                'error_message' => 'Cancelled by user',
                'updated_at' => now(),
            ]);

        $this->syncCounts($job);
        $job->update([
            'status' => 'cancelled',
            'finished_at' => now(),
            'pause_reason' => 'manual',
        ]);

        if ($job->source_ref_type && $job->source_ref_id) {
            $this->cancelSourceRef($job);
        }

        return $job->fresh();
    }

    public function pauseJob(CommunicationJob $job, string $reason = 'manual'): CommunicationJob
    {
        if (!$job->isPausable()) {
            return $job;
        }

        $job->update([
            'status' => 'paused',
            'pause_reason' => $reason,
        ]);

        return $job;
    }

    public function resumeJob(CommunicationJob $job): CommunicationJob
    {
        if (!$job->isResumable()) {
            return $job;
        }

        $pending = $job->recipients()->where('status', 'pending')->count();
        if ($pending === 0 && in_array($job->status, ['paused'], true)) {
            // Nothing left to send
            return $this->markCompleted($job) ?? $job;
        }

        $nextStatus = $job->scheduled_at && $job->scheduled_at->isFuture() ? 'scheduled' : 'pending';
        $job->update([
            'status' => $nextStatus,
            'pause_reason' => null,
        ]);

        $this->redispatchRemaining($job);

        return $job->fresh();
    }

    public function syncCounts(CommunicationJob $job): void
    {
        $counts = CommunicationJobRecipient::where('communication_job_id', $job->id)
            ->selectRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN status IN ('skipped','cancelled') THEN 1 ELSE 0 END) as skipped_count")
            ->selectRaw('COUNT(*) as recipient_count')
            ->first();

        $job->forceFill([
            'sent_count' => (int) ($counts->sent_count ?? 0),
            'failed_count' => (int) ($counts->failed_count ?? 0),
            'skipped_count' => (int) ($counts->skipped_count ?? 0),
            'recipient_count' => (int) ($counts->recipient_count ?? $job->recipient_count),
        ])->save();
    }

    public function markRecipientByContact(
        CommunicationJob|string $job,
        string $contact,
        string $status,
        ?int $recipientId = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?int $logId = null
    ): void {
        $job = $this->resolve($job);
        if (!$job) {
            return;
        }

        $query = CommunicationJobRecipient::where('communication_job_id', $job->id)
            ->where('contact', $contact)
            ->where('status', 'pending');

        if ($recipientId !== null) {
            $query->where(function ($q) use ($recipientId) {
                $q->where('recipient_id', $recipientId)->orWhereNull('recipient_id');
            });
        }

        $recipient = $query->orderBy('id')->first();
        if (!$recipient) {
            return;
        }

        $recipient->update([
            'status' => $status,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'sent_at' => in_array($status, ['sent', 'failed', 'skipped'], true) ? now() : null,
            'communication_log_id' => $logId,
        ]);

        if (in_array($status, ['sent', 'failed', 'skipped', 'cancelled'], true)) {
            $this->bumpCount($job, $status);
        }
    }

    public function pauseAllActiveForCredits(): int
    {
        return CommunicationJob::query()
            ->whereIn('status', ['pending', 'scheduled', 'running'])
            ->where('channel', 'sms')
            ->update([
                'status' => 'paused',
                'pause_reason' => 'insufficient_sms_credits',
                'updated_at' => now(),
            ]);
    }

    public function resumeAllPausedForCredits(): Collection
    {
        $jobs = CommunicationJob::query()
            ->where('status', 'paused')
            ->where('pause_reason', 'insufficient_sms_credits')
            ->orderBy('id')
            ->get();

        foreach ($jobs as $job) {
            $pending = $job->recipients()->where('status', 'pending')->count();
            if ($pending === 0) {
                $this->markCompleted($job);
                continue;
            }

            // SMS redispatched via CommunicationPauseService paused_bulk_sms meta.
            // Email/WhatsApp continue from remaining recipient rows.
            if ($job->channel === 'sms') {
                $job->update([
                    'status' => 'pending',
                    'pause_reason' => null,
                ]);
            } else {
                $this->resumeJob($job);
            }
        }

        return $jobs;
    }

    protected function redispatchRemaining(CommunicationJob $job): void
    {
        $pending = $job->recipients()->where('status', 'pending')->get();
        if ($pending->isEmpty()) {
            return;
        }

        $meta = $job->meta ?? [];
        $target = $meta['target'] ?? 'custom';
        $senderId = $meta['sender_id'] ?? null;
        $userId = $job->created_by;

        $recipients = $pending->map(function (CommunicationJobRecipient $r) use ($job) {
            $row = [
                'phone' => $job->channel === 'sms' || $job->channel === 'whatsapp' ? $r->contact : null,
                'email' => $job->channel === 'email' ? $r->contact : null,
                'contact' => $r->contact,
                'name' => $r->name,
                'recipient_type' => $r->recipient_type,
                'recipient_id' => $r->recipient_id,
                'entity' => [
                    'id' => $r->recipient_id,
                    'type' => $r->recipient_type,
                    'name' => $r->name,
                ],
            ];
            if ($job->channel === 'whatsapp') {
                $row['number'] = $r->contact;
            }

            return $row;
        })->all();

        $job->update(['status' => 'running', 'started_at' => $job->started_at ?? now()]);

        match ($job->channel) {
            'sms' => BulkSendSMS::dispatch(
                $job->tracking_id,
                $recipients,
                (string) $job->message,
                (string) ($job->title ?? 'SMS'),
                $target,
                $senderId,
                $userId
            ),
            'email' => BulkSendEmail::dispatch(
                $job->tracking_id,
                $recipients,
                (string) $job->message,
                (string) ($job->title ?? 'Email'),
                $target,
                $meta['attachment_path'] ?? null,
                $userId
            ),
            'whatsapp' => BulkSendWhatsAppMessages::dispatch(
                $job->tracking_id,
                $recipients,
                (string) $job->message,
                (string) ($job->title ?? 'WhatsApp'),
                $target,
                $meta['media_url'] ?? null,
                true,
                $userId
            ),
            default => null,
        };
    }

    protected function cancelSourceRef(CommunicationJob $job): void
    {
        try {
            $ref = $job->sourceRef;
            if (!$ref) {
                return;
            }

            if (method_exists($ref, 'update') && isset($ref->status)) {
                if (in_array($ref->status, ['pending', 'active', 'paused'], true)) {
                    $ref->update(['status' => 'cancelled']);
                }
            }
        } catch (\Throwable $e) {
            // Source may have been deleted
        }
    }

    protected function bumpCount(CommunicationJob $job, string $status): void
    {
        $column = match ($status) {
            'sent' => 'sent_count',
            'failed' => 'failed_count',
            'skipped', 'cancelled' => 'skipped_count',
            default => null,
        };
        if ($column) {
            CommunicationJob::where('id', $job->id)->increment($column);
        }
    }

    protected function resolve(CommunicationJob|string $job): ?CommunicationJob
    {
        if ($job instanceof CommunicationJob) {
            return $job;
        }

        return $this->findByTrackingId($job);
    }

    protected function extractContact(array $item, string $channel): ?string
    {
        if (!empty($item['contact'])) {
            return (string) $item['contact'];
        }

        return match ($channel) {
            'email' => $item['email'] ?? null,
            'whatsapp' => $item['number'] ?? $item['phone'] ?? null,
            default => $item['phone'] ?? null,
        };
    }

    protected function extractName(array $item): ?string
    {
        if (!empty($item['name'])) {
            return (string) $item['name'];
        }
        if (!empty($item['parent_name'])) {
            return (string) $item['parent_name'];
        }
        if (!empty($item['entity']['name'])) {
            return (string) $item['entity']['name'];
        }
        $entity = $item['entity'] ?? null;
        if (is_object($entity) && isset($entity->name)) {
            return (string) $entity->name;
        }

        return null;
    }
}

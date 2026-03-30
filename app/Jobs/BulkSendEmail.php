<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Mail\GenericMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BulkSendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 7200; // 2 hours for large batches

    protected string $trackingId;
    protected array $recipients; // [['email' => '...', 'entity' => [...]], ...]
    protected string $message;
    protected string $subject;
    protected string $target;
    protected ?string $attachmentPath;
    protected ?int $userId;

    public function __construct(
        string $trackingId,
        array $recipients,
        string $message,
        string $subject,
        string $target,
        ?string $attachmentPath = null,
        ?int $userId = null
    ) {
        $this->trackingId = $trackingId;
        $this->recipients = $recipients;
        $this->message = $message;
        $this->subject = $subject;
        $this->target = $target;
        $this->attachmentPath = $attachmentPath;
        $this->userId = $userId ?? auth()->id();
    }

    public function handle(): void
    {
        $totalRecipients = count($this->recipients);
        $sentCount = 0;
        $failedCount = 0;
        $processed = 0;
        $reportRows = [];

        // Idempotency: if this job is retried/restarted with the same tracking_id,
        // avoid re-sending recipients that were already marked as sent.
        $existingSentKeys = [];
        try {
            $existingLogs = CommunicationLog::where('channel', 'email')
                ->where('tracking_id', $this->trackingId)
                ->where('scope', 'email')
                ->where('type', 'email')
                ->where('status', 'sent')
                ->get(['contact', 'recipient_id']);

            foreach ($existingLogs as $log) {
                $existingSentKeys[$log->contact . '|' . ($log->recipient_id ?? 'null')] = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk Email idempotency pre-check failed; proceeding without it', [
                'tracking_id' => $this->trackingId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Bulk Email send job started', [
            'tracking_id' => $this->trackingId,
            'total_recipients' => $totalRecipients,
        ]);

        $this->updateProgress([
            'status' => 'processing',
            'total' => $totalRecipients,
            'sent' => 0,
            'failed' => 0,
            'processed' => 0,
        ]);

        foreach ($this->recipients as $item) {
            $email = $item['email'] ?? null;
            $entityData = $item['entity'] ?? $item;
            if (!$email) {
                continue;
            }
            $processed++;

            try {
                $entity = $this->resolveEntity($entityData);
                $recipientId = $entity->id ?? null;
                $idempotencyKey = $email . '|' . ($recipientId ?? 'null');
                if (isset($existingSentKeys[$idempotencyKey])) {
                    $sentCount++;
                    $reportRows[] = $this->buildReportRow(
                        $email,
                        $entityData,
                        'sent',
                        'Skipped: already sent (idempotent retry)'
                    );
                    continue;
                }
                $personalized = replace_placeholders($this->message, $entity);
                Mail::to($email)->send(new GenericMail($this->subject, $personalized, $this->attachmentPath));

                $sentCount++;
                $reportRows[] = $this->buildReportRow($email, $entityData, 'sent');

                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id' => $recipientId,
                    'contact' => $email,
                    'channel' => 'email',
                    'title' => $this->subject,
                    'message' => $personalized,
                    'type' => 'email',
                    'status' => 'sent',
                    'response' => 'OK',
                    'classroom_id' => $entity->classroom_id ?? null,
                    'scope' => 'email',
                    'sent_at' => now(),
                    'tracking_id' => $this->trackingId,
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                $entity = $entity ?? (is_array($entityData) ? (object) $entityData : (object) []);
                Log::error('Email send error in bulk job', [
                    'tracking_id' => $this->trackingId,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                $reportRows[] = $this->buildReportRow($email, $entityData ?? [], 'failed', $e->getMessage());
                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id' => $entity->id ?? null,
                    'contact' => $email,
                    'channel' => 'email',
                    'title' => $this->subject,
                    'message' => $this->message,
                    'type' => 'email',
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                    'classroom_id' => $entity->classroom_id ?? null,
                    'scope' => 'email',
                    'sent_at' => now(),
                    'tracking_id' => $this->trackingId,
                ]);
            }

            if ($processed % 10 === 0 || $processed === $totalRecipients) {
                $this->updateProgress([
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'processed' => $processed,
                ]);
            }
        }

        $reportId = 'dr_email_' . $this->trackingId;
        $report = [
            'channel' => 'email',
            'recipients' => $reportRows,
            'summary' => ['sent' => $sentCount, 'failed' => $failedCount, 'skipped' => 0],
            'created_at' => now()->toIso8601String(),
        ];
        Cache::put("comm_report:{$reportId}", $report, now()->addHours(24));

        $key = 'comm_recent_report_ids';
        $recent = Cache::get($key, []);
        array_unshift($recent, [
            'id' => $reportId,
            'channel' => 'email',
            'summary' => $report['summary'],
            'created_at' => $report['created_at'],
        ]);
        $recent = array_slice($recent, 0, 20);
        Cache::put($key, $recent, now()->addHours(2));

        $this->updateProgress([
            'status' => 'completed',
            'sent' => $sentCount,
            'failed' => $failedCount,
            'processed' => $processed,
            'report_id' => $reportId,
        ]);

        Log::info('Bulk Email send job completed', [
            'tracking_id' => $this->trackingId,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total' => $totalRecipients,
        ]);
    }

    protected function resolveEntity($entityData)
    {
        if (is_array($entityData) && isset($entityData['type'], $entityData['id'])) {
            $entityClass = $entityData['type'];
            if (class_exists($entityClass)) {
                try {
                    $entity = $entityClass::find($entityData['id']);
                    if ($entity) return $entity;
                } catch (\Exception $e) {
                    // fall through
                }
            }
        }
        return is_array($entityData) ? (object) $entityData : $entityData;
    }

    protected function buildReportRow(string $email, $entityData, string $status, ?string $reason = null): array
    {
        $name = 'Custom / ' . $email;
        if (is_array($entityData)) {
            $studentName = trim(($entityData['first_name'] ?? '') . ' ' . ($entityData['last_name'] ?? ''));
            $name = $studentName ?: $email;
        }
        $row = ['name' => $name, 'contact' => $email, 'status' => $status];
        if ($reason) $row['reason'] = $reason;
        return $row;
    }

    protected function updateProgress(array $data): void
    {
        $key = "bulk_email_progress:{$this->trackingId}";
        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, $data), now()->addHours(24));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk Email send job failed', [
            'tracking_id' => $this->trackingId,
            'error' => $exception->getMessage(),
        ]);
        $this->updateProgress(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}

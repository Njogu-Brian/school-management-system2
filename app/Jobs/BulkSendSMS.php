<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BulkSendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 7200; // 2 hours for large batches

    protected string $trackingId;
    protected array $recipients; // [['phone' => '...', 'entity' => [...]], ...]
    protected string $message;
    protected string $title;
    protected string $target;
    protected ?string $senderId;
    protected ?int $userId;

    public function __construct(
        string $trackingId,
        array $recipients,
        string $message,
        string $title,
        string $target,
        ?string $senderId = null,
        ?int $userId = null
    ) {
        $this->trackingId = $trackingId;
        $this->recipients = $recipients;
        $this->message = $message;
        $this->title = $title;
        $this->target = $target;
        $this->senderId = $senderId;
        $this->userId = $userId ?? auth()->id();
    }

    public function handle(SMSService $smsService): void
    {
        $totalRecipients = count($this->recipients);
        $sentCount = 0;
        $failedCount = 0;
        $processed = 0;
        $reportRows = [];
        $chosenSender = $this->senderId === 'finance' ? $smsService->getFinanceSenderId() : null;

        // Idempotency: if this job is retried/restarted with the same tracking_id,
        // avoid re-sending recipients that were already marked as sent.
        $existingSentKeys = [];
        try {
            $existingLogs = CommunicationLog::where('channel', 'sms')
                ->where('tracking_id', $this->trackingId)
                ->where('scope', 'sms')
                ->where('type', 'sms')
                ->where('status', 'sent')
                ->get(['contact', 'recipient_id']);

            foreach ($existingLogs as $log) {
                $existingSentKeys[$log->contact . '|' . ($log->recipient_id ?? 'null')] = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk SMS idempotency pre-check failed; proceeding without it', [
                'tracking_id' => $this->trackingId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Bulk SMS send job started', [
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
            $phone = $item['phone'] ?? null;
            $entityData = $item['entity'] ?? $item;
            if (!$phone) {
                continue;
            }
            $processed++;

            try {
                $entity = $this->resolveEntity($entityData);

                $recipientId = $entity->id ?? null;
                $idempotencyKey = $phone . '|' . ($recipientId ?? 'null');
                if (isset($existingSentKeys[$idempotencyKey])) {
                    $sentCount++;
                    $reportRows[] = $this->buildReportRow(
                        $phone,
                        $entityData,
                        'sent',
                        'Skipped: already sent (idempotent retry)'
                    );
                    continue;
                }

                $personalized = replace_placeholders($this->message, $entity);
                $response = $smsService->sendSMS($phone, $personalized, $chosenSender);

                $status = 'sent';
                if (strtolower(data_get($response, 'status', 'sent')) !== 'success'
                    && strtolower(data_get($response, 'status', 'sent')) !== 'sent') {
                    $status = 'failed';
                }

                $status === 'sent' ? $sentCount++ : $failedCount++;
                $reportRows[] = $this->buildReportRow($phone, $entityData, $status,
                    $status !== 'sent' ? (is_array($response) ? json_encode($response) : (string) $response) : null);

                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id' => $recipientId,
                    'contact' => $phone,
                    'channel' => 'sms',
                    'title' => $this->title,
                    'message' => $personalized,
                    'type' => 'sms',
                    'status' => $status,
                    'response' => $response,
                    'classroom_id' => $entity->classroom_id ?? null,
                    'scope' => 'sms',
                    'sent_at' => now(),
                    'tracking_id' => $this->trackingId,
                    'provider_id' => data_get($response, 'transactionId')
                        ?? data_get($response, 'id')
                        ?? data_get($response, 'message_id')
                        ?? data_get($response, 'MessageID'),
                    'provider_status' => strtolower(data_get($response, 'status', 'sent')),
                ]);

                if ($status === 'sent') {
                    $existingSentKeys[$idempotencyKey] = true;
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $entity = $entity ?? (is_array($entityData) ? (object) $entityData : (object) []);
                Log::error('SMS send error in bulk job', [
                    'tracking_id' => $this->trackingId,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
                $reportRows[] = $this->buildReportRow($phone, $entityData ?? [], 'failed', $e->getMessage());
                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id' => $entity->id ?? null,
                    'contact' => $phone,
                    'channel' => 'sms',
                    'title' => $this->title,
                    'message' => $this->message,
                    'type' => 'sms',
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                    'classroom_id' => $entity->classroom_id ?? null,
                    'scope' => 'sms',
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

        $reportId = 'dr_sms_' . $this->trackingId;
        $report = [
            'channel' => 'sms',
            'recipients' => $reportRows,
            'summary' => ['sent' => $sentCount, 'failed' => $failedCount, 'skipped' => 0],
            'created_at' => now()->toIso8601String(),
        ];
        Cache::put("comm_report:{$reportId}", $report, now()->addHours(24));

        $key = 'comm_recent_report_ids';
        $recent = Cache::get($key, []);
        array_unshift($recent, [
            'id' => $reportId,
            'channel' => 'sms',
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

        Log::info('Bulk SMS send job completed', [
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

    protected function buildReportRow(string $phone, $entityData, string $status, ?string $reason = null): array
    {
        $name = 'Custom / ' . $phone;
        if (is_array($entityData)) {
            $studentName = trim(($entityData['first_name'] ?? '') . ' ' . ($entityData['last_name'] ?? ''));
            $name = $studentName ?: $phone;
        }
        $row = ['name' => $name, 'contact' => $phone, 'status' => $status];
        if ($reason) $row['reason'] = $reason;
        return $row;
    }

    protected function updateProgress(array $data): void
    {
        $key = "bulk_sms_progress:{$this->trackingId}";
        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, $data), now()->addHours(24));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk SMS send job failed', [
            'tracking_id' => $this->trackingId,
            'error' => $exception->getMessage(),
        ]);
        $this->updateProgress(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}

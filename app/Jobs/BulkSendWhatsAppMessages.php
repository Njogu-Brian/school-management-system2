<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Student;
use App\Services\CommunicationJobService;
use App\Services\CommunicationPauseService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BulkSendWhatsAppMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Recipients processed per job run (keeps each job under queue worker timeout). */
    public const CHUNK_SIZE = 12;

    /**
     * One attempt per chunk; idempotency + next-chunk dispatch handle recovery.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Per-chunk timeout (12 msgs × ~10s gap + API time).
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Tracking ID for this bulk send operation
     *
     * @var string
     */
    protected $trackingId;

    /**
     * Recipients data: ['phone' => entity]
     *
     * @var array
     */
    protected $recipients;

    /**
     * Message content
     *
     * @var string
     */
    protected $message;

    /**
     * Message title
     *
     * @var string
     */
    protected $title;

    /**
     * Target type
     *
     * @var string
     */
    protected $target;

    /**
     * Media URL (optional)
     *
     * @var string|null
     */
    protected $mediaUrl;

    /**
     * Skip already sent messages
     *
     * @var bool
     */
    protected $skipSent;

    /**
     * User ID who created the job
     *
     * @var int|null
     */
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $trackingId,
        array $recipients,
        string $message,
        string $title,
        string $target,
        ?string $mediaUrl = null,
        bool $skipSent = true,
        ?int $userId = null
    ) {
        $this->trackingId = $trackingId;
        $this->recipients = $recipients;
        $this->message = $message;
        $this->title = $title;
        $this->target = $target;
        $this->mediaUrl = $mediaUrl;
        $this->skipSent = $skipSent;
        $this->userId = $userId ?? auth()->id();
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        $jobService = app(CommunicationJobService::class);
        $commJob = $jobService->ensureJob(
            $this->trackingId,
            'whatsapp',
            str_starts_with($this->trackingId, 'scheduled_fee_') ? 'scheduled_fee'
                : (str_starts_with($this->trackingId, 'scheduled_') ? 'scheduled_comm' : 'manual_bulk'),
            array_values($this->recipients),
            $this->title,
            $this->message,
            'pending',
            $this->userId,
            null,
            ['target' => $this->target, 'media_url' => $this->mediaUrl]
        );

        if ($commJob->status === 'cancelled') {
            return;
        }

        if (CommunicationPauseService::isPaused() || $commJob->status === 'paused') {
            CommunicationPauseService::pauseBulkProgress($this->trackingId, 'whatsapp', [
                'total' => count($this->recipients),
            ]);
            $jobService->markPaused($commJob, $commJob->pause_reason ?? 'insufficient_sms_credits');

            return;
        }

        $jobService->markRunning($commJob);

        $batch = array_slice($this->recipients, 0, self::CHUNK_SIZE);
        $remaining = array_slice($this->recipients, self::CHUNK_SIZE);
        $progress = $this->getProgress();

        $totalRecipients = (int) ($progress['total'] ?? 0);
        if ($totalRecipients <= 0) {
            $totalRecipients = count($this->recipients) + count($remaining);
        }

        $sentCount = (int) ($progress['sent'] ?? 0);
        $skippedCount = (int) ($progress['skipped'] ?? 0);
        $failedCount = (int) ($progress['failed'] ?? 0);
        $processed = (int) ($progress['processed'] ?? 0);
        $reportRows = [];
        $delayBetweenMessages = \App\Services\WhatsAppBulkRateLimiter::delaySeconds();
        $lastSentTime = 0;

        // Idempotency: if this job is retried/restarted with the same tracking_id,
        // avoid re-sending recipients that were already marked as sent.
        $existingSentKeys = [];
        try {
            $existingLogs = CommunicationLog::where('channel', 'whatsapp')
                ->where('tracking_id', $this->trackingId)
                ->where('scope', 'whatsapp')
                ->where('type', 'whatsapp')
                ->where('status', 'sent')
                ->get(['contact', 'recipient_id']);

            foreach ($existingLogs as $log) {
                $existingSentKeys[$log->contact . '|' . ($log->recipient_id ?? 'null')] = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk WhatsApp idempotency pre-check failed; proceeding without it', [
                'tracking_id' => $this->trackingId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Bulk WhatsApp send job started', [
            'tracking_id' => $this->trackingId,
            'batch_size' => count($batch),
            'remaining_batches' => (int) ceil(count($remaining) / max(1, self::CHUNK_SIZE)),
            'total_recipients' => $totalRecipients,
            'skip_sent' => $this->skipSent,
        ]);

        if (empty($batch)) {
            $this->finalizeJob($sentCount, $failedCount, $skippedCount, $processed, $totalRecipients);

            return;
        }

        $this->updateProgress([
            'status' => 'processing',
            'total' => $totalRecipients,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'processed' => $processed,
        ]);

        foreach ($batch as $item) {
            $freshJob = $jobService->findByTrackingId($this->trackingId);
            if ($freshJob && in_array($freshJob->status, ['cancelled', 'paused'], true)) {
                if ($freshJob->status === 'paused') {
                    CommunicationPauseService::pauseBulkProgress($this->trackingId, 'whatsapp', [
                        'total' => $totalRecipients,
                        'processed' => $processed,
                        'sent' => $sentCount,
                        'failed' => $failedCount,
                    ]);
                }

                return;
            }
            if (CommunicationPauseService::isPaused()) {
                CommunicationPauseService::pauseBulkProgress($this->trackingId, 'whatsapp', [
                    'total' => $totalRecipients,
                    'processed' => $processed,
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                ]);
                $jobService->markPaused($this->trackingId);

                return;
            }

            $phone = $item['phone'] ?? null;
            $entityData = $item['entity'] ?? $item;
            if (!$phone) {
                continue;
            }
            $processed++;
            
            try {
                $recipientId = is_array($entityData) ? ($entityData['id'] ?? null) : ($entityData->id ?? null);
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

                // Check if already sent (if skipSent is enabled)
                if ($this->skipSent) {
                    $alreadySent = CommunicationLog::where('contact', $phone)
                        ->where('channel', 'whatsapp')
                        ->where('status', 'sent')
                        ->where('message', 'like', '%' . substr($this->message, 0, 50) . '%')
                        ->where('created_at', '>=', now()->subHours(24)) // Check last 24 hours
                        ->exists();
                    
                    if ($alreadySent) {
                        $skippedCount++;
                        $reportRows[] = $this->buildReportRow($phone, $entityData, 'skipped', 'Already sent in last 24h');
                        $this->updateProgress([
                            'skipped' => $skippedCount,
                            'processed' => $processed,
                        ]);
                        continue;
                    }
                }

                // Rate limit: wait before each WhatsApp API call
                if ($lastSentTime > 0) {
                    $currentTime = time();
                    $timeSinceLastMessage = $currentTime - $lastSentTime;
                    
                    if ($timeSinceLastMessage < $delayBetweenMessages) {
                        $waitTime = $delayBetweenMessages - $timeSinceLastMessage;
                        sleep($waitTime);
                    }
                } else {
                    \App\Services\WhatsAppBulkRateLimiter::waitBeforeSend('global');
                }

                // Reconstruct entity from data
                $entity = null;
                if (is_array($entityData) && isset($entityData['type']) && isset($entityData['id'])) {
                    $entityClass = $entityData['type'];
                    if (class_exists($entityClass)) {
                        try {
                            $entity = $entityClass::find($entityData['id']);
                        } catch (\Exception $e) {
                            Log::warning('Could not load entity in bulk send', [
                                'type' => $entityClass,
                                'id' => $entityData['id'],
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
                
                // Fallback to object if entity not found (for placeholders)
                if (!$entity) {
                    $entity = is_array($entityData) ? (object)$entityData : $entityData;
                }
                
                if (! empty($item['message'])) {
                    $personalized = $item['message'];
                } elseif (! empty($item['parent_name'])) {
                    $parent = ($entity instanceof \App\Models\Student) ? $entity->parent : null;
                    $personalized = replace_placeholders(
                        $this->message,
                        $entity,
                        parent_recipient_placeholder_extra((string) $item['parent_name'], $parent, $item['parent_slot'] ?? null)
                    );
                } else {
                    $personalized = replace_placeholders($this->message, $entity);
                }
                $finalMessage = $this->mediaUrl ? ($personalized . "\n\nMedia: " . $this->mediaUrl) : $personalized;

                // Send message
                $response = $whatsAppService->sendMessage($phone, $finalMessage);
                $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';

                // Check for rate limiting error
                $responseBody = data_get($response, 'body', []);
                $isRateLimited = false;
                $retryAfter = null;

                if (is_array($responseBody)) {
                    $errorMessage = data_get($responseBody, 'message', '');
                    if (is_string($errorMessage) && 
                        (str_contains(strtolower($errorMessage), 'account protection') || 
                         str_contains(strtolower($errorMessage), 'rate limit'))) {
                        $isRateLimited = true;
                        $retryAfter = data_get($responseBody, 'retry_after');
                        if (is_numeric($retryAfter) && $retryAfter > $delayBetweenMessages) {
                            $delayBetweenMessages = (int) ceil($retryAfter);
                            Log::info('WhatsApp rate limit detected, adjusting delay', [
                                'tracking_id' => $this->trackingId,
                                'new_delay' => $delayBetweenMessages,
                            ]);
                        }
                    }
                }

                // Retry if rate limited
                if ($isRateLimited && $status === 'failed') {
                    $waitTime = $retryAfter ?? $delayBetweenMessages;
                    Log::info("Rate limited, waiting before retry", [
                        'tracking_id' => $this->trackingId,
                        'phone' => $phone,
                        'wait_time' => $waitTime,
                    ]);
                    sleep((int) ceil($waitTime));
                    
                    $response = $whatsAppService->sendMessage($phone, $finalMessage);
                    $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                }

                // Log the communication
                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'whatsapp',
                    'title'          => $this->title,
                    'message'        => $finalMessage,
                    'type'           => 'whatsapp',
                    'status'         => $status,
                    'response'       => $response,
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'provider_id'    => data_get($response, 'message_id')
                                        ?? data_get($response, 'body.messages.0.id')
                                        ?? data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                    'tracking_id'   => $this->trackingId,
                ]);
                $jobService->markRecipientByContact(
                    $this->trackingId,
                    $phone,
                    $status,
                    $entity->id ?? null
                );

                if ($status === 'sent') {
                    $sentCount++;
                    $reportRows[] = $this->buildReportRow($phone, $entityData, 'sent');
                } else {
                    $failedCount++;
                    $reportRows[] = $this->buildReportRow($phone, $entityData, 'failed', json_encode(data_get($response, 'body') ?? $response));
                }

                $lastSentTime = time();

                // Update progress every 10 messages or at the end
                if ($processed % 10 === 0 || $processed === $totalRecipients) {
                    $this->updateProgress([
                        'sent' => $sentCount,
                        'failed' => $failedCount,
                        'skipped' => $skippedCount,
                        'processed' => $processed,
                    ]);
                }

            } catch (\Throwable $e) {
                $failedCount++;
                Log::error('WhatsApp send error in bulk job', [
                    'tracking_id' => $this->trackingId,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $reportRows[] = $this->buildReportRow($phone, $entityData ?? [], 'failed', $e->getMessage());
                // Log failed attempt
                CommunicationLog::create([
                    'recipient_type' => $this->target,
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'whatsapp',
                    'title'          => $this->title,
                    'message'        => $this->message,
                    'type'           => 'whatsapp',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'tracking_id'    => $this->trackingId,
                ]);

                $this->updateProgress([
                    'failed' => $failedCount,
                    'processed' => $processed,
                ]);
            }
        }

        $this->mergeReportRows($reportRows);

        if (!empty($remaining)) {
            $this->updateProgress([
                'status' => 'processing',
                'sent' => $sentCount,
                'failed' => $failedCount,
                'skipped' => $skippedCount,
                'processed' => $processed,
            ]);

            Log::info('Bulk WhatsApp dispatching next chunk', [
                'tracking_id' => $this->trackingId,
                'remaining' => count($remaining),
            ]);

            self::dispatch(
                $this->trackingId,
                array_values($remaining),
                $this->message,
                $this->title,
                $this->target,
                $this->mediaUrl,
                $this->skipSent,
                $this->userId
            );

            return;
        }

        $this->finalizeJob($sentCount, $failedCount, $skippedCount, $processed, $totalRecipients);
    }

    protected function finalizeJob(
        int $sentCount,
        int $failedCount,
        int $skippedCount,
        int $processed,
        int $totalRecipients
    ): void {
        $reportId = 'dr_wa_' . $this->trackingId;
        $allRows = Cache::get("comm_report_rows:{$this->trackingId}", []);
        Cache::forget("comm_report_rows:{$this->trackingId}");

        $report = [
            'channel' => 'whatsapp',
            'recipients' => $allRows,
            'summary' => ['sent' => $sentCount, 'failed' => $failedCount, 'skipped' => $skippedCount],
            'created_at' => now()->toIso8601String(),
        ];
        Cache::put("comm_report:{$reportId}", $report, now()->addHours(24));

        $key = 'comm_recent_report_ids';
        $recent = Cache::get($key, []);
        array_unshift($recent, [
            'id' => $reportId,
            'channel' => 'whatsapp',
            'summary' => $report['summary'],
            'created_at' => $report['created_at'],
        ]);
        $recent = array_slice($recent, 0, 20);
        Cache::put($key, $recent, now()->addHours(2));

        $this->updateProgress([
            'status' => 'completed',
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'processed' => $processed,
            'report_id' => $reportId,
        ]);

        try {
            app(CommunicationJobService::class)->markCompleted($this->trackingId);
        } catch (\Throwable $e) {
            // ignore
        }

        Log::info('Bulk WhatsApp send job completed', [
            'tracking_id' => $this->trackingId,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'total' => $totalRecipients,
        ]);
    }

    protected function mergeReportRows(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $key = "comm_report_rows:{$this->trackingId}";
        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, $rows), now()->addHours(24));
    }

    protected function getProgress(): array
    {
        return Cache::get("bulk_whatsapp_progress:{$this->trackingId}", []);
    }

    /**
     * Build a report row for delivery report
     */
    protected function buildReportRow(string $phone, $entityData, string $status, ?string $reason = null): array
    {
        $name = 'Custom / ' . $phone;
        if (is_array($entityData)) {
            $studentName = trim(($entityData['first_name'] ?? '') . ' ' . ($entityData['last_name'] ?? ''));
            $name = $studentName ?: $phone;
        }
        $row = ['name' => $name, 'contact' => $phone, 'status' => $status];
        if ($reason) {
            $row['reason'] = $reason;
        }
        return $row;
    }

    /**
     * Update progress in cache
     */
    protected function updateProgress(array $data): void
    {
        $key = "bulk_whatsapp_progress:{$this->trackingId}";
        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, $data), now()->addHours(24));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk WhatsApp send job failed', [
            'tracking_id' => $this->trackingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->updateProgress([
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);
    }
}

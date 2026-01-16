<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Student;
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

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200; // 2 hours for large batches

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
     * Create a new job instance.
     */
    public function __construct(
        string $trackingId,
        array $recipients,
        string $message,
        string $title,
        string $target,
        ?string $mediaUrl = null,
        bool $skipSent = true
    ) {
        $this->trackingId = $trackingId;
        $this->recipients = $recipients;
        $this->message = $message;
        $this->title = $title;
        $this->target = $target;
        $this->mediaUrl = $mediaUrl;
        $this->skipSent = $skipSent;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        $totalRecipients = count($this->recipients);
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $processed = 0;
        $delayBetweenMessages = 5; // Default 5 seconds for account protection
        $lastSentTime = 0;

        Log::info('Bulk WhatsApp send job started', [
            'tracking_id' => $this->trackingId,
            'total_recipients' => $totalRecipients,
            'skip_sent' => $this->skipSent,
        ]);

        $this->updateProgress([
            'status' => 'processing',
            'total' => $totalRecipients,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 0,
        ]);

        foreach ($this->recipients as $phone => $entityData) {
            $processed++;
            
            try {
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
                        $this->updateProgress([
                            'skipped' => $skippedCount,
                            'processed' => $processed,
                        ]);
                        continue;
                    }
                }

                // Calculate delay needed since last message
                if ($lastSentTime > 0) {
                    $currentTime = time();
                    $timeSinceLastMessage = $currentTime - $lastSentTime;
                    
                    if ($timeSinceLastMessage < $delayBetweenMessages) {
                        $waitTime = $delayBetweenMessages - $timeSinceLastMessage;
                        sleep($waitTime);
                    }
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
                
                // Replace placeholders
                $personalized = replace_placeholders($this->message, $entity);
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
                    'provider_id'    => data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                ]);

                if ($status === 'sent') {
                    $sentCount++;
                } else {
                    $failedCount++;
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
                ]);

                $this->updateProgress([
                    'failed' => $failedCount,
                    'processed' => $processed,
                ]);
            }
        }

        // Final progress update
        $this->updateProgress([
            'status' => 'completed',
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'processed' => $processed,
        ]);

        Log::info('Bulk WhatsApp send job completed', [
            'tracking_id' => $this->trackingId,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'total' => $totalRecipients,
        ]);
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

<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\SwimmingWallet;
use App\Models\PaymentLink;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BulkSendSwimmingBalanceNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * Tracking ID for this bulk send operation
     *
     * @var string
     */
    protected $trackingId;

    /**
     * Student IDs to send notifications for
     *
     * @var array
     */
    protected $studentIds;

    /**
     * Channels to send to (sms, email, whatsapp)
     *
     * @var array
     */
    protected $channels;

    /**
     * User ID who initiated the bulk send
     *
     * @var int
     */
    protected $userId;

    /**
     * Amount for payment link (null means use balance amount)
     *
     * @var float|null
     */
    protected $paymentLinkAmount;

    /**
     * Expiration days for payment link
     *
     * @var int
     */
    protected $expirationDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $trackingId,
        array $studentIds,
        array $channels,
        int $userId,
        ?float $paymentLinkAmount = null,
        int $expirationDays = 30
    ) {
        $this->trackingId = $trackingId;
        $this->studentIds = $studentIds;
        $this->channels = $channels;
        $this->userId = $userId;
        $this->paymentLinkAmount = $paymentLinkAmount;
        $this->expirationDays = $expirationDays;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $totalStudents = count($this->studentIds);
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];
        $processed = 0;

        Log::info('Bulk send swimming balance job started', [
            'tracking_id' => $this->trackingId,
            'total_students' => $totalStudents,
            'channels' => $this->channels,
            'user_id' => $this->userId
        ]);

        // Initialize progress in cache
        $this->updateProgress([
            'status' => 'processing',
            'total' => $totalStudents,
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'current_student' => null,
            'errors' => [],
            'started_at' => now()->toDateTimeString()
        ]);

        // Process students in smaller batches
        $batchSize = 10;
        $studentChunks = array_chunk($this->studentIds, $batchSize);

        foreach ($studentChunks as $chunk) {
            $students = Student::with(['parent', 'family'])
                ->whereIn('id', $chunk)
                ->get();

            foreach ($students as $student) {
                $processed++;
                
                try {
                    $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
                    $balance = (float) $wallet->balance;

                    // Skip if balance is zero or positive (only send for negative balances)
                    if ($balance >= 0) {
                        $skippedCount++;
                        $this->updateProgress([
                            'processed' => $processed,
                            'skipped' => $skippedCount
                        ]);
                        continue;
                    }

                    $currentInfo = [
                        'student' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
                        'admission_number' => $student->admission_number,
                        'balance' => number_format(abs($balance), 2)
                    ];

                    // Update progress with current student
                    $this->updateProgress([
                        'processed' => $processed,
                        'current_student' => $currentInfo
                    ]);

                    // Check parent contact info
                    $parent = $student->parent ?? null;
                    if (!$parent) {
                        $skippedCount++;
                        $this->updateProgress([
                            'skipped' => $skippedCount
                        ]);
                        continue;
                    }

                    // Create payment link for the balance amount
                    $linkAmount = $this->paymentLinkAmount ?? abs($balance);
                    $paymentLink = $this->createPaymentLink($student, $linkAmount);

                    // Send via each channel
                    $sentChannels = [];
                    foreach ($this->channels as $channel) {
                        try {
                            $hasContact = $this->checkContactAvailable($parent, $channel);

                            if ($hasContact) {
                                $this->sendBalanceNotificationByChannel(
                                    $student,
                                    $wallet,
                                    $paymentLink,
                                    $channel,
                                    $parent
                                );
                                $sentChannels[] = $channel;
                                
                                // Small delay between sends to avoid rate limiting
                                usleep(200000); // 0.2 seconds
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to send {$channel} for student {$student->id}", [
                                'tracking_id' => $this->trackingId,
                                'student_id' => $student->id,
                                'channel' => $channel,
                                'error' => $e->getMessage()
                            ]);
                            
                            if (count($errors) < 10) {
                                $errors[] = "{$student->admission_number} ({$channel}): " . $e->getMessage();
                            }
                        }
                    }

                    if (!empty($sentChannels)) {
                        $sentCount++;
                        $this->updateProgress([
                            'sent' => $sentCount
                        ]);
                    } else {
                        $skippedCount++;
                        $this->updateProgress([
                            'skipped' => $skippedCount
                        ]);
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Bulk send failed for student', [
                        'tracking_id' => $this->trackingId,
                        'student_id' => $student->id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    if (count($errors) < 10) {
                        $errors[] = "Student {$student->admission_number ?? 'Unknown'}: " . $e->getMessage();
                    }
                    
                    $this->updateProgress([
                        'failed' => $failedCount
                    ]);
                }
            }

            // Small delay between batches
            usleep(100000); // 0.1 seconds
        }

        // Mark as completed
        $this->updateProgress([
            'status' => 'completed',
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
            'errors' => $errors,
            'current_student' => null,
            'completed_at' => now()->toDateTimeString()
        ]);

        Log::info('Bulk send swimming balance job completed', [
            'tracking_id' => $this->trackingId,
            'total' => $totalStudents,
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * Create payment link for swimming balance
     */
    protected function createPaymentLink(Student $student, float $amount): PaymentLink
    {
        $expiresAt = now()->addDays($this->expirationDays);
        
        return PaymentLink::create([
            'student_id' => $student->id,
            'invoice_id' => null, // No invoice for swimming
            'family_id' => $student->family_id,
            'amount' => $amount,
            'currency' => 'KES',
            'description' => 'Swimming balance payment',
            'account_reference' => 'SWIM-' . $student->admission_number,
            'expires_at' => $expiresAt,
            'max_uses' => 1,
            'created_by' => $this->userId,
            'status' => 'active',
            'metadata' => [
                'is_swimming' => true,
                'swimming_balance' => abs($amount),
            ],
        ]);
    }

    /**
     * Update progress in cache
     */
    protected function updateProgress(array $data): void
    {
        $cacheKey = "bulk_send_swimming_balance_progress_{$this->trackingId}";
        $current = Cache::get($cacheKey, []);
        $updated = array_merge($current, $data);
        Cache::put($cacheKey, $updated, now()->addHours(2));
    }

    /**
     * Check if parent has contact info for the specified channel
     */
    protected function checkContactAvailable($parent, string $channel): bool
    {
        if ($channel === 'sms') {
            return !empty($parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null);
        } elseif ($channel === 'email') {
            return !empty($parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null);
        } elseif ($channel === 'whatsapp') {
            return !empty($parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->guardian_whatsapp
                ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null);
        }
        
        return false;
    }

    /**
     * Send balance notification via a specific channel
     */
    protected function sendBalanceNotificationByChannel(
        Student $student,
        SwimmingWallet $wallet,
        PaymentLink $paymentLink,
        string $channel,
        $parent
    ): void {
        $balance = abs((float) $wallet->balance);
        $paymentLinkUrl = $paymentLink->getPaymentUrl();

        // Get parent name and greeting
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? null;
        $greeting = $parentName ? "Dear {$parentName}" : "Dear Parent";

        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');

        $variables = [
            'parent_name' => $parentName ?? 'Parent',
            'greeting' => $greeting,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'balance' => 'Ksh ' . number_format($balance, 2),
            'payment_link' => $paymentLinkUrl,
            'school_name' => $schoolName,
        ];

        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        // Get services
        $commService = app(CommunicationService::class);

        // Send via specific channel
        if ($channel === 'sms') {
            $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            if ($parentPhone) {
                $smsTemplate = CommunicationTemplate::where('code', 'swimming_balance_sms')
                    ->first();
                
                if (!$smsTemplate) {
                    $smsTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'swimming_balance_sms'],
                        [
                            'title' => 'Swimming Balance SMS',
                            'type' => 'sms',
                            'subject' => null,
                            'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of {{balance}}.\n\nPay now: {{payment_link}}\n\nThank you.\n{{school_name}}",
                        ]
                    );
                }

                $smsMessage = $replacePlaceholders($smsTemplate->content, $variables);
                $smsService = app(\App\Services\SMSService::class);
                $financeSenderId = $smsService->getFinanceSenderId();
                $commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title, $financeSenderId);
            }
        } elseif ($channel === 'email') {
            $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
            if ($parentEmail) {
                $emailTemplate = CommunicationTemplate::where('code', 'swimming_balance_email')
                    ->first();
                
                if (!$emailTemplate) {
                    $emailTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'swimming_balance_email'],
                        [
                            'title' => 'Swimming Balance Email',
                            'type' => 'email',
                            'subject' => 'Outstanding Swimming Balance – {{student_name}}',
                            'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of {{balance}}.\n\nPlease make payment using the link below:\n{{payment_link}}\n\nThank you for your cooperation.\n\nKind regards,\n{{school_name}} Finance Office",
                        ]
                    );
                }

                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                // Convert newlines to HTML breaks for email
                $emailContent = nl2br($emailContent);
                $commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent);
            }
        } elseif ($channel === 'whatsapp') {
            $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->guardian_whatsapp
                ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            
            if ($whatsappPhone) {
                $whatsappTemplate = CommunicationTemplate::where('code', 'swimming_balance_whatsapp')
                    ->first();
                
                if (!$whatsappTemplate) {
                    $whatsappTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'swimming_balance_whatsapp'],
                        [
                            'title' => 'Swimming Balance WhatsApp',
                            'type' => 'whatsapp',
                            'subject' => null,
                            'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of *{{balance}}*.\n\nPay now: {{payment_link}}\n\nThank you.\n{{school_name}}",
                        ]
                    );
                }

                $whatsappMessage = $replacePlaceholders($whatsappTemplate->content, $variables);
                $whatsappService = app(\App\Services\WhatsAppService::class);
                $response = $whatsappService->sendMessage($whatsappPhone, $whatsappMessage);
                
                $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $parent->id ?? null,
                    'contact'        => $whatsappPhone,
                    'channel'        => 'whatsapp',
                    'title'          => $whatsappTemplate->subject ?? $whatsappTemplate->title,
                    'message'        => $whatsappMessage,
                    'type'           => 'whatsapp',
                    'status'         => $status,
                    'response'       => $response,
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'provider_id'    => data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk send swimming balance job failed', [
            'tracking_id' => $this->trackingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Update progress to failed
        $this->updateProgress([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'failed_at' => now()->toDateTimeString()
        ]);
    }
}

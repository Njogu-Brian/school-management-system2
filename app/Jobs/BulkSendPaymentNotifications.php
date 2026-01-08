<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Http\Controllers\Finance\PaymentController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BulkSendPaymentNotifications implements ShouldQueue
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
     * Payment IDs to send notifications for
     *
     * @var array
     */
    protected $paymentIds;

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
     * Create a new job instance.
     */
    public function __construct(string $trackingId, array $paymentIds, array $channels, int $userId)
    {
        $this->trackingId = $trackingId;
        $this->paymentIds = $paymentIds;
        $this->channels = $channels;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $totalPayments = count($this->paymentIds);
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];
        $processed = 0;

        Log::info('Bulk send job started', [
            'tracking_id' => $this->trackingId,
            'total_payments' => $totalPayments,
            'channels' => $this->channels,
            'user_id' => $this->userId
        ]);

        // Initialize progress in cache
        $this->updateProgress([
            'status' => 'processing',
            'total' => $totalPayments,
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'current_payment' => null,
            'errors' => [],
            'started_at' => now()->toDateTimeString()
        ]);

        // Process payments in smaller batches
        $batchSize = 10;
        $paymentChunks = array_chunk($this->paymentIds, $batchSize);

        foreach ($paymentChunks as $chunk) {
            $payments = Payment::with(['student.parent', 'paymentMethod'])
                ->whereIn('id', $chunk)
                ->where('reversed', false)
                ->get();

            foreach ($payments as $payment) {
                $processed++;
                
                try {
                    $currentInfo = [
                        'receipt' => $payment->receipt_number,
                        'student' => $payment->student->full_name ?? 'Unknown',
                        'amount' => number_format($payment->amount, 2)
                    ];

                    // Update progress with current payment
                    $this->updateProgress([
                        'processed' => $processed,
                        'current_payment' => $currentInfo
                    ]);

                    // Get already sent channels for this payment
                    $bulkSent = $payment->bulk_sent_channels ?? [];
                    
                    // Filter out channels that have already been sent
                    $channelsToSend = array_filter($this->channels, function($channel) use ($bulkSent) {
                        return !in_array($channel, $bulkSent);
                    });

                    // Skip if all channels have already been sent
                    if (empty($channelsToSend)) {
                        $skippedCount++;
                        $this->updateProgress([
                            'skipped' => $skippedCount
                        ]);
                        continue;
                    }

                    // Check parent contact info
                    $parent = $payment->student->parent ?? null;
                    if (!$parent) {
                        $skippedCount++;
                        $this->updateProgress([
                            'skipped' => $skippedCount
                        ]);
                        continue;
                    }

                    // Track which channels were successfully sent
                    $sentChannels = [];

                    // Send via each channel that hasn't been sent yet
                    foreach ($channelsToSend as $channel) {
                        try {
                            $hasContact = $this->checkContactAvailable($parent, $channel);

                            if ($hasContact) {
                                $this->sendPaymentNotificationByChannel($payment, $channel, $parent);
                                $sentChannels[] = $channel;
                                
                                // Small delay between sends to avoid rate limiting
                                usleep(200000); // 0.2 seconds
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to send {$channel} for payment {$payment->id}", [
                                'tracking_id' => $this->trackingId,
                                'payment_id' => $payment->id,
                                'channel' => $channel,
                                'error' => $e->getMessage()
                            ]);
                            
                            if (count($errors) < 10) { // Limit errors stored
                                $errors[] = "Payment #{$payment->receipt_number} ({$channel}): " . $e->getMessage();
                            }
                        }
                    }

                    // Mark channels as bulk sent if any were successfully sent
                    if (!empty($sentChannels)) {
                        $payment->markBulkSent($sentChannels);
                        $sentCount++;
                        $this->updateProgress([
                            'sent' => $sentCount
                        ]);
                    } else {
                        // If no channels were sent (no contact info), still count as skipped
                        $skippedCount++;
                        $this->updateProgress([
                            'skipped' => $skippedCount
                        ]);
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Bulk send failed for payment', [
                        'tracking_id' => $this->trackingId,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    if (count($errors) < 10) {
                        $errors[] = "Payment #{$payment->receipt_number}: " . $e->getMessage();
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
            'current_payment' => null,
            'completed_at' => now()->toDateTimeString()
        ]);

        Log::info('Bulk send job completed', [
            'tracking_id' => $this->trackingId,
            'total' => $totalPayments,
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * Update progress in cache
     */
    protected function updateProgress(array $data): void
    {
        $cacheKey = "bulk_send_progress_{$this->trackingId}";
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
     * Send payment notification via a specific channel
     */
    protected function sendPaymentNotificationByChannel(Payment $payment, string $channel, $parent): void
    {
        $student = $payment->student;

        // Get receipt link
        if (!$payment->public_token) {
            $payment->public_token = Payment::generatePublicToken();
            $payment->save();
        }

        $receiptLink = url('/receipt/' . $payment->public_token);

        // Get parent name and greeting
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? null;
        $greeting = $parentName ? "Dear {$parentName}" : "Dear Parent";

        // Calculate outstanding balance
        $outstandingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        $payment->refresh();
        $carriedForward = $payment->unallocated_amount ?? 0;
        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');

        $variables = [
            'parent_name' => $parentName ?? 'Parent',
            'greeting' => $greeting,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'amount' => 'Ksh ' . number_format($payment->amount, 2),
            'receipt_number' => $payment->receipt_number,
            'transaction_code' => $payment->transaction_code,
            'payment_date' => $payment->payment_date->format('d M Y'),
            'receipt_link' => $receiptLink,
            'finance_portal_link' => $receiptLink,
            'outstanding_amount' => 'Ksh ' . number_format($outstandingBalance, 2),
            'carried_forward' => number_format($carriedForward, 2),
            'school_name' => $schoolName,
        ];

        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        // Get services
        $commService = app(\App\Services\CommunicationService::class);

        // Send via specific channel
        if ($channel === 'sms') {
            $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            if ($parentPhone) {
                $smsTemplate = CommunicationTemplate::where('code', 'payment_receipt_sms')
                    ->orWhere('code', 'finance_payment_received_sms')
                    ->first();
                
                if (!$smsTemplate) {
                    $smsTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_sms'],
                        [
                            'title' => 'Payment Receipt SMS',
                            'type' => 'sms',
                            'subject' => null,
                            'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{finance_portal_link}}\n\nThank you for your continued support.\n{{school_name}}",
                        ]
                    );
                }

                $smsMessage = $replacePlaceholders($smsTemplate->content, $variables);
                $smsService = app(\App\Services\SMSService::class);
                $financeSenderId = $smsService->getFinanceSenderId();
                $commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title, $financeSenderId, $payment->id);
            }
        } elseif ($channel === 'email') {
            $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
            if ($parentEmail) {
                $emailTemplate = CommunicationTemplate::where('code', 'payment_receipt_email')
                    ->orWhere('code', 'finance_payment_received_email')
                    ->first();
                
                if (!$emailTemplate) {
                    $emailTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_email'],
                        [
                            'title' => 'Payment Receipt Email',
                            'type' => 'email',
                            'subject' => 'Payment Receipt – {{student_name}}',
                            'content' => "{{greeting}},\n\nThank you for your payment of {{amount}} received on {{payment_date}} for {{student_name}}.\nPlease find the payment receipt attached.\n\nYou may also view invoices, receipts, and statements here:\n{{finance_portal_link}}\n\nWe appreciate your cooperation.\n\nKind regards,\n{{school_name}} Finance Office",
                        ]
                    );
                }

                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                $receiptService = app(\App\Services\ReceiptService::class);
                $pdfPath = $receiptService->generateReceipt($payment, ['save' => true]);
                $commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent, $pdfPath);
            }
        } elseif ($channel === 'whatsapp') {
            $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->guardian_whatsapp
                ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            
            if ($whatsappPhone) {
                $whatsappTemplate = CommunicationTemplate::where('code', 'payment_receipt_whatsapp')
                    ->orWhere('code', 'finance_payment_received_whatsapp')
                    ->first();
                
                if (!$whatsappTemplate) {
                    $whatsappTemplate = CommunicationTemplate::firstOrCreate(
                        ['code', 'payment_receipt_whatsapp'],
                        [
                            'title' => 'Payment Receipt WhatsApp',
                            'type' => 'whatsapp',
                            'subject' => null,
                            'content' => "{{greeting}},\n\nWe have received a payment of {{amount}} for {{student_name}} ({{admission_number}}) on {{payment_date}}.\n\nReceipt Number: {{receipt_number}}\n\nView or download your receipt here:\n{{receipt_link}}\n\nThank you for your continued support.\n{{school_name}}",
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
                    'payment_id'     => $payment->id,
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
        Log::error('Bulk send job failed', [
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


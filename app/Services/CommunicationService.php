<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\ParentInfo;
use App\Exceptions\InsufficientSmsCreditsException;
use App\Services\CommunicationPauseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

class CommunicationService
{
    protected $emailService;
    protected $smsService;
    protected $whatsAppService;

    public function __construct(EmailService $emailService, SMSService $smsService, WhatsAppService $whatsAppService)
    {
        $this->emailService = $emailService;
        $this->smsService   = $smsService;
        $this->whatsAppService = $whatsAppService;
    }

    public function sendSMS($recipientType, $recipientId, $phone, $message, $title = null, $senderId = null, $paymentId = null): array
    {
        if ($blocked = $this->blockedParentSchoolNotification('sms', $recipientType, $recipientId, $phone, $message, $title, $paymentId)) {
            return $blocked;
        }

        try {
            $result = $this->smsService->sendSMS($phone, $message, $senderId);

            // Check for insufficient credits error
            if (isset($result['status']) && $result['status'] === 'error' && isset($result['error_code']) && $result['error_code'] === 'INSUFFICIENT_CREDITS') {
                $balance = (float) ($result['balance'] ?? 0);
                Log::error('SMS sending blocked: Insufficient credits', [
                    'phone' => $phone,
                    'balance' => $balance
                ]);

                CommunicationLog::create([
                    'recipient_type' => $recipientType,
                    'recipient_id'   => $recipientId,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'title'          => $title ?? 'SMS Notification',
                    'message'        => $message,
                    'type'           => 'sms',
                    'status'         => 'paused',
                    'response'       => $result,
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                    'error_code'     => 'INSUFFICIENT_CREDITS',
                    'payment_id'     => $paymentId,
                ]);

                CommunicationPauseService::pauseDueToInsufficientCredits($balance, 'CommunicationService::sendSMS');

                throw new InsufficientSmsCreditsException($balance);
            }

            // Check if the provider returned an error status
            $providerStatus = strtolower(data_get($result, 'status', 'sent'));
            $statusCode = data_get($result, 'statusCode');
            $reason = strtolower(data_get($result, 'reason', ''));
            $msgId = data_get($result, 'msgId');
            $transactionId = data_get($result, 'transactionId');
            
            // Determine final status based on provider response
            // Success conditions: status is "success" AND (statusCode is "200" or 200) AND reason is "success"
            $isSuccess = (
                $providerStatus === 'success' && 
                ($statusCode === '200' || $statusCode === 200) && 
                $reason === 'success'
            );
            
            // Also check for other success indicators (some providers may use different formats)
            if (!$isSuccess && $providerStatus === 'sent') {
                $isSuccess = true;
            }
            
            // NOTE: Empty msgId in initial response is normal for HostPinnacle
            // The messageId will be provided later via DLR webhook when delivery status is updated
            // If status is success and transactionId is present, message is queued successfully
            if ($isSuccess && empty($msgId)) {
                // This is expected behavior - msgId comes via webhook
                // Only check balance if we want to log low balance warnings
                $balance = $this->smsService->checkBalance();
                
                Log::info('SMS sent successfully - msgId will be provided via DLR webhook', [
                    'phone' => $phone,
                    'transaction_id' => $transactionId,
                    'status' => $providerStatus,
                    'statusCode' => $statusCode,
                    'note' => 'Empty msgId in initial response is normal. MessageId will be received via DLR webhook.'
                ]);
                
                // Log warning only if balance is low
                if ($balance !== null && $balance <= 10) {
                    Log::warning('SMS sent but account balance is low', [
                        'phone' => $phone,
                        'transaction_id' => $transactionId,
                        'balance' => $balance,
                        'recommendation' => 'Consider topping up account balance soon'
                    ]);
                }
                
                // Still treat as success - message is queued, msgId will come via webhook
                $finalStatus = 'sent';
            } else {
                $finalStatus = $isSuccess ? 'sent' : 'failed';
            }
            
            if (!$isSuccess) {
                Log::warning('SMS provider returned error or failed validation', [
                    'phone' => $phone,
                    'status' => $providerStatus,
                    'statusCode' => $statusCode,
                    'reason' => $reason,
                    'msgId' => $msgId,
                    'result' => $result
                ]);
            }

            // Determine error code
            $errorCode = null;
            if (!$isSuccess) {
                if (empty($msgId) && $providerStatus === 'success') {
                    $errorCode = 'NO_MSG_ID'; // Message accepted but not queued (likely insufficient balance)
                } else {
                    $errorCode = $statusCode ?? 'UNKNOWN_ERROR';
                }
            }

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'title'          => $title ?? 'SMS Notification',
                'message'        => $message,
                'type'           => 'sms',
                'status'         => $finalStatus,
                'response'       => is_array($result) ? $result : ['response' => (string) $result],
                'scope'          => 'sms',
                'sent_at'        => now(),
                'provider_id'    => $transactionId ?? data_get($result, 'id') ?? data_get($result, 'message_id') ?? data_get($result, 'MessageID'),
                'provider_status' => $providerStatus,
                'error_code'     => $errorCode,
                'payment_id'     => $paymentId,
            ]);

            Log::info("SMS attempt finished", [
                'phone'   => $phone,
                'status'  => $finalStatus,
                'provider_status' => $providerStatus,
                'statusCode' => $statusCode,
                'result'  => $result,
            ]);

            return [
                'success' => $finalStatus === 'sent',
                'status' => $finalStatus,
                'provider_status' => $providerStatus,
                'status_code' => $statusCode,
                'result' => $result,
                'error' => $finalStatus === 'sent'
                    ? null
                    : (data_get($result, 'reason') ?? data_get($result, 'message') ?? 'SMS send failed'),
            ];
        } catch (\Throwable $e) {
            Log::error("SMS sending threw exception: " . $e->getMessage(), [
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'title'          => $title ?? 'SMS Notification',
                'message'        => $message,
                'type'           => 'sms',
                'status'         => 'failed',
                'response'       => ['error' => $e->getMessage()],
                'scope'          => 'sms',
                'sent_at'        => now(),
                'payment_id'     => $paymentId ?? null,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'provider_status' => 'exception',
                'status_code' => null,
                'result' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendEmail($recipientType, $recipientId, $email, $subject, $htmlMessage, $attachmentPath = null)
    {
        if ($this->blockedParentSchoolNotification('email', $recipientType, $recipientId, $email, $htmlMessage, $subject)) {
            return;
        }

        try {
            // GenericMail expects relative path from storage/app/public, not full path
            $relativeAttachmentPath = null;
            if ($attachmentPath) {
                // If it's already a full path, extract relative part
                if (strpos($attachmentPath, storage_path('app/public/')) === 0) {
                    $relativeAttachmentPath = str_replace(storage_path('app/public/'), '', $attachmentPath);
                } elseif (strpos($attachmentPath, 'app/public/') !== false) {
                    $relativeAttachmentPath = str_replace('app/public/', '', $attachmentPath);
                } elseif (file_exists(storage_path('app/public/' . $attachmentPath))) {
                    $relativeAttachmentPath = $attachmentPath;
                }
            }
            
            $mail = new GenericMail($subject, $htmlMessage, $relativeAttachmentPath);
            Mail::to($email)->send($mail);

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'title'          => $subject,
                'message'        => $htmlMessage,
                'type'           => 'email',
                'status'         => 'sent',
                'response'       => ['status' => 'sent'],
                'scope'          => 'email',
                'sent_at'        => now(),
            ]);
        } catch (\Exception $e) {
            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'title'          => $subject,
                'message'        => $htmlMessage,
                'type'           => 'email',
                'status'         => 'failed',
                'response'       => ['error' => $e->getMessage()],
                'scope'          => 'email',
                'sent_at'        => now(),
            ]);
            
            throw $e; // Re-throw to allow caller to handle
        }
    }

    public function sendWhatsApp($recipientType, $recipientId, $phone, $message, $title = null, $paymentId = null)
    {
        if ($this->blockedParentSchoolNotification('whatsapp', $recipientType, $recipientId, $phone, $message, $title, $paymentId)) {
            return;
        }

        try {
            $result = $this->whatsAppService->sendMessage($phone, $message);
            
            $status = data_get($result, 'status') === 'success' ? 'sent' : 'failed';
            
            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'whatsapp',
                'title'          => $title ?? 'WhatsApp Notification',
                'message'        => $message,
                'type'           => 'whatsapp',
                'status'         => $status,
                'response'       => is_array($result) ? $result : ['response' => (string) $result],
                'scope'          => 'whatsapp',
                'sent_at'        => now(),
                'provider_id'    => data_get($result, 'body.id') ?? data_get($result, 'body.message_id'),
                'provider_status' => data_get($result, 'status'),
                'payment_id'     => $paymentId,
            ]);
            
            if ($status !== 'sent') {
                Log::warning('WhatsApp sending failed', [
                    'phone' => $phone,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("WhatsApp sending threw exception: " . $e->getMessage(), [
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'whatsapp',
                'title'          => $title ?? 'WhatsApp Notification',
                'message'        => $message,
                'type'           => 'whatsapp',
                'status'         => 'failed',
                'response'       => ['error' => $e->getMessage()],
                'scope'          => 'whatsapp',
                'sent_at'        => now(),
                'payment_id'     => $paymentId ?? null,
            ]);
            
            throw $e; // Re-throw to allow caller to handle
        }
    }

    /**
     * Block parent-targeted sends when contact is muted, guardian-only, or not on the allowed list.
     *
     * @return array<string, mixed>|null  SMS skip payload, or null to proceed
     */
    protected function blockedParentSchoolNotification(
        string $channel,
        $recipientType,
        $recipientId,
        string $contact,
        string $message,
        ?string $title = null,
        ?int $paymentId = null,
    ): ?array {
        if ($recipientType !== 'parent' || ! $recipientId) {
            return null;
        }

        $parent = ParentInfo::find($recipientId);
        if (! $parent) {
            return $this->logSkippedParentNotification($channel, $recipientId, $contact, $message, $title, $paymentId, 'parent_not_found');
        }

        if (! $parent->contactAllowedForSchoolNotification($channel, $contact)) {
            return $this->logSkippedParentNotification(
                $channel,
                $recipientId,
                $contact,
                $message,
                $title,
                $paymentId,
                'muted_or_disallowed_contact'
            );
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function logSkippedParentNotification(
        string $channel,
        int $recipientId,
        string $contact,
        string $message,
        ?string $title,
        ?int $paymentId,
        string $reason,
    ): ?array {
        Log::info('Skipped parent school notification', [
            'parent_id' => $recipientId,
            'channel' => $channel,
            'contact' => $contact,
            'reason' => $reason,
        ]);

        CommunicationLog::create([
            'recipient_type' => 'parent',
            'recipient_id' => $recipientId,
            'contact' => $contact,
            'channel' => $channel,
            'title' => $title ?? 'Notification',
            'message' => $message,
            'type' => $channel,
            'status' => 'skipped',
            'response' => ['reason' => $reason],
            'scope' => $channel,
            'sent_at' => now(),
            'payment_id' => $paymentId,
        ]);

        if ($channel === 'sms') {
            return [
                'success' => false,
                'status' => 'skipped',
                'provider_status' => 'skipped',
                'status_code' => null,
                'result' => ['reason' => $reason],
                'error' => $reason,
            ];
        }

        return ['skipped' => true, 'reason' => $reason];
    }
}

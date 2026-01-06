<?php

namespace App\Services;

use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

class CommunicationService
{
    protected $emailService;
    protected $smsService;

    public function __construct(EmailService $emailService, SMSService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService   = $smsService;
    }

    public function sendSMS($recipientType, $recipientId, $phone, $message, $title = null, $senderId = null)
    {
        try {
            $result = $this->smsService->sendSMS($phone, $message, $senderId);

            // Check for insufficient credits error
            if (isset($result['status']) && $result['status'] === 'error' && isset($result['error_code']) && $result['error_code'] === 'INSUFFICIENT_CREDITS') {
                $balance = $result['balance'] ?? 0;
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
                    'status'         => 'failed',
                    'response'       => $result,
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                    'error_code'     => 'INSUFFICIENT_CREDITS',
                ]);

                // Re-throw as exception so caller can handle it
                throw new \Exception("Insufficient SMS credits. Current balance: {$balance}");
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
            
            // CRITICAL: If msgId is empty, the message was NOT queued for delivery
            // This often happens when balance is 0 - provider accepts request but doesn't process it
            if ($isSuccess && empty($msgId)) {
                Log::error('SMS provider returned success but msgId is empty - message NOT queued for delivery', [
                    'phone' => $phone,
                    'transaction_id' => $transactionId,
                    'status' => $providerStatus,
                    'statusCode' => $statusCode,
                    'reason' => $reason,
                    'result' => $result,
                    'likely_cause' => 'Insufficient balance or account issue'
                ]);
                
                // Treat as failed - message won't be delivered
                $isSuccess = false;
                $finalStatus = 'failed';
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
            ]);

            Log::info("SMS attempt finished", [
                'phone'   => $phone,
                'status'  => $finalStatus,
                'provider_status' => $providerStatus,
                'statusCode' => $statusCode,
                'result'  => $result,
            ]);
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
            ]);
        }
    }

    public function sendEmail($recipientType, $recipientId, $email, $subject, $htmlMessage, $attachmentPath = null)
    {
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
}

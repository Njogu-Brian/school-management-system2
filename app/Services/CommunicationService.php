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

    public function sendSMS($recipientType, $recipientId, $phone, $message, $title = null)
    {
        try {
            $result = $this->smsService->sendSMS($phone, $message);

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'title'          => $title ?? 'SMS Notification',
                'message'        => $message,
                'type'           => 'sms',
                'status'         => $result['status'] ?? 'sent',
                'response'       => is_array($result) ? $result : ['response' => (string) $result],
                'scope'          => 'sms',
                'sent_at'        => now(),
                'provider_id'    => data_get($result, 'id') ?? data_get($result, 'message_id') ?? data_get($result, 'MessageID'),
                'provider_status' => strtolower(data_get($result, 'status', 'sent')),
            ]);

            Log::info("SMS attempt finished", [
                'phone'   => $phone,
                'status'  => $result['status'] ?? 'sent',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error("SMS sending threw exception: " . $e->getMessage());

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

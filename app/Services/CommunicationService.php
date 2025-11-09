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

    public function sendSMS($recipientType, $recipientId, $phone, $message)
    {
        try {
            $result = $this->smsService->sendSMS($phone, $message);

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => $result['status'] ?? 'unknown',
                'response'       => is_array($result) ? json_encode($result) : (string) $result,
            ]);

            Log::info("SMS attempt finished", [
                'phone'   => $phone,
                'status'  => $result['status'] ?? 'unknown',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error("SMS sending threw exception: " . $e->getMessage());

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => 'failed',
                'response'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send an email and capture audit logs.
     */
    public function sendEmail(
        string $recipientType,
        ?int $recipientId,
        string $email,
        string $subject,
        string $htmlMessage,
        ?string $attachmentPath = null
    ): void
    {
        try {
            Mail::to($email)->send(new GenericMail($subject, $htmlMessage, $attachmentPath));

            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'message'        => $htmlMessage,
                'status'         => 'success',
                'response'       => 'Sent',
                'title'          => $subject,
            ]);
        } catch (\Throwable $e) {
            CommunicationLog::create([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'message'        => $htmlMessage,
                'status'         => 'failed',
                'response'       => $e->getMessage(),
                'title'          => $subject,
            ]);
        }
    }
}

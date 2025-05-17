<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Services\EmailService;
use App\Services\SMSService;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Log, Mail;

class CommunicationService
{
    protected $emailService;
    protected $smsService;

    public function __construct(EmailService $emailService, SMSService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    public function sendSMS($recipientType, $recipientId, $phone, $message)
    {
        $result = $this->smsService->sendSMS($phone, $message);

        CommunicationLog::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'contact' => $phone,
            'channel' => 'sms',
            'message' => $message,
            'status' => $result['status'],
            'response' => $result['message'],
        ]);
    }

    public function sendEmail($recipientType, $recipientId, $email, $subject, $htmlMessage)
{
    try {
        Mail::to($email)->send(new GenericMail($subject, $htmlMessage));

        CommunicationLog::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'contact' => $email,
            'channel' => 'email',
            'message' => $htmlMessage,
            'status' => 'success',
            'response' => 'Sent',
        ]);
    } catch (\Exception $e) {
        CommunicationLog::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'contact' => $email,
            'channel' => 'email',
            'message' => $htmlMessage,
            'status' => 'failed',
            'response' => $e->getMessage(),
        ]);
    }
}

}

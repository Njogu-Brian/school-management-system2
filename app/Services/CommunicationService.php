<?php

namespace App\Services;

use App\Models\CommunicationLog;
use Illuminate\Support\Arr;
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

    public function sendSMS(
        string $recipientType,
        ?int $recipientId,
        string $phone,
        string $message,
        array $meta = []
    ): mixed
    {
        $meta = Arr::except($meta, [
            'recipient_type', 'recipient_id', 'contact', 'channel', 'message', 'status', 'response',
        ]);

        try {
            $result = $this->smsService->sendSMS($phone, $message);

            $payload = [
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => $result['status'] ?? 'unknown',
                'response'       => is_array($result) ? json_encode($result) : (string) $result,
                'sent_at'        => now(),
            ];

            if ($providerId = data_get($result, 'id') ?? data_get($result, 'message_id') ?? data_get($result, 'MessageID')) {
                $payload['provider_id'] = $providerId;
            }
            if ($providerStatus = data_get($result, 'status')) {
                $payload['provider_status'] = strtolower($providerStatus);
            }

            CommunicationLog::create(array_merge($payload, $meta));

            Log::info("SMS attempt finished", [
                'phone'   => $phone,
                'status'  => $result['status'] ?? 'unknown',
                'result'  => $result,
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error("SMS sending threw exception: " . $e->getMessage());

            CommunicationLog::create(array_merge([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => 'failed',
                'response'       => $e->getMessage(),
                'sent_at'        => now(),
            ], $meta));

            return null;
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
        ?string $attachmentPath = null,
        array $meta = []
    ): void
    {
        $meta = Arr::except($meta, [
            'recipient_type', 'recipient_id', 'contact', 'channel', 'message', 'status', 'response',
        ]);

        try {
            Mail::to($email)->send(new GenericMail($subject, $htmlMessage, $attachmentPath));

            CommunicationLog::create(array_merge([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'message'        => $htmlMessage,
                'status'         => 'success',
                'response'       => 'Sent',
                'title'          => $subject,
                'sent_at'        => now(),
            ], $meta));
        } catch (\Throwable $e) {
            CommunicationLog::create(array_merge([
                'recipient_type' => $recipientType,
                'recipient_id'   => $recipientId,
                'contact'        => $email,
                'channel'        => 'email',
                'message'        => $htmlMessage,
                'status'         => 'failed',
                'response'       => $e->getMessage(),
                'title'          => $subject,
                'sent_at'        => now(),
            ], $meta));
        }
    }
}

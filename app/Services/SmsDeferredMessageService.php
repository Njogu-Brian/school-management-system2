<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\SmsDeferredMessage;
use Illuminate\Support\Facades\Log;

class SmsDeferredMessageService
{
    public const KIND_ATTENDANCE_ABSENT = 'attendance_absent';

    public const KIND_FEE_REMINDER = 'fee_reminder';

    public const KIND_GENERIC = 'generic';

    /**
     * Queue an SMS until credits resume (or until expires_at for attendance).
     */
    public function defer(
        string $kind,
        string $contact,
        string $message,
        ?string $senderId = null,
        ?string $title = null,
        ?string $scope = null,
        ?string $recipientType = null,
        ?int $recipientId = null,
        ?\DateTimeInterface $expiresAt = null,
        array $meta = [],
    ): SmsDeferredMessage {
        return SmsDeferredMessage::create([
            'kind' => $kind,
            'contact' => $contact,
            'message' => $message,
            'sender_id' => $senderId,
            'title' => $title,
            'scope' => $scope,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'meta' => $meta,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Attendance absence SMS: expire at 16:00 same day (app timezone).
     */
    public function attendanceExpiryToday(): \Carbon\Carbon
    {
        return now()->copy()->setTime(16, 0, 0);
    }

    public function purgeExpired(): int
    {
        $count = 0;
        SmsDeferredMessage::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $row->update([
                        'status' => 'expired',
                        'error_message' => 'Expired before SMS credits were available',
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Attempt to send pending deferred messages while credits last.
     *
     * @return array{sent:int, skipped:int, paused:bool}
     */
    public function flushPending(?int $limit = 200): array
    {
        $sms = app(SMSService::class);
        $sent = 0;
        $skipped = 0;
        $paused = false;

        $this->purgeExpired();

        if (CommunicationPauseService::isPaused() || ! $sms->hasSufficientCredits(1)) {
            return ['sent' => 0, 'skipped' => 0, 'paused' => true];
        }

        SmsDeferredMessage::query()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('id')
            ->limit($limit ?? 200)
            ->get()
            ->each(function (SmsDeferredMessage $row) use ($sms, &$sent, &$skipped, &$paused) {
                if ($paused || CommunicationPauseService::isPaused()) {
                    $paused = true;

                    return false;
                }

                $required = SmsSegmentCalculator::segmentsForMessage($row->message);
                if (! $sms->hasSufficientCredits($required)) {
                    CommunicationPauseService::pauseDueToInsufficientCredits(
                        (float) ($sms->checkBalance() ?? $sms->getLastKnownBalance() ?? 0),
                        'SmsDeferredMessageService::flushPending'
                    );
                    $paused = true;

                    return false;
                }

                try {
                    $result = $sms->sendSMS($row->contact, $row->message, $row->sender_id);
                    if (is_array($result) && ($result['error_code'] ?? '') === 'INSUFFICIENT_CREDITS') {
                        CommunicationPauseService::pauseDueToInsufficientCredits(
                            (float) ($result['balance'] ?? 0),
                            'SmsDeferredMessageService::flushPending'
                        );
                        $paused = true;

                        return false;
                    }

                    $ok = is_array($result)
                        && (
                            in_array(strtolower((string) ($result['status'] ?? '')), ['success', 'sent'], true)
                            || (string) ($result['statusCode'] ?? '') === '200'
                        );

                    if (! $ok) {
                        $row->update([
                            'error_message' => is_array($result) ? ($result['message'] ?? 'Send failed') : 'Send failed',
                        ]);
                        $skipped++;

                        return true;
                    }

                    $row->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'error_message' => null,
                    ]);

                    CommunicationLog::create([
                        'recipient_type' => $row->recipient_type ?? 'parent',
                        'recipient_id' => $row->recipient_id,
                        'contact' => $row->contact,
                        'channel' => 'sms',
                        'message' => $row->message,
                        'status' => 'sent',
                        'response' => $result,
                        'title' => $row->title,
                        'type' => 'sms',
                        'scope' => $row->scope,
                        'sent_at' => now(),
                    ]);
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Deferred SMS flush failed', [
                        'id' => $row->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }

                return true;
            });

        return compact('sent', 'skipped', 'paused');
    }
}

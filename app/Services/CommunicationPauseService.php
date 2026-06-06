<?php

namespace App\Services;

use App\Jobs\BulkSendSMS;
use App\Models\CommunicationLog;
use App\Models\FeeReminder;
use App\Models\ScheduledCommunication;
use App\Models\ScheduledFeeCommunication;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommunicationPauseService
{
    public const SETTING_KEY = 'communications_paused';

    public const META_KEY = 'communications_paused_meta';

    public static function isPaused(): bool
    {
        return setting_bool(self::SETTING_KEY, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getMeta(): ?array
    {
        $raw = setting(self::META_KEY);
        if (!$raw) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected static function saveMeta(array $meta): void
    {
        setting_set(self::META_KEY, json_encode($meta));
    }

    public static function pauseDueToInsufficientCredits(float $balance = 0, ?string $trigger = null): void
    {
        $meta = self::getMeta() ?? [];
        $alreadyPaused = self::isPaused();

        if (!$alreadyPaused) {
            setting_set_bool(self::SETTING_KEY, true);

            $feePaused = ScheduledFeeCommunication::query()
                ->whereIn('status', ['pending', 'active'])
                ->update(['status' => 'paused']);

            $scheduledPaused = ScheduledCommunication::query()
                ->where('status', 'pending')
                ->update(['status' => 'paused']);

            $remindersPaused = FeeReminder::query()
                ->where('status', 'pending')
                ->update(['status' => 'paused']);

            Log::warning('Communications paused: insufficient SMS credits', [
                'balance' => $balance,
                'trigger' => $trigger,
                'scheduled_fee_paused' => $feePaused,
                'scheduled_comm_paused' => $scheduledPaused,
                'fee_reminders_paused' => $remindersPaused,
            ]);

            try {
                app(SystemAlertService::class)->raiseSmsCreditsAlert($balance, $trigger);
            } catch (\Throwable $e) {
                Log::warning('Failed to raise SMS credits system alert', ['error' => $e->getMessage()]);
            }
        }

        self::saveMeta(array_merge($meta, [
            'reason' => 'insufficient_sms_credits',
            'balance' => $balance,
            'trigger' => $trigger,
            'paused_at' => $meta['paused_at'] ?? now()->toIso8601String(),
            'paused_by' => $meta['paused_by'] ?? auth()->id(),
            'paused_bulk_sms' => $meta['paused_bulk_sms'] ?? [],
        ]));
    }

    /**
     * Store a bulk SMS job payload so it can be re-dispatched on resume (not lost from the queue).
     */
    public static function registerPausedBulkSmsJob(
        string $trackingId,
        array $recipients,
        string $message,
        string $title,
        string $target,
        ?string $senderId = null,
        ?int $userId = null
    ): void {
        $meta = self::getMeta() ?? [];
        $jobs = $meta['paused_bulk_sms'] ?? [];
        $jobs[$trackingId] = [
            'tracking_id' => $trackingId,
            'recipients' => $recipients,
            'message' => $message,
            'title' => $title,
            'target' => $target,
            'sender_id' => $senderId,
            'user_id' => $userId,
            'registered_at' => now()->toIso8601String(),
        ];
        $meta['paused_bulk_sms'] = $jobs;

        if (!self::isPaused()) {
            setting_set_bool(self::SETTING_KEY, true);
        }

        self::saveMeta(array_merge($meta, [
            'reason' => $meta['reason'] ?? 'insufficient_sms_credits',
            'paused_at' => $meta['paused_at'] ?? now()->toIso8601String(),
        ]));
    }

    /**
     * Mark in-progress bulk send progress caches as paused.
     */
    public static function pauseBulkProgress(string $trackingId, string $channel, array $extra = []): void
    {
        $key = match ($channel) {
            'sms' => "bulk_sms_progress:{$trackingId}",
            'whatsapp' => "bulk_whatsapp_progress:{$trackingId}",
            'email' => "bulk_email_progress:{$trackingId}",
            default => null,
        };
        if (!$key) {
            return;
        }

        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, array_merge([
            'status' => 'paused',
            'paused_at' => now()->toIso8601String(),
            'pause_reason' => 'insufficient_sms_credits',
        ], $extra)), now()->addHours(48));
    }

    /**
     * @return array{scheduled_fee: int, scheduled: int, fee_reminders: int, sms_resent: int, bulk_jobs_redispatched: int}
     */
    public static function resume(?int $userId = null): array
    {
        $sms = app(SMSService::class);
        $balance = $sms->checkBalance(true);

        if ($balance !== null && $balance < 1) {
            throw new \RuntimeException(
                'Cannot resume: SMS balance is still ' . $balance . '. Top up credits first, then try again.'
            );
        }

        Cache::forget('sms_balance');

        $meta = self::getMeta() ?? [];

        setting_set_bool(self::SETTING_KEY, false);

        $feeCount = 0;
        ScheduledFeeCommunication::query()
            ->where('status', 'paused')
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$feeCount) {
                foreach ($items as $item) {
                    $item->update([
                        'status' => $item->isRecurring() ? 'active' : 'pending',
                    ]);
                    $feeCount++;
                }
            });

        $scheduledCount = ScheduledCommunication::query()
            ->where('status', 'paused')
            ->update(['status' => 'pending']);

        $reminderCount = FeeReminder::query()
            ->where('status', 'paused')
            ->update(['status' => 'pending']);

        $smsResent = self::resendPausedSmsLogs();
        $bulkRedispatched = self::redispatchPausedBulkSmsJobs($meta['paused_bulk_sms'] ?? []);

        self::saveMeta([]);

        Log::info('Communications resumed after pause', [
            'user_id' => $userId ?? auth()->id(),
            'scheduled_fee' => $feeCount,
            'scheduled' => $scheduledCount,
            'fee_reminders' => $reminderCount,
            'sms_resent' => $smsResent,
            'bulk_jobs_redispatched' => $bulkRedispatched,
            'balance' => $balance,
        ]);

        return [
            'scheduled_fee' => $feeCount,
            'scheduled' => $scheduledCount,
            'fee_reminders' => $reminderCount,
            'sms_resent' => $smsResent,
            'bulk_jobs_redispatched' => $bulkRedispatched,
        ];
    }

    /**
     * Retry individual SMS rows paused/failed due to insufficient credits (including the triggering message).
     */
    public static function resendPausedSmsLogs(): int
    {
        $sms = app(SMSService::class);
        $resent = 0;

        CommunicationLog::query()
            ->where('channel', 'sms')
            ->where('error_code', 'INSUFFICIENT_CREDITS')
            ->whereIn('status', ['failed', 'paused'])
            ->orderBy('id')
            ->chunkById(50, function ($logs) use ($sms, &$resent) {
                foreach ($logs as $log) {
                    if (self::isPaused()) {
                        return false;
                    }

                    try {
                        $result = $sms->sendSMS($log->contact, $log->message);

                        if (is_array($result) && ($result['error_code'] ?? '') === 'INSUFFICIENT_CREDITS') {
                            self::pauseDueToInsufficientCredits(
                                (float) ($result['balance'] ?? 0),
                                'CommunicationPauseService::resendPausedSmsLogs'
                            );

                            return false;
                        }

                        $providerStatus = strtolower((string) data_get($result, 'status', 'sent'));
                        $isSuccess = in_array($providerStatus, ['success', 'sent'], true)
                            || (string) data_get($result, 'statusCode') === '200';

                        if (!$isSuccess) {
                            continue;
                        }

                        $log->update([
                            'status' => 'sent',
                            'error_code' => null,
                            'response' => $result,
                            'sent_at' => now(),
                            'provider_id' => data_get($result, 'transactionId')
                                ?? data_get($result, 'id')
                                ?? data_get($result, 'message_id'),
                            'provider_status' => $providerStatus,
                        ]);
                        $resent++;
                    } catch (\Throwable $e) {
                        Log::warning('Resume SMS retry failed for log #' . $log->id, [
                            'error' => $e->getMessage(),
                        ]);

                        if ($e instanceof \App\Exceptions\InsufficientSmsCreditsException) {
                            return false;
                        }
                    }
                }
            });

        return $resent;
    }

    /**
     * @param  array<string, array<string, mixed>>  $pausedBulkJobs
     */
    protected static function redispatchPausedBulkSmsJobs(array $pausedBulkJobs): int
    {
        $count = 0;

        foreach ($pausedBulkJobs as $payload) {
            if (empty($payload['tracking_id']) || empty($payload['recipients'])) {
                continue;
            }

            $trackingId = (string) $payload['tracking_id'];
            $progress = Cache::get("bulk_sms_progress:{$trackingId}", []);
            Cache::put("bulk_sms_progress:{$trackingId}", array_merge($progress, [
                'status' => 'queued',
                'resumed_at' => now()->toIso8601String(),
            ]), now()->addHours(48));

            BulkSendSMS::dispatch(
                $trackingId,
                $payload['recipients'],
                (string) ($payload['message'] ?? ''),
                (string) ($payload['title'] ?? 'SMS'),
                (string) ($payload['target'] ?? 'custom'),
                $payload['sender_id'] ?? null,
                $payload['user_id'] ?? null
            );

            $count++;
        }

        return $count;
    }

    public static function countPausedSmsLogs(): int
    {
        return CommunicationLog::query()
            ->where('channel', 'sms')
            ->where('error_code', 'INSUFFICIENT_CREDITS')
            ->whereIn('status', ['failed', 'paused'])
            ->count();
    }

    /**
     * Whether the UI should offer "Resume" (global pause or orphaned paused work).
     */
    public static function hasResumableWork(): bool
    {
        if (self::isPaused()) {
            return true;
        }

        if (self::countPausedSmsLogs() > 0) {
            return true;
        }

        $meta = self::getMeta() ?? [];
        if (!empty($meta['paused_bulk_sms'])) {
            return true;
        }

        if (ScheduledFeeCommunication::query()->where('status', 'paused')->exists()) {
            return true;
        }

        if (ScheduledCommunication::query()->where('status', 'paused')->exists()) {
            return true;
        }

        if (FeeReminder::query()->where('status', 'paused')->exists()) {
            return true;
        }

        return false;
    }

    public static function assertNotPaused(): void
    {
        if (self::isPaused()) {
            throw new \RuntimeException(
                'Outbound communications are paused due to insufficient SMS credits. Top up credits and resume from Communication → Queues.'
            );
        }
    }
}

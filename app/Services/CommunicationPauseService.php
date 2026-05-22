<?php

namespace App\Services;

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

    public static function pauseDueToInsufficientCredits(float $balance = 0, ?string $trigger = null): void
    {
        if (self::isPaused()) {
            return;
        }

        setting_set_bool(self::SETTING_KEY, true);
        setting_set(self::META_KEY, json_encode([
            'reason' => 'insufficient_sms_credits',
            'balance' => $balance,
            'trigger' => $trigger,
            'paused_at' => now()->toIso8601String(),
            'paused_by' => auth()->id(),
        ]));

        $feePaused = ScheduledFeeCommunication::query()
            ->whereIn('status', ['pending', 'active'])
            ->update(['status' => 'paused']);

        $scheduledPaused = ScheduledCommunication::query()
            ->where('status', 'pending')
            ->update(['status' => 'paused']);

        $remindersPaused = FeeReminder::query()
            ->where('status', 'pending')
            ->update(['status' => 'paused']);

        self::markActiveBulkJobsPaused();

        Log::warning('Communications paused: insufficient SMS credits', [
            'balance' => $balance,
            'trigger' => $trigger,
            'scheduled_fee_paused' => $feePaused,
            'scheduled_comm_paused' => $scheduledPaused,
            'fee_reminders_paused' => $remindersPaused,
        ]);
    }

    /**
     * Mark in-progress bulk send progress caches as paused (does not delete queue jobs).
     */
    protected static function markActiveBulkJobsPaused(): void
    {
        foreach (['bulk_sms_progress:', 'bulk_whatsapp_progress:', 'bulk_email_progress:'] as $prefix) {
            // Cannot list all cache keys on file driver; bulk jobs self-check isPaused() each iteration.
        }
    }

    /**
     * @return array{scheduled_fee: int, scheduled: int, fee_reminders: int}
     */
    public static function resume(?int $userId = null): array
    {
        setting_set_bool(self::SETTING_KEY, false);
        setting_set(self::META_KEY, '');

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

        Log::info('Communications resumed after pause', [
            'user_id' => $userId ?? auth()->id(),
            'scheduled_fee' => $feeCount,
            'scheduled' => $scheduledCount,
            'fee_reminders' => $reminderCount,
        ]);

        return [
            'scheduled_fee' => $feeCount,
            'scheduled' => $scheduledCount,
            'fee_reminders' => $reminderCount,
        ];
    }

    public static function assertNotPaused(): void
    {
        if (self::isPaused()) {
            throw new \RuntimeException(
                'Outbound communications are paused due to insufficient SMS credits. Top up credits and resume from Communication → Queues.'
            );
        }
    }

    /**
     * Update bulk job progress cache to paused state when possible.
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
}

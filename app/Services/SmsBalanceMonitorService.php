<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsBalanceMonitorService
{
    public function lowBalanceThreshold(): float
    {
        return max(1, (float) setting('sms_low_balance_threshold', 50));
    }

    public function checkAndAlert(): ?float
    {
        $sms = app(SMSService::class);
        $balance = $sms->checkBalance(true);

        if ($balance === null) {
            Log::warning('SMS balance monitor: unable to read balance from provider');

            return null;
        }

        $threshold = $this->lowBalanceThreshold();

        if ($balance <= 0) {
            return $balance;
        }

        if ($balance <= $threshold) {
            app(SystemAlertService::class)->raiseSmsLowBalanceAlert($balance, $threshold);
        }

        return $balance;
    }

    /**
     * @return array<string, mixed>
     */
    public function statusSnapshot(bool $forceRefresh = false): array
    {
        $sms = app(SMSService::class);
        $balance = $sms->checkBalance($forceRefresh);
        $threshold = $this->lowBalanceThreshold();
        $paused = CommunicationPauseService::isPaused();

        return [
            'balance' => $balance,
            'threshold' => $threshold,
            'is_low' => $balance !== null && $balance > 0 && $balance <= $threshold,
            'is_empty' => $balance !== null && $balance <= 0,
            'is_paused' => $paused,
            'paused_meta' => CommunicationPauseService::getMeta(),
            'checked_at' => now()->toIso8601String(),
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class WhatsAppBulkRateLimiter
{
    public static function delaySeconds(): int
    {
        $fromSetting = setting('whatsapp_bulk_delay_seconds');

        return max(1, (int) ($fromSetting ?: config('services.whatsapp.bulk_delay_seconds', 10)));
    }

    /**
     * Enforce a minimum gap between WhatsApp API calls (shared across requests via cache).
     */
    public static function waitBeforeSend(string $scope = 'global'): void
    {
        $delay = self::delaySeconds();
        $key = 'whatsapp_last_sent_at:' . $scope;
        $last = Cache::get($key);

        if ($last !== null) {
            $elapsed = microtime(true) - (float) $last;
            if ($elapsed < $delay) {
                sleep((int) ceil($delay - $elapsed));
            }
        }

        Cache::put($key, microtime(true), now()->addHours(4));
    }
}

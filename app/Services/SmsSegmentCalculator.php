<?php

namespace App\Services;

/**
 * Pure local SMS segment costing (no provider API calls).
 *
 * GSM-7: 160 chars single / 153 per part when concatenated
 * UCS-2: 70 chars single / 67 per part when concatenated
 */
class SmsSegmentCalculator
{
    /** @var string GSM 7-bit default alphabet + common extension markers we treat as GSM when alone */
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM_EXTENDED = "^{}\\[~]|€";

    public static function isGsm7(string $message): bool
    {
        $len = mb_strlen($message, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($message, $i, 1, 'UTF-8');
            if (mb_strpos(self::GSM_BASIC, $ch, 0, 'UTF-8') !== false) {
                continue;
            }
            if (mb_strpos(self::GSM_EXTENDED, $ch, 0, 'UTF-8') !== false) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Effective character units for segment math (GSM extended chars cost 2).
     */
    public static function characterUnits(string $message): int
    {
        if (! self::isGsm7($message)) {
            return mb_strlen($message, 'UTF-8');
        }

        $units = 0;
        $len = mb_strlen($message, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($message, $i, 1, 'UTF-8');
            $units += mb_strpos(self::GSM_EXTENDED, $ch, 0, 'UTF-8') !== false ? 2 : 1;
        }

        return $units;
    }

    public static function encoding(string $message): string
    {
        return self::isGsm7($message) ? 'gsm7' : 'ucs2';
    }

    public static function segmentsForMessage(string $message): int
    {
        $units = self::characterUnits($message);
        if ($units <= 0) {
            return 1;
        }

        if (self::isGsm7($message)) {
            if ($units <= 160) {
                return 1;
            }

            return (int) ceil($units / 153);
        }

        if ($units <= 70) {
            return 1;
        }

        return (int) ceil($units / 67);
    }

    public static function creditsForBatch(string $message, int $recipientCount): int
    {
        $recipients = max(0, $recipientCount);
        if ($recipients === 0) {
            return 0;
        }

        return self::segmentsForMessage($message) * $recipients;
    }

    /**
     * @return array{encoding:string, characters:int, segments_per_message:int, recipients:int, credits_required:int}
     */
    public static function estimate(string $message, int $recipientCount = 1): array
    {
        $segments = self::segmentsForMessage($message);
        $recipients = max(0, $recipientCount);

        return [
            'encoding' => self::encoding($message),
            'characters' => self::characterUnits($message),
            'segments_per_message' => $segments,
            'recipients' => $recipients,
            'credits_required' => $segments * $recipients,
        ];
    }
}

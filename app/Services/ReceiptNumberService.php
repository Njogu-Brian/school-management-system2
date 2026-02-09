<?php

namespace App\Services;

use App\Models\Payment;

/**
 * Central service for systematic receipt numbers (all new payments).
 *
 * Format: RCPT/YYYY-NNNN (e.g. RCPT/2026-0836). Numeric after "RCPT/".
 * Siblings: first child gets the base, next get base-01, base-02, etc.
 *
 * Legacy formats (unchanged for existing records):
 * - RCPT/2026-0836-S48: from DocumentNumberService (yearly sequence) plus old
 *   sibling suffix "-S{student_id}". New siblings use -01, -02 instead.
 * - REC-M4AL2G7UXX: was REC- + 10 random chars (C2B/bank flows). All new use RCPT/YYYY-NNNN.
 */
class ReceiptNumberService
{
    /**
     * Generate the next systematic receipt number for a new payment.
     * Use this for all new payments (C2B, manual, statement parse).
     */
    public static function generateForPayment(): string
    {
        $maxAttempts = 10;
        $attempt = 0;
        do {
            $receiptNumber = DocumentNumberService::generateReceipt();
            $exists = Payment::where('receipt_number', $receiptNumber)->exists();
            if (!$exists) {
                return $receiptNumber;
            }
            $attempt++;
            if ($attempt < $maxAttempts) {
                usleep(10000);
            }
        } while ($attempt < $maxAttempts);

        return $receiptNumber . '-' . time();
    }

    /**
     * Receipt number for one sibling in a shared group.
     * First child (index 0) gets the base; next get base-01, base-02, etc.
     *
     * @param string $baseReceiptNumber Shared base e.g. RCPT/2026-0836
     * @param int $zeroBasedIndex 0 = first child (gets base), 1 = second (-01), 2 = third (-02)…
     */
    public static function receiptNumberForSibling(string $baseReceiptNumber, int $zeroBasedIndex): string
    {
        if ($zeroBasedIndex === 0) {
            return $baseReceiptNumber;
        }
        return $baseReceiptNumber . '-' . str_pad((string) $zeroBasedIndex, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Next available receipt number for a sibling when adding to an existing shared group.
     * Queries existing payments with this shared_receipt_number (or receipt_number = base)
     * and returns base for first, base-01 for second, base-02 for third, etc.
     */
    public static function nextReceiptNumberForSibling(string $sharedBaseReceiptNumber): string
    {
        $existing = Payment::where('reversed', false)
            ->where(function ($q) use ($sharedBaseReceiptNumber) {
                $q->where('shared_receipt_number', $sharedBaseReceiptNumber)
                    ->orWhere('receipt_number', $sharedBaseReceiptNumber)
                    ->orWhere('receipt_number', 'LIKE', $sharedBaseReceiptNumber . '-%');
            })
            ->pluck('receipt_number');

        $maxIndex = -1;
        foreach ($existing as $rn) {
            if ($rn === $sharedBaseReceiptNumber) {
                $maxIndex = max($maxIndex, 0);
                continue;
            }
            if (str_starts_with($rn, $sharedBaseReceiptNumber . '-')) {
                $suffix = substr($rn, strlen($sharedBaseReceiptNumber) + 1);
                if (preg_match('/^\d+$/', $suffix)) {
                    $maxIndex = max($maxIndex, (int) $suffix);
                }
            }
        }

        $nextIndex = $maxIndex + 1;
        return self::receiptNumberForSibling($sharedBaseReceiptNumber, $nextIndex);
    }
}

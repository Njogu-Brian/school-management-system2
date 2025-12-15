<?php

namespace App\Services;

use App\Models\DocumentCounter;
use Illuminate\Support\Str;

class DocumentNumberService
{
    /**
     * Generate the next document number for a given type with configurable sequence.
     *
     * @param string $type e.g. 'invoice', 'receipt', 'credit_note', 'debit_note'
     * @param string $defaultPrefix e.g. 'INV', 'RCPT' (used if counter doesn't exist)
     * @param int $defaultPadLength Default padding length (used if counter doesn't exist)
     * @return string
     */
    public static function generate(string $type, string $defaultPrefix = '', int $defaultPadLength = 4): string
    {
        $counter = DocumentCounter::firstOrCreate(
            ['type' => $type],
            [
                'prefix' => $defaultPrefix,
                'suffix' => '',
                'padding_length' => $defaultPadLength,
                'next_number' => 1,
                'reset_period' => 'never',
            ]
        );

        // Check if reset needed
        self::checkAndResetCounter($counter);

        $number = $counter->next_number;
        
        // Format number with padding
        $paddedNumber = str_pad((string)$number, $counter->padding_length ?? $defaultPadLength, '0', STR_PAD_LEFT);
        
        // Build formatted number
        $formatted = $counter->prefix . $paddedNumber . $counter->suffix;
        if ($counter->prefix && !Str::endsWith($counter->prefix, '-')) {
            $formatted = $counter->prefix . '-' . $paddedNumber . $counter->suffix;
        }

        // Increment the counter for next use
        $counter->increment('next_number');

        return $formatted;
    }
    
    /**
     * Check if counter needs reset based on reset_period
     */
    private static function checkAndResetCounter(DocumentCounter $counter): void
    {
        $now = now();
        $needsReset = false;
        
        switch ($counter->reset_period) {
            case 'yearly':
                if ($counter->last_reset_year !== $now->year) {
                    $needsReset = true;
                    $counter->last_reset_year = $now->year;
                }
                break;
                
            case 'monthly':
                if ($counter->last_reset_year !== $now->year || $counter->last_reset_month !== $now->month) {
                    $needsReset = true;
                    $counter->last_reset_year = $now->year;
                    $counter->last_reset_month = $now->month;
                }
                break;
        }
        
        if ($needsReset) {
            $counter->next_number = 1;
            $counter->save();
        }
    }
    
    /**
     * Generate receipt number (separate sequence)
     */
    public static function generateReceipt(): string
    {
        return self::generate('receipt', 'RCPT', 6);
    }
    
    /**
     * Generate invoice number
     */
    public static function generateInvoice(): string
    {
        return self::generate('invoice', 'INV', 5);
    }
    
    /**
     * Generate credit note number
     */
    public static function generateCreditNote(): string
    {
        return self::generate('credit_note', 'CN', 5);
    }
    
    /**
     * Generate debit note number
     */
    public static function generateDebitNote(): string
    {
        return self::generate('debit_note', 'DN', 5);
    }
}

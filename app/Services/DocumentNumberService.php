<?php

namespace App\Services;

use App\Models\DocumentCounter;
use Illuminate\Support\Str;

class DocumentNumberService
{
    /**
     * Generate the next document number for a given type.
     *
     * @param string $type e.g. 'invoice', 'receipt'
     * @param string $prefix e.g. 'INV', 'RCPT'
     * @param int $padLength
     * @return string
     */
    public static function generate(string $type, string $prefix = '', int $padLength = 4): string
    {
        $counter = DocumentCounter::firstOrCreate(
            ['type' => $type],
            ['next_number' => 1]
        );

        $number = $counter->next_number;
        $formatted = $prefix . '-' . str_pad($number, $padLength, '0', STR_PAD_LEFT);

        // Increment the counter for next use
        $counter->increment('next_number');

        return $formatted;
    }
}

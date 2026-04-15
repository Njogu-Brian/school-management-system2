<?php

namespace App\Support;

class NameCase
{
    /**
     * Convert a name-like string into "sentence case" (title-cased words).
     *
     * Examples:
     * - "jOhN   DOE" => "John Doe"
     * - "MARY-ANN o'connor" => "Mary-Ann O'Connor"
     */
    public static function sentence(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($v === '') {
            return '';
        }

        // Lower everything first (multibyte-safe), then title-case words.
        $v = mb_strtolower($v, 'UTF-8');
        $v = mb_convert_case($v, MB_CASE_TITLE, 'UTF-8');

        // Preserve common name separators by re-title-casing after them.
        // e.g. "O'Connor", "Mary-Ann"
        $v = preg_replace_callback("/([\\p{L}])(['-])([\\p{L}])/u", function ($m) {
            return $m[1] . $m[2] . mb_strtoupper($m[3], 'UTF-8');
        }, $v) ?? $v;

        return $v;
    }
}


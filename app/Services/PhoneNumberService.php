<?php

namespace App\Services;

class PhoneNumberService
{
    /**
     * Local (national significant) digit lengths for common calling codes.
     * Fallback for unknown codes is a permissive range.
     *
     * NOTE: This is intentionally conservative; if you need full international validation,
     * integrate libphonenumber later.
     *
     * @return array<string, array{min:int,max:int}>
     */
    protected function localLengthRules(): array
    {
        return [
            '+254' => ['min' => 9, 'max' => 9],   // Kenya
            '+256' => ['min' => 9, 'max' => 9],   // Uganda
            '+255' => ['min' => 9, 'max' => 9],   // Tanzania
            '+250' => ['min' => 9, 'max' => 9],   // Rwanda
            '+257' => ['min' => 8, 'max' => 8],   // Burundi
            '+211' => ['min' => 9, 'max' => 9],   // South Sudan (commonly 9)
            '+234' => ['min' => 10, 'max' => 10], // Nigeria
            '+233' => ['min' => 9, 'max' => 9],   // Ghana
            '+27'  => ['min' => 9, 'max' => 9],   // South Africa
            '+44'  => ['min' => 9, 'max' => 10],  // UK (varies)
            '+1'   => ['min' => 10, 'max' => 10], // US/CA
        ];
    }

    /**
     * Validate that the provided local digits match the expected length for the calling code.
     *
     * @return array{ok:bool,min:int,max:int,code:string,digits:int}
     */
    public function validateLocalDigitsLength(?string $localDigits, ?string $countryCode): array
    {
        $countryCode = $this->normalizeCountryCode($countryCode);
        $digits = preg_replace('/\D+/', '', (string) $localDigits);
        $digits = ltrim($digits, '0'); // user may type leading 0 locally
        $len = strlen($digits);

        $rule = $this->localLengthRules()[$countryCode] ?? ['min' => 4, 'max' => 15];

        return [
            'ok' => $len >= $rule['min'] && $len <= $rule['max'],
            'min' => $rule['min'],
            'max' => $rule['max'],
            'code' => $countryCode,
            'digits' => $len,
        ];
    }

    public function normalizeCountryCode(?string $code, string $default = '+254'): string
    {
        $code = trim((string) $code);
        if ($code === '') {
            $code = $default;
        }

        // Handle +ke or ke as Kenya
        $lower = strtolower($code);
        if ($lower === '+ke' || $lower === 'ke') {
            return '+254';
        }

        if (!str_starts_with($code, '+')) {
            $code = '+' . ltrim($code, '+');
        }

        // Keep only digits after plus
        $digits = preg_replace('/\D+/', '', $code);
        if ($digits === '') {
            $digits = preg_replace('/\D+/', '', $default) ?: '254';
        }

        return '+' . $digits;
    }

    public function formatWithCountryCode(?string $number, ?string $countryCode = '+254'): ?string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }

        $countryCode = $this->normalizeCountryCode($countryCode);
        $codeDigits = ltrim($countryCode, '+');

        // Strip all non-digits
        $digits = preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return null;
        }

        // Remove leading 00 (international prefix)
        if (str_starts_with($digits, '00')) {
            $digits = ltrim($digits, '0');
        }

        // Remove duplicate country codes if present
        if (str_starts_with($digits, $codeDigits . $codeDigits)) {
            $digits = substr($digits, strlen($codeDigits));
        }

        // Remove leading country code if present
        if (str_starts_with($digits, $codeDigits)) {
            $digits = substr($digits, strlen($codeDigits));
        }

        // Remove leading zeros in local part
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return null;
        }

        return '+' . $codeDigits . $digits;
    }

    public function extractLocalNumber(?string $fullPhone, ?string $countryCode = '+254'): ?string
    {
        $fullPhone = trim((string) $fullPhone);
        if ($fullPhone === '') {
            return null;
        }

        $countryCode = $this->normalizeCountryCode($countryCode);
        $codeDigits = ltrim($countryCode, '+');

        $digits = preg_replace('/\D+/', '', $fullPhone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, $codeDigits)) {
            $digits = substr($digits, strlen($codeDigits));
        }

        return ltrim($digits, '0');
    }
}

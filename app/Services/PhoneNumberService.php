<?php

namespace App\Services;

class PhoneNumberService
{
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

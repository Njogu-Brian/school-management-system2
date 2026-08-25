<?php

namespace App\Services\Finance;

/**
 * Parse payer / payee identity from M-Pesa statement narrations and Daraja payloads.
 *
 * Statement phones are stored in local Kenyan masked form (0700***000): leading 0,
 * visible prefix, three stars, last three digits when the full number is known.
 * Already-masked values (25470****430, 07******914) keep their star pattern after
 * converting the 254 country code to a leading 0.
 */
class MpesaStatementIdentity
{
    /** @var list<string> */
    protected static array $genericNames = [
        'pay bill',
        'pay bill online',
        'business pay bill',
        'small business pay',
        'small business pay bill',
        'utility account',
        'merchant pay utility',
        'loan recovery for',
        'from',
        'to',
        'mpesa',
        'm-pesa',
        'paybill',
        'pesalink',
        'kings edu',
    ];

    public static function normalizeWhitespace(?string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n"], ' ', (string) $text);

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Convert a Kenya MSISDN (full or already masked) to local statement form.
     * Examples: 254708225397 → 0708***397, 25470****430 → 070****430, 07******914 → 07******914.
     */
    public static function toLocalMaskedPhone(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // Daraja production hashes the MSISDN — never invent a phone from hex.
        if (strlen($raw) === 64 && ctype_xdigit($raw)) {
            return null;
        }

        if (preg_match('/(\d+)\*+(\d+)/', $raw, $m)) {
            $prefix = $m[1];
            $stars = str_repeat('*', strlen($m[0]) - strlen($m[1]) - strlen($m[2]));
            $suffix = $m[2];

            if (str_starts_with($prefix, '254')) {
                $prefix = '0' . substr($prefix, 3);
            } elseif (! str_starts_with($prefix, '0') && strlen($prefix) >= 2) {
                $prefix = '0' . $prefix;
            }

            if (strlen($prefix) >= 4 && strlen($suffix) >= 3) {
                return substr($prefix, 0, 4) . '***' . substr($suffix, -3);
            }

            return $prefix . $stars . $suffix;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        $local = self::toLocalTenDigits($digits);
        if ($local === null) {
            return null;
        }

        return substr($local, 0, 4) . '***' . substr($local, -3);
    }

    /**
     * @return array{
     *   phone: ?string,
     *   name: ?string,
     *   account: ?string,
     *   paybill: ?string,
     *   till: ?string
     * }
     */
    public static function parseParty(string $narration): array
    {
        $text = self::normalizeWhitespace($narration);
        $empty = [
            'phone' => null,
            'name' => null,
            'account' => null,
            'paybill' => null,
            'till' => null,
        ];

        if ($text === '') {
            return $empty;
        }

        $account = null;
        if (preg_match('/Acc\.?\s*(.+)$/i', $text, $accMatch)) {
            $account = trim($accMatch[1]);
            if ($account === '') {
                $account = null;
            }
        }

        // Incoming school paybill (Safaricom DETAILED STATEMENT / paybill PDF).
        if (preg_match('/(?:Pay Bill(?:\s+Online)?|Small Business Pay Bill|Business Pay Bill(?:\s+via API)?)\s+(?:from|by)\s+/i', $text)) {
            if (preg_match('/(?:from|by)\s+(\d+\*+\d+|\d{10,12})\s*[-–]\s*(.+?)(?:\s+Acc\.|$)/i', $text, $m)) {
                return [
                    'phone' => self::toLocalMaskedPhone($m[1]),
                    'name' => self::cleanPersonName($m[2]),
                    'account' => $account,
                    'paybill' => null,
                    'till' => null,
                ];
            }

            if (preg_match('/(?:from|by)\s+(\d{4,8})\s*[-–]\s*(.+?)(?:\s+Acc\.|$)/i', $text, $m)) {
                return [
                    'phone' => null,
                    'name' => self::cleanPersonName($m[2]),
                    'account' => $account,
                    'paybill' => null,
                    'till' => $m[1],
                ];
            }

            return $empty;
        }

        // Send money / pochi (outgoing personal statement).
        if (preg_match('/(?:Customer Transfer|Customer Payment to Small Business|Customer Send Money to Micro SME Business)(?:.*?)(?:to\s*)[-–]?\s*(.+)$/i', $text, $m)) {
            [$phone, $name] = self::splitPhoneAndName($m[1]);

            return [
                'phone' => $phone,
                'name' => $name,
                'account' => $account,
                'paybill' => null,
                'till' => null,
            ];
        }

        // Buy goods / merchant.
        if (preg_match('/Merchant Payment(?:\s+Online)?\s+to\s+(\d+)\s*[-–]?\s*(.+)$/i', $text, $m)) {
            return [
                'phone' => null,
                'name' => self::cleanPersonName($m[2]),
                'account' => $account,
                'paybill' => null,
                'till' => $m[1],
            ];
        }

        // Outgoing paybill.
        if (preg_match('/Pay Bill(?:\s+Online)?\s+to\s+(\d+)\s*[-–]\s*(.+)$/i', $text, $m)) {
            $tail = trim($m[2]);
            $recipient = $tail;
            $acc = $account;
            if (preg_match('/^(.+?)\s+Acc\.?\s+(.+)$/i', $tail, $am)) {
                $recipient = trim($am[1]);
                $acc = trim($am[2]);
            }

            return [
                'phone' => null,
                'name' => self::cleanPersonName($recipient),
                'account' => $acc,
                'paybill' => $m[1],
                'till' => null,
            ];
        }

        // Equity POS / card deposit: "CHICKEN PARK-WAT BY:/623614658382/24-08-2026 17:28"
        if (preg_match('/^(.+?)\s+BY\s*:\s*\/\d+/i', $text, $m)) {
            $name = self::cleanPersonName($m[1]);
            if ($name !== null) {
                return [
                    'phone' => self::extractPhoneFromText($text),
                    'name' => $name,
                    'account' => $account,
                    'paybill' => null,
                    'till' => null,
                ];
            }
        }

        return $empty;
    }

    /**
     * First Kenya mobile found in free text (masked or full), in local statement form.
     */
    public static function extractPhoneFromText(?string $text): ?string
    {
        $text = self::normalizeWhitespace($text);
        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{2,5}\*+\d{2,4}|2547\d{8}|25411\d{7}|25410\d{7}|07\d{8}|01[01]\d{7})\b/', $text, $m)) {
            return self::toLocalMaskedPhone($m[1]);
        }

        return null;
    }

    /**
     * Prefer the statement payee; fall back to Daraja FirstName / MiddleName / LastName.
     */
    public static function preferStatementOrDaraja(?string $statementName, ?string $darajaName): ?string
    {
        $statementName = self::cleanPersonName($statementName);
        if ($statementName !== null) {
            return $statementName;
        }

        return self::cleanPersonName($darajaName);
    }

    public static function darajaFullName(?string $first, ?string $middle, ?string $last): ?string
    {
        $parts = array_filter([trim((string) $first), trim((string) $middle), trim((string) $last)], fn ($p) => $p !== '');

        return self::cleanPersonName(implode(' ', $parts));
    }

    /**
     * Prefix/suffix pairs used to match a masked statement phone against stored parent numbers.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function partialPhoneMatchParts(?string $masked): array
    {
        $masked = trim((string) $masked);
        if ($masked === '') {
            return [];
        }

        $parts = preg_split('/\*+|\.{3,}/', $masked) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if (count($parts) !== 2) {
            return [];
        }

        $prefix = preg_replace('/\D+/', '', $parts[0]) ?? '';
        $suffix = preg_replace('/\D+/', '', $parts[1]) ?? '';

        if ($prefix === '' || $suffix === '') {
            return [];
        }

        $variants = [[$prefix, $suffix]];
        if (str_starts_with($prefix, '254')) {
            $variants[] = ['0' . substr($prefix, 3), $suffix];
        } elseif (str_starts_with($prefix, '0')) {
            $variants[] = ['254' . substr($prefix, 1), $suffix];
        }

        return $variants;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected static function splitPhoneAndName(string $tail): array
    {
        $tail = trim($tail);
        $phone = null;
        if (preg_match('/(\d+\*+\d+|\d{10,12})/', $tail, $m)) {
            $phone = self::toLocalMaskedPhone($m[1]);
        }

        $name = trim(preg_replace('/\d+\*+\d+|\d{10,12}/', ' ', $tail) ?? '');
        $name = self::cleanPersonName($name);

        return [$phone, $name];
    }

    protected static function cleanPersonName(?string $name): ?string
    {
        $name = self::normalizeWhitespace($name);
        $name = trim($name, " \t-–.,");
        if ($name === '') {
            return null;
        }

        if (in_array(strtolower($name), self::$genericNames, true)) {
            return null;
        }

        // Title-case words but keep Safaricom's **** redaction intact.
        $words = preg_split('/\s+/', $name) ?: [];
        $titled = [];
        foreach ($words as $word) {
            if ($word === '' || str_contains($word, '*')) {
                $titled[] = $word;
                continue;
            }
            $titled[] = mb_convert_case(mb_strtolower($word), MB_CASE_TITLE, 'UTF-8');
        }

        $cleaned = trim(implode(' ', $titled));

        return $cleaned === '' ? null : $cleaned;
    }

    /**
     * Phone shown on the Transactions list / mobile app.
     * Never returns a Daraja SHA-256 MSISDN hash. C2B hashes fall back to the matching
     * bank-statement mask when $statementPhonesByRef is provided (keyed by trans_id).
     *
     * @param  array<string, string|null>  $statementPhonesByRef
     */
    public static function phoneForTransaction(object $txn, array $statementPhonesByRef = []): ?string
    {
        if ($txn instanceof \App\Models\MpesaC2BTransaction) {
            $formatted = $txn->formatted_phone;
            if ($formatted) {
                return $formatted;
            }

            return self::toLocalMaskedPhone($statementPhonesByRef[$txn->trans_id] ?? null);
        }

        if ($txn instanceof \App\Models\BankStatementTransaction) {
            return self::toLocalMaskedPhone($txn->phone_number);
        }

        $raw = $txn->phone_number ?? $txn->msisdn ?? null;
        $masked = self::toLocalMaskedPhone($raw);
        if ($masked) {
            return $masked;
        }

        $type = $txn->transaction_type ?? null;
        $code = $txn->trans_code ?? $txn->trans_id ?? $txn->reference_number ?? null;
        if (($type === 'c2b' || $type === null) && $code) {
            return self::toLocalMaskedPhone($statementPhonesByRef[$code] ?? null);
        }

        return null;
    }

    protected static function toLocalTenDigits(string $digits): ?string
    {
        if (strlen($digits) === 12 && str_starts_with($digits, '2547')) {
            return '0' . substr($digits, 3);
        }

        // Telkom / 01x: 25410… / 25411… only — not bank account 0120… tokens.
        if (strlen($digits) === 12 && str_starts_with($digits, '2541')) {
            $local = '0' . substr($digits, 3);
            if (preg_match('/^01[01]\d{7}$/', $local)) {
                return $local;
            }

            return null;
        }

        if (preg_match('/^07\d{8}$/', $digits) || preg_match('/^01[01]\d{7}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^7\d{8}$/', $digits)) {
            return '0' . $digits;
        }

        return null;
    }
}

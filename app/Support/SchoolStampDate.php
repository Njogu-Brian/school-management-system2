<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Official school stamp dates for PDFs and digital reports.
 *
 * Report cards use fixed end-of-term stamp dates (not published_at).
 * Finance documents (invoices, fee statements) use the generation date.
 */
final class SchoolStampDate
{
    /**
     * Term 1 → 1 April, Term 2 → 31 July, Term 3 → 30 October
     * of the report card's academic year.
     */
    public static function forReportCardTerm(mixed $term, mixed $academicYear = null): Carbon
    {
        $year = self::resolveYear($academicYear);
        $termNumber = self::resolveTermNumber($term);

        return match ($termNumber) {
            2 => Carbon::create($year, 7, 31, 0, 0, 0, config('app.timezone')),
            3 => Carbon::create($year, 10, 30, 0, 0, 0, config('app.timezone')),
            default => Carbon::create($year, 4, 1, 0, 0, 0, config('app.timezone')),
        };
    }

    /**
     * Invoices and fee statements: stamp with the date the PDF was generated.
     */
    public static function forGenerated(DateTimeInterface|string|null $at = null): Carbon
    {
        $tz = config('app.timezone');

        if ($at instanceof DateTimeInterface) {
            return Carbon::instance(\Illuminate\Support\Carbon::parse($at))->timezone($tz);
        }

        if (is_string($at) && trim($at) !== '') {
            return Carbon::parse($at)->timezone($tz);
        }

        return Carbon::now($tz);
    }

    public static function resolveTermNumber(mixed $term): int
    {
        if (is_int($term) || (is_string($term) && ctype_digit(trim($term)))) {
            $n = (int) $term;

            return max(1, min(3, $n));
        }

        if (is_object($term)) {
            if (isset($term->name)) {
                return self::resolveTermNumber((string) $term->name);
            }
            if (isset($term->term_number)) {
                return self::resolveTermNumber($term->term_number);
            }
        }

        $label = strtoupper(trim((string) $term));
        if (preg_match('/\bTERM\s*([123])\b/', $label, $m) || preg_match('/\bT\s*([123])\b/', $label, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\b([123])\b/', $label, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    public static function resolveYear(mixed $academicYear): int
    {
        if (is_int($academicYear) || (is_string($academicYear) && ctype_digit(trim((string) $academicYear)))) {
            $y = (int) $academicYear;
            // Ignore DB ids (1, 2, 3…) — only accept calendar years.
            if ($y >= 2000 && $y <= 2100) {
                return $y;
            }
        }

        if (is_object($academicYear)) {
            if (isset($academicYear->year) && is_numeric($academicYear->year)) {
                $y = (int) $academicYear->year;
                if ($y >= 2000 && $y <= 2100) {
                    return $y;
                }
            }
            if (isset($academicYear->name) && preg_match('/(20\d{2})/', (string) $academicYear->name, $m)) {
                return (int) $m[1];
            }
        }

        if (is_string($academicYear) && preg_match('/(20\d{2})/', $academicYear, $m)) {
            return (int) $m[1];
        }

        return (int) Carbon::now(config('app.timezone'))->year;
    }
}

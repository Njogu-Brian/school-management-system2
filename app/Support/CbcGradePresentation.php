<?php

namespace App\Support;

use App\Services\Academics\ClassroomGradingService;

/**
 * CBC percentage grades for display (Klickit-style badges).
 * Falls back to standard CBC bands when no classroom scheme applies.
 */
class CbcGradePresentation
{
    /** @return list<array{min: float, max: float, label: string, short: string, tier: string}> */
    public static function standardBands(): array
    {
        return [
            ['min' => 0, 'max' => 29, 'label' => 'Below Expectation', 'short' => 'BE', 'tier' => 'below'],
            ['min' => 30, 'max' => 59, 'label' => 'Approaching Expectation', 'short' => 'AE', 'tier' => 'approaching'],
            ['min' => 60, 'max' => 79, 'label' => 'Meeting Expectation', 'short' => 'ME', 'tier' => 'meeting'],
            ['min' => 80, 'max' => 100, 'label' => 'Exceeding Expectation', 'short' => 'EE', 'tier' => 'exceeding'],
        ];
    }

    /**
     * @return array{label: string, short: string, tier: string, percent: float}|null
     */
    public static function forPercentage(?float $percent, ?int $classroomId = null): ?array
    {
        if ($percent === null || ! is_numeric($percent)) {
            return null;
        }

        $percent = max(0.0, min(100.0, (float) $percent));

        if ($classroomId) {
            $graded = app(ClassroomGradingService::class)->gradeForPercentage($percent, $classroomId);
            $band = $graded['band'] ?? null;
            if ($band) {
                $label = trim((string) ($band->descriptor ?: $band->label ?: ''));
                if ($label !== '') {
                    return [
                        'label' => self::normalizeLabel($label),
                        'short' => self::shortFromLabel($label),
                        'tier' => self::tierFromPercent($percent),
                        'percent' => $percent,
                    ];
                }
            }
        }

        foreach (self::standardBands() as $band) {
            if ($percent >= $band['min'] && $percent <= $band['max']) {
                return [
                    'label' => $band['label'],
                    'short' => $band['short'],
                    'tier' => $band['tier'],
                    'percent' => $percent,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{label: string, short: string, tier: string, percent: float}|null
     */
    public static function forRawScore(?float $score, ?float $maxMarks, ?int $classroomId = null): ?array
    {
        if ($score === null || ! is_numeric($score)) {
            return null;
        }

        $max = ($maxMarks !== null && (float) $maxMarks > 0) ? (float) $maxMarks : 100.0;
        $percent = ((float) $score / $max) * 100.0;

        return self::forPercentage($percent, $classroomId);
    }

    public static function tierFromPercent(float $percent): string
    {
        if ($percent < 30) {
            return 'below';
        }
        if ($percent < 60) {
            return 'approaching';
        }
        if ($percent < 80) {
            return 'meeting';
        }

        return 'exceeding';
    }

    /**
     * Normalize legacy CBC codes (PL1–PL4, E/M/A/B) to EE/ME/AE/BE.
     */
    public static function normalizeShortCode(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $key = strtoupper(trim($code));

        return match ($key) {
            'PL4', 'E', 'EE' => 'EE',
            'PL3', 'M', 'ME' => 'ME',
            'PL2', 'A', 'AE' => 'AE',
            'PL1', 'B', 'BE' => 'BE',
            default => $key,
        };
    }

    public static function nameFromShortCode(?string $code): ?string
    {
        $short = self::normalizeShortCode($code);
        if ($short === null) {
            return null;
        }

        foreach (self::standardBands() as $band) {
            if ($band['short'] === $short) {
                return $band['label'];
            }
        }

        return null;
    }

    public static function normalizeLabel(string $label): string
    {
        $map = [
            'below expectation' => 'Below Expectation',
            'approaching expectation' => 'Approaching Expectation',
            'above expectation' => 'Approaching Expectation',
            'meeting expectation' => 'Meeting Expectation',
            'meets expectation' => 'Meeting Expectation',
            'exceeding expectation' => 'Exceeding Expectation',
            'exceeds expectation' => 'Exceeding Expectation',
        ];

        $key = strtolower(trim($label));

        return $map[$key] ?? $label;
    }

    public static function shortFromLabel(string $label): string
    {
        $fromCode = self::normalizeShortCode($label);
        if ($fromCode !== null && in_array($fromCode, ['EE', 'ME', 'AE', 'BE'], true)) {
            return $fromCode;
        }

        $normalized = self::normalizeLabel($label);
        foreach (self::standardBands() as $band) {
            if (strcasecmp($band['label'], $normalized) === 0) {
                return $band['short'];
            }
        }

        return strtoupper(substr(preg_replace('/\s+/', '', $normalized) ?? 'G', 0, 2));
    }
}

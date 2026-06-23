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
            ['min' => 30, 'max' => 49, 'label' => 'Approaching Expectation', 'short' => 'AE', 'tier' => 'approaching'],
            ['min' => 50, 'max' => 79, 'label' => 'Meeting Expectation', 'short' => 'ME', 'tier' => 'meeting'],
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
        if ($percent < 50) {
            return 'approaching';
        }
        if ($percent < 80) {
            return 'meeting';
        }

        return 'exceeding';
    }

    public static function normalizeLabel(string $label): string
    {
        $map = [
            'below expectation' => 'Below Expectation',
            'approaching expectation' => 'Approaching Expectation',
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
        $normalized = self::normalizeLabel($label);
        foreach (self::standardBands() as $band) {
            if (strcasecmp($band['label'], $normalized) === 0) {
                return $band['short'];
            }
        }

        return strtoupper(substr(preg_replace('/\s+/', '', $normalized) ?? 'G', 0, 2));
    }
}

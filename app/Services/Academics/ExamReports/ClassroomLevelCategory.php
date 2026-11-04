<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;

/**
 * Map classrooms onto school phase bands used in teacher performance reports.
 */
final class ClassroomLevelCategory
{
    public const PRESCHOOL = 'preschool';

    public const LOWER_PRIMARY = 'lower_primary';

    public const UPPER_PRIMARY = 'upper_primary';

    public const JUNIOR_HIGH = 'junior_high';

    public const OTHER = 'other';

    /**
     * @return list<array{key: string, label: string, detail: string}>
     */
    public static function ordered(): array
    {
        return [
            [
                'key' => self::PRESCHOOL,
                'label' => 'Preschool — Foundation',
                'detail' => 'Classes, PP1, PP2',
            ],
            [
                'key' => self::LOWER_PRIMARY,
                'label' => 'Lower Primary',
                'detail' => 'Grade 1–3',
            ],
            [
                'key' => self::UPPER_PRIMARY,
                'label' => 'Upper Primary',
                'detail' => 'Grade 4–6',
            ],
            [
                'key' => self::JUNIOR_HIGH,
                'label' => 'Junior High',
                'detail' => 'Grade 7–9',
            ],
            [
                'key' => self::OTHER,
                'label' => 'Other',
                'detail' => 'Unclassified classes',
            ],
        ];
    }

    public static function labelFor(string $key): string
    {
        foreach (self::ordered() as $row) {
            if ($row['key'] === $key) {
                return $row['label'].' ('.$row['detail'].')';
            }
        }

        return 'Other';
    }

    public static function resolve(?Classroom $classroom): string
    {
        if (! $classroom) {
            return self::OTHER;
        }

        $levelType = strtolower(trim((string) ($classroom->level_type ?? '')));
        if (in_array($levelType, [
            self::PRESCHOOL,
            self::LOWER_PRIMARY,
            self::UPPER_PRIMARY,
            self::JUNIOR_HIGH,
        ], true)) {
            return $levelType;
        }

        return self::inferFromName((string) ($classroom->name ?? ''));
    }

    public static function inferFromName(string $name): string
    {
        $hay = strtolower(trim($name));
        if ($hay === '') {
            return self::OTHER;
        }

        if (preg_match('/\b(creche|crèche|foundation|nursery|pp\s*[123]|pp[123]|kg\s*[12]|kg[12]|pre[\s\-]?primary|baby\s*class)\b/i', $hay)) {
            return self::PRESCHOOL;
        }

        if (preg_match('/\b(?:grade|class|std|standard|form)\s*(\d{1,2})\b/i', $hay, $m)
            || preg_match('/^(\d{1,2})\s*(?:[a-z]|east|west|north|south|blue|red|green|yellow)?\b/i', $hay, $m)) {
            $grade = (int) $m[1];
            if ($grade >= 1 && $grade <= 3) {
                return self::LOWER_PRIMARY;
            }
            if ($grade >= 4 && $grade <= 6) {
                return self::UPPER_PRIMARY;
            }
            if ($grade >= 7 && $grade <= 9) {
                return self::JUNIOR_HIGH;
            }
        }

        return self::OTHER;
    }
}

<?php

namespace App\Services\Academics;

use App\Models\Academics\GradingBand;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\GradingSchemeMapping;

/**
 * Resolves percentage-based grades from per-class grading schemes (grading_schemes + grading_bands).
 */
class ClassroomGradingService
{
    /**
     * @return array{label: string|null, points: float|null, band: ?GradingBand}
     */
    public function gradeForPercentage(float $percent, int $classroomId): array
    {
        $percent = max(0, min(100, $percent));
        $schemeId = GradingSchemeMapping::where('classroom_id', $classroomId)->value('grading_scheme_id')
            ?? GradingScheme::where('is_default', true)->value('id');

        if (! $schemeId) {
            return ['label' => null, 'points' => null, 'band' => null];
        }

        $band = GradingBand::query()
            ->where('grading_scheme_id', $schemeId)
            ->where('min', '<=', $percent)
            ->where('max', '>=', $percent)
            ->orderByDesc('min')
            ->first();

        if (! $band) {
            return ['label' => null, 'points' => null, 'band' => null];
        }

        return [
            'label' => $band->label,
            'points' => $band->rank !== null ? (float) $band->rank : null,
            'band' => $band,
        ];
    }

    /**
     * Convert raw score to percentage using paper max marks, then grade.
     *
     * @return array{label: string|null, points: float|null}
     */
    public function gradeForRawScore(?float $score, float $maxMarks, int $classroomId): array
    {
        if ($score === null || $maxMarks <= 0) {
            return ['label' => null, 'points' => null];
        }

        $pct = (float) $score / (float) $maxMarks * 100.0;
        $g = $this->gradeForPercentage($pct, $classroomId);

        return [
            'label' => $g['label'],
            'points' => $g['points'],
        ];
    }
}

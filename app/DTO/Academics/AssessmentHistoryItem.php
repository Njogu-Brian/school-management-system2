<?php

namespace App\DTO\Academics;

use Carbon\CarbonInterface;

/**
 * Normalized read-model row for student assessment history (Phase 0 facade).
 */
class AssessmentHistoryItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $title,
        public readonly ?int $subjectId,
        public readonly ?string $subjectName,
        public readonly ?int $academicYearId,
        public readonly ?int $termId,
        public readonly ?string $assessedOn,
        public readonly ?float $scoreRaw,
        public readonly ?float $scoreMax,
        public readonly ?string $scoreDisplay,
        public readonly ?float $scorePercent,
        public readonly ?string $gradeLabel,
        public readonly ?array $performanceLevel,
        public readonly string $status,
        public readonly array $legacySource,
        public readonly ?string $remark = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->typeLabel,
            'title' => $this->title,
            'subject_id' => $this->subjectId,
            'subject_name' => $this->subjectName,
            'academic_year_id' => $this->academicYearId,
            'term_id' => $this->termId,
            'assessed_on' => $this->assessedOn,
            'score_raw' => $this->scoreRaw,
            'score_max' => $this->scoreMax,
            'score_display' => $this->scoreDisplay,
            'score_percent' => $this->scorePercent,
            'grade_label' => $this->gradeLabel,
            'performance_level' => $this->performanceLevel,
            'status' => $this->status,
            'remark' => $this->remark,
            'legacy_source' => $this->legacySource,
        ];
    }

    public function sortTimestamp(): int
    {
        if ($this->assessedOn) {
            return strtotime($this->assessedOn) ?: 0;
        }

        return 0;
    }

    public static function formatAssessedOn(CarbonInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m-d');
        }

        return substr((string) $date, 0, 10) ?: null;
    }

    public static function buildScoreDisplay(?float $score, ?float $max): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($max !== null && $max > 0) {
            return rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.')
                . '/'
                . rtrim(rtrim(number_format($max, 2, '.', ''), '0'), '.');
        }

        return rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');
    }

    public static function buildScorePercent(?float $score, ?float $max): ?float
    {
        if ($score === null || $max === null || $max <= 0) {
            return null;
        }

        return round(($score / $max) * 100, 2);
    }
}

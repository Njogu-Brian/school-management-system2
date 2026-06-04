<?php

namespace App\Services\Academics;

use App\Models\Academics\Assessment;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\PortfolioAssessment;

/**
 * Maps legacy rows to canonical assessment type codes (Phase 0 read layer).
 */
class AssessmentTypeResolver
{
    public const TYPE_TRADITIONAL_EXAM = 'traditional_exam';
    public const TYPE_CAT = 'cat';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_SPEED_TEST = 'speed_test';
    public const TYPE_PROJECT = 'project';
    public const TYPE_CBC_FORMATIVE = 'cbc_formative';
    public const TYPE_CBC_SUMMATIVE = 'cbc_summative';
    public const TYPE_PORTFOLIO = 'portfolio';
    public const TYPE_ORAL = 'oral';
    public const TYPE_PRACTICAL = 'practical';
    public const TYPE_WEEKLY_ASSESSMENT = 'weekly_assessment';
    public const TYPE_REPORT_CARD_TERM = 'report_card_term';

    public const LABELS = [
        self::TYPE_TRADITIONAL_EXAM => 'Traditional Exam',
        self::TYPE_CAT => 'CAT',
        self::TYPE_ASSIGNMENT => 'Assignment',
        self::TYPE_SPEED_TEST => 'Speed Test',
        self::TYPE_PROJECT => 'Project',
        self::TYPE_CBC_FORMATIVE => 'CBC Formative',
        self::TYPE_CBC_SUMMATIVE => 'CBC Summative',
        self::TYPE_PORTFOLIO => 'Portfolio',
        self::TYPE_ORAL => 'Oral Assessment',
        self::TYPE_PRACTICAL => 'Practical Assessment',
        self::TYPE_WEEKLY_ASSESSMENT => 'Weekly Assessment',
        self::TYPE_REPORT_CARD_TERM => 'Term Report Card',
    ];

    /**
     * @return array{type: string, type_label: string}
     */
    public function resolveForExamMark(ExamMark $mark): array
    {
        $exam = $mark->exam;
        if (! $exam) {
            return $this->pair(self::TYPE_TRADITIONAL_EXAM);
        }

        if ((bool) ($exam->is_cat ?? false)) {
            return $this->pair(self::TYPE_CAT);
        }

        $method = strtolower((string) ($mark->assessment_method ?? $exam->assessment_method ?? 'written'));
        if ($method === 'oral') {
            return $this->pair(self::TYPE_ORAL);
        }
        if ($method === 'practical') {
            return $this->pair(self::TYPE_PRACTICAL);
        }
        if ($method === 'portfolio') {
            return $this->pair(self::TYPE_PORTFOLIO);
        }
        if ($method === 'project') {
            return $this->pair(self::TYPE_PROJECT);
        }

        $category = strtolower((string) ($exam->exam_category ?? ''));
        if ($category === 'formative') {
            return $this->pair(self::TYPE_CBC_FORMATIVE);
        }
        if (in_array($category, ['summative', 'national', 'standardized'], true)) {
            return $this->pair(self::TYPE_CBC_SUMMATIVE);
        }

        $examTypeName = strtolower((string) ($exam->examType?->name ?? ''));
        $examName = strtolower((string) ($exam->name ?? ''));
        if (str_contains($examTypeName, 'quiz') || str_contains($examName, 'speed test') || str_contains($examName, 'speed-test')) {
            return $this->pair(self::TYPE_SPEED_TEST);
        }
        if (str_contains($examTypeName, 'mock') || str_contains($examName, 'mock')) {
            return $this->pair(self::TYPE_TRADITIONAL_EXAM);
        }

        return $this->pair(self::TYPE_TRADITIONAL_EXAM);
    }

    /**
     * @return array{type: string, type_label: string}
     */
    public function resolveForPortfolio(PortfolioAssessment $portfolio): array
    {
        if (($portfolio->portfolio_type ?? '') === 'project') {
            return $this->pair(self::TYPE_PROJECT);
        }

        return $this->pair(self::TYPE_PORTFOLIO);
    }

    /**
     * @return array{type: string, type_label: string}
     */
    public function resolveForWeeklyAssessment(Assessment $assessment): array
    {
        $raw = strtolower((string) ($assessment->assessment_type ?? ''));

        if (str_contains($raw, 'cat')) {
            return $this->pair(self::TYPE_CAT);
        }
        if (str_contains($raw, 'assign') || str_contains($raw, 'homework')) {
            return $this->pair(self::TYPE_ASSIGNMENT);
        }
        if (str_contains($raw, 'oral')) {
            return $this->pair(self::TYPE_ORAL);
        }
        if (str_contains($raw, 'practical')) {
            return $this->pair(self::TYPE_PRACTICAL);
        }
        if (str_contains($raw, 'project')) {
            return $this->pair(self::TYPE_PROJECT);
        }
        if (str_contains($raw, 'portfolio')) {
            return $this->pair(self::TYPE_PORTFOLIO);
        }
        if (str_contains($raw, 'speed')) {
            return $this->pair(self::TYPE_SPEED_TEST);
        }

        return $this->pair(self::TYPE_WEEKLY_ASSESSMENT);
    }

    public function labelFor(string $type): string
    {
        return self::LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * @return array{type: string, type_label: string}
     */
    protected function pair(string $type): array
    {
        return [
            'type' => $type,
            'type_label' => $this->labelFor($type),
        ];
    }
}

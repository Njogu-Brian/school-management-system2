<?php

namespace App\Services;

use App\Models\Academics\ExamMark;
use App\Models\Academics\CBCPerformanceLevel;
use App\Models\Academics\CBCCoreCompetency;
use App\Models\Academics\PortfolioAssessment;
use App\Models\Academics\Exam;
use App\Models\Student;
use Illuminate\Support\Collection;

class CBCAssessmentService
{
    /**
     * Calculate performance level from score
     */
    public static function calculatePerformanceLevel(float $score): ?CBCPerformanceLevel
    {
        return CBCPerformanceLevel::getByScore($score);
    }

    /**
     * Calculate overall performance level for a student in a term
     */
    public static function calculateOverallPerformanceLevel(
        int $studentId,
        int $academicYearId,
        int $termId
    ): ?CBCPerformanceLevel {
        $marks = ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
            $q->where('academic_year_id', $academicYearId)
              ->where('term_id', $termId);
        })
        ->where('student_id', $studentId)
        ->whereNotNull('score_raw')
        ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $totalScore = $marks->sum('score_raw');
        $totalMaxMarks = $marks->sum(function($mark) {
            return $mark->exam->max_marks ?? 100;
        });

        $average = $totalMaxMarks > 0 ? ($totalScore / $totalMaxMarks) * 100 : 0;

        return self::calculatePerformanceLevel($average);
    }

    /**
     * Calculate core competencies assessment
     */
    public static function calculateCoreCompetencies(
        int $studentId,
        int $academicYearId,
        int $termId
    ): array {
        $marks = ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
            $q->where('academic_year_id', $academicYearId)
              ->where('term_id', $termId);
        })
        ->where('student_id', $studentId)
        ->whereNotNull('competency_scores')
        ->get();

        $competencies = CBCCoreCompetency::getActive();
        $results = [];

        foreach ($competencies as $competency) {
            $scores = [];
            foreach ($marks as $mark) {
                $compScores = $mark->competency_scores ?? [];
                if (isset($compScores[$competency->code])) {
                    $scores[] = $compScores[$competency->code];
                }
            }

            $results[$competency->code] = [
                'name' => $competency->name,
                'code' => $competency->code,
                'average' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null,
                'scores' => $scores,
            ];
        }

        return $results;
    }

    /**
     * Calculate CAT breakdown
     */
    public static function calculateCATBreakdown(
        int $studentId,
        int $academicYearId,
        int $termId,
        int $subjectId = null
    ): array {
        $query = ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
            $q->where('academic_year_id', $academicYearId)
              ->where('term_id', $termId)
              ->where('is_cat', true);
        })
        ->where('student_id', $studentId)
        ->whereNotNull('score_raw');

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $catMarks = $query->get();

        $breakdown = [
            'cat_1' => null,
            'cat_2' => null,
            'cat_3' => null,
            'average' => null,
        ];

        foreach ($catMarks as $mark) {
            $catNumber = $mark->cat_number ?? $mark->exam->cat_number;
            if ($catNumber >= 1 && $catNumber <= 3) {
                $breakdown["cat_{$catNumber}"] = [
                    'score' => $mark->score_raw,
                    'max_marks' => $mark->exam->max_marks ?? 100,
                    'percentage' => $mark->exam->max_marks 
                        ? round(($mark->score_raw / $mark->exam->max_marks) * 100, 2)
                        : $mark->score_raw,
                    'performance_level' => $mark->performanceLevel?->code,
                ];
            }
        }

        // Calculate average
        $scores = array_filter(array_column($breakdown, 'percentage'), fn($v) => !is_null($v));
        if (count($scores) > 0) {
            $breakdown['average'] = round(array_sum($scores) / count($scores), 2);
        }

        return $breakdown;
    }

    /**
     * Calculate learning areas performance
     */
    public static function calculateLearningAreasPerformance(
        int $studentId,
        int $academicYearId,
        int $termId
    ): array {
        $marks = ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
            $q->where('academic_year_id', $academicYearId)
              ->where('term_id', $termId);
        })
        ->where('student_id', $studentId)
        ->with('subject')
        ->get()
        ->groupBy('subject.learning_area');

        $results = [];

        foreach ($marks as $learningArea => $areaMarks) {
            $totalScore = $areaMarks->sum('score_raw');
            $totalMaxMarks = $areaMarks->sum(function($mark) {
                return $mark->exam->max_marks ?? 100;
            });

            $average = $totalMaxMarks > 0 ? ($totalScore / $totalMaxMarks) * 100 : 0;
            $performanceLevel = self::calculatePerformanceLevel($average);

            $results[$learningArea] = [
                'average' => round($average, 2),
                'performance_level' => $performanceLevel?->code,
                'performance_level_name' => $performanceLevel?->name,
                'subjects_count' => $areaMarks->unique('subject_id')->count(),
            ];
        }

        return $results;
    }

    /**
     * Get portfolio summary for a student
     */
    public static function getPortfolioSummary(
        int $studentId,
        int $academicYearId,
        int $termId
    ): array {
        $portfolios = PortfolioAssessment::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', 'assessed')
            ->get();

        return [
            'total' => $portfolios->count(),
            'by_type' => $portfolios->groupBy('portfolio_type')->map->count(),
            'average_score' => $portfolios->avg('total_score'),
            'performance_levels' => $portfolios->groupBy('performance_level_id')
                ->map(function($group) {
                    return $group->first()->performanceLevel?->code;
                })
                ->filter()
                ->countBy()
                ->toArray(),
        ];
    }

    /**
     * Generate comprehensive CBC assessment data for report card
     */
    public static function generateReportCardData(
        int $studentId,
        int $academicYearId,
        int $termId
    ): array {
        $overallLevel = self::calculateOverallPerformanceLevel($studentId, $academicYearId, $termId);
        $competencies = self::calculateCoreCompetencies($studentId, $academicYearId, $termId);
        $catBreakdown = self::calculateCATBreakdown($studentId, $academicYearId, $termId);
        $learningAreas = self::calculateLearningAreasPerformance($studentId, $academicYearId, $termId);
        $portfolio = self::getPortfolioSummary($studentId, $academicYearId, $termId);

        return [
            'overall_performance_level_id' => $overallLevel?->id,
            'overall_performance_level' => $overallLevel?->code,
            'core_competencies' => $competencies,
            'cat_breakdown' => $catBreakdown,
            'learning_areas_performance' => $learningAreas,
            'portfolio_summary' => $portfolio,
            'performance_summary' => [
                'total_subjects' => ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
                    $q->where('academic_year_id', $academicYearId)
                      ->where('term_id', $termId);
                })
                ->where('student_id', $studentId)
                ->distinct('subject_id')
                ->count('subject_id'),
                'average_score' => ExamMark::whereHas('exam', function($q) use ($academicYearId, $termId) {
                    $q->where('academic_year_id', $academicYearId)
                      ->where('term_id', $termId);
                })
                ->where('student_id', $studentId)
                ->whereNotNull('score_raw')
                ->get()
                ->map(function($mark) {
                    $maxMarks = $mark->exam->max_marks ?? 100;
                    return $maxMarks > 0 ? ($mark->score_raw / $maxMarks) * 100 : 0;
                })
                ->avg(),
            ],
        ];
    }
}


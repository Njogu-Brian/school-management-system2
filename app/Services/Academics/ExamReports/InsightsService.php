<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;

class InsightsService
{
    /**
     * Basic automated insights by comparing current exam vs previous exam (same term/year/class).
     */
    public function examInsights(Exam $exam, Classroom $classroom, ?int $streamId = null): array
    {
        $analytics = new AnalyticsService();
        $current = $analytics->subjectPerformanceForExam($exam, $classroom, $streamId);

        $prevExam = Exam::query()
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('term_id', $exam->term_id)
            ->where('id', '!=', $exam->id)
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->firstWhere('starts_on', '<=', $exam->starts_on ?? now());

        if (! $prevExam) {
            return [
                'meta' => ['mode' => 'exam', 'exam_id' => $exam->id, 'prev_exam_id' => null],
                'insights' => [
                    ['type' => 'info', 'text' => 'No previous exam found in this term to compare trends.'],
                ],
            ];
        }

        $prev = $analytics->subjectPerformanceForExam($prevExam, $classroom, $streamId);
        $prevBySubject = collect($prev['subjects'] ?? [])->keyBy('subject_id');

        $insights = [];
        foreach (($current['subjects'] ?? []) as $row) {
            $p = $prevBySubject->get($row['subject_id']);
            if (! $p) continue;
            if ($row['mean'] !== null && $p['mean'] !== null) {
                $d = round(((float) $row['mean']) - ((float) $p['mean']), 2);
                if (abs($d) >= 3) {
                    $insights[] = [
                        'type' => $d > 0 ? 'positive' : 'negative',
                        'text' => "{$row['subject']} mean changed by {$d} (from {$p['mean']} to {$row['mean']}).",
                    ];
                }
            }
            if ($row['pass_rate'] !== null && $p['pass_rate'] !== null) {
                $d = round(((float) $row['pass_rate']) - ((float) $p['pass_rate']), 2);
                if (abs($d) >= 5) {
                    $insights[] = [
                        'type' => $d > 0 ? 'positive' : 'negative',
                        'text' => "{$row['subject']} pass rate changed by {$d}% (from {$p['pass_rate']}% to {$row['pass_rate']}%).",
                    ];
                }
            }
        }

        if (empty($insights)) {
            $insights[] = ['type' => 'info', 'text' => 'No major changes detected vs previous exam (thresholds: mean ±3, pass rate ±5%).'];
        }

        return [
            'meta' => ['mode' => 'exam', 'exam_id' => $exam->id, 'prev_exam_id' => $prevExam->id],
            'insights' => $insights,
        ];
    }
}


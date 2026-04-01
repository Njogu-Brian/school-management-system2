<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use Illuminate\Support\Collection;

class TrendsService
{
    private function passMarkPercent(): float
    {
        $v = setting('exam_pass_mark_percent', 50);
        $f = is_numeric($v) ? (float) $v : 50.0;
        return max(0.0, min(100.0, $f));
    }

    /**
     * Returns a per-exam time series for a term.
     * Filters optionally by classroom/stream/subject/teacher.
     */
    public function examSeriesForTerm(
        int $academicYearId,
        int $termId,
        ?int $classroomId = null,
        ?int $streamId = null,
        ?int $subjectId = null,
        ?int $teacherId = null
    ): array {
        $exams = Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->orderBy('starts_on')
            ->orderBy('created_at')
            ->get(['id', 'name', 'starts_on', 'max_marks']);

        if ($exams->isEmpty()) {
            return [
                'meta' => compact('academicYearId', 'termId', 'classroomId', 'streamId', 'subjectId', 'teacherId'),
                'series' => [],
            ];
        }

        $examIds = $exams->pluck('id');

        $marks = ExamMark::query()
            ->whereIn('exam_id', $examIds)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($classroomId || $streamId, function ($q) use ($classroomId, $streamId) {
                $q->whereHas('student', function ($qs) use ($classroomId, $streamId) {
                    if ($classroomId) $qs->where('classroom_id', $classroomId);
                    if ($streamId) $qs->where('stream_id', $streamId);
                });
            })
            ->get(['exam_id', 'score_raw', 'score_moderated'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            })
            ->filter(fn (ExamMark $m) => $m->marks_obtained !== null)
            ->values()
            ->groupBy('exam_id');

        $passPct = $this->passMarkPercent();

        $series = $exams->map(function (Exam $exam) use ($marks, $passPct) {
            $vals = collect($marks->get($exam->id, collect()))
                ->pluck('marks_obtained')
                ->map(fn ($v) => (float) $v)
                ->values();

            $max = (float) ($exam->max_marks ?? 100);
            $passThreshold = ($passPct / 100.0) * $max;
            $passRate = $vals->count()
                ? round(($vals->filter(fn ($m) => $m >= $passThreshold)->count() / $vals->count()) * 100, 2)
                : null;

            return [
                'exam_id' => $exam->id,
                'exam' => $exam->name,
                'starts_on' => $exam->starts_on?->toDateString(),
                'count' => $vals->count(),
                'mean' => $vals->count() ? round($vals->avg(), 2) : null,
                'min' => $vals->count() ? $vals->min() : null,
                'max' => $vals->count() ? $vals->max() : null,
                'pass_rate' => $passRate,
            ];
        })->values();

        // Value-add vs previous exam in series
        $series = $series->map(function ($row, $idx) use ($series) {
            $prev = $idx > 0 ? $series[$idx - 1] : null;
            $row['delta_mean'] = ($prev && $row['mean'] !== null && $prev['mean'] !== null)
                ? round(((float) $row['mean']) - ((float) $prev['mean']), 2)
                : null;
            $row['delta_pass_rate'] = ($prev && $row['pass_rate'] !== null && $prev['pass_rate'] !== null)
                ? round(((float) $row['pass_rate']) - ((float) $prev['pass_rate']), 2)
                : null;
            return $row;
        })->values();

        return [
            'meta' => [
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'classroom_id' => $classroomId,
                'stream_id' => $streamId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'pass_mark_percent' => $passPct,
            ],
            'series' => $series,
        ];
    }

    /**
     * Simple rule-based insights based on a series payload.
     */
    public function insightsFromSeries(array $seriesPayload): array
    {
        $series = collect($seriesPayload['series'] ?? []);
        if ($series->count() < 2) {
            return [
                'meta' => $seriesPayload['meta'] ?? [],
                'insights' => ['Not enough exams in the selected term to compute trends.'],
            ];
        }

        $latest = $series->last();
        $prev = $series[$series->count() - 2];

        $insights = [];
        if ($latest['delta_mean'] !== null) {
            $dir = $latest['delta_mean'] >= 0 ? 'improved' : 'declined';
            $insights[] = "Average {$dir} by {$latest['delta_mean']} compared to the previous exam.";
        }
        if ($latest['delta_pass_rate'] !== null) {
            $dir = $latest['delta_pass_rate'] >= 0 ? 'increased' : 'decreased';
            $insights[] = "Pass rate {$dir} by {$latest['delta_pass_rate']}% compared to the previous exam.";
        }
        if (($latest['mean'] ?? null) !== null) {
            $insights[] = "Latest exam mean: {$latest['mean']} (n={$latest['count']}).";
        }
        if (($latest['pass_rate'] ?? null) !== null) {
            $insights[] = "Latest exam pass rate: {$latest['pass_rate']}%.";
        }

        // Highlight best/worst in the term
        $best = $series->filter(fn ($r) => $r['mean'] !== null)->sortByDesc('mean')->first();
        $worst = $series->filter(fn ($r) => $r['mean'] !== null)->sortBy('mean')->first();
        if ($best && $worst && $best['exam_id'] !== $worst['exam_id']) {
            $insights[] = "Best mean in term: {$best['mean']} ({$best['exam']}). Worst: {$worst['mean']} ({$worst['exam']}).";
        }

        return [
            'meta' => $seriesPayload['meta'] ?? [],
            'insights' => $insights,
        ];
    }
}


<?php

namespace App\Services\Academics;

use App\DTO\Academics\AssessmentHistoryItem;
use App\Models\Academics\Assessment;
use App\Models\Academics\ExamMark;
use App\Models\Academics\PortfolioAssessment;
use App\Models\Academics\ReportCard;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation of legacy academic tables for Student 360 (Phase 0).
 */
class AssessmentReadFacade
{
    public function __construct(
        protected AssessmentTypeResolver $typeResolver,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function history(
        Student $student,
        array $filters = [],
        bool $restrictToPublishedForGuardians = false,
    ): LengthAwarePaginator {
        $items = collect()
            ->merge($this->collectExamMarks($student, $filters, $restrictToPublishedForGuardians))
            ->merge($this->collectWeeklyAssessments($student, $filters))
            ->merge($this->collectPortfolios($student, $filters, $restrictToPublishedForGuardians))
            ->merge($this->collectReportCards($student, $filters, $restrictToPublishedForGuardians));

        $typeFilter = $this->normalizeTypeFilter($filters['type'] ?? null);
        if ($typeFilter !== []) {
            $items = $items->filter(fn (AssessmentHistoryItem $item) => in_array($item->type, $typeFilter, true));
        }

        $items = $items->sortByDesc(fn (AssessmentHistoryItem $item) => $item->sortTimestamp())->values();

        return $this->paginateCollection($items, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function academicSummary(
        Student $student,
        array $filters = [],
        bool $restrictToPublishedForGuardians = false,
    ): array {
        $scopedFilters = $filters;
        unset($scopedFilters['page'], $scopedFilters['per_page'], $scopedFilters['type']);

        $allItems = collect()
            ->merge($this->collectExamMarks($student, $scopedFilters, $restrictToPublishedForGuardians))
            ->merge($this->collectWeeklyAssessments($student, $scopedFilters))
            ->merge($this->collectPortfolios($student, $scopedFilters, $restrictToPublishedForGuardians));

        $percents = $allItems
            ->map(fn (AssessmentHistoryItem $i) => $i->scorePercent)
            ->filter(fn ($p) => $p !== null)
            ->values();

        $countsByType = $allItems
            ->groupBy(fn (AssessmentHistoryItem $i) => $i->type)
            ->map(fn (Collection $group) => $group->count())
            ->all();

        $reportCardQuery = ReportCard::query()
            ->with(['term', 'overallPerformanceLevel'])
            ->where('student_id', $student->id);

        if (! empty($filters['academic_year_id'])) {
            $reportCardQuery->where('academic_year_id', (int) $filters['academic_year_id']);
        }
        if (! empty($filters['term_id'])) {
            $reportCardQuery->where('term_id', (int) $filters['term_id']);
        }
        if ($restrictToPublishedForGuardians) {
            $reportCardQuery->whereNotNull('published_at');
        }

        $reportCards = $reportCardQuery->orderByDesc('id')->get();
        $latestCard = $reportCards->first();

        $countsByType[AssessmentTypeResolver::TYPE_REPORT_CARD_TERM] = $reportCards->count();

        $latestPercent = $this->resolveReportCardOverallPercent($latestCard);
        $latestGrade = $latestCard?->overallPerformanceLevel?->name
            ?? $this->resolveSummaryGrade($latestCard);

        return [
            'student_id' => $student->id,
            'academic_year_id' => isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null,
            'term_id' => isset($filters['term_id']) ? (int) $filters['term_id'] : null,
            'current_term_id' => $this->resolveCurrentTermId(),
            'exam_average' => $percents->isNotEmpty() ? round((float) $percents->avg(), 2) : null,
            'latest_overall_percentage' => $latestPercent,
            'latest_overall_grade' => $latestGrade,
            'latest_performance_level' => $latestCard?->overallPerformanceLevel
                ? [
                    'id' => $latestCard->overallPerformanceLevel->id,
                    'code' => $latestCard->overallPerformanceLevel->code,
                    'name' => $latestCard->overallPerformanceLevel->name,
                ]
                : null,
            'report_cards_count' => $reportCards->count(),
            'published_report_cards_count' => $reportCards->whereNotNull('published_at')->count(),
            'marks_recorded_count' => $allItems->filter(
                fn (AssessmentHistoryItem $i) => $i->legacySource['table'] === 'exam_marks'
            )->count(),
            'portfolio_count' => $allItems->filter(
                fn (AssessmentHistoryItem $i) => in_array($i->legacySource['table'], ['portfolio_assessments'], true)
            )->count(),
            'weekly_assessment_count' => $allItems->filter(
                fn (AssessmentHistoryItem $i) => $i->legacySource['table'] === 'assessments'
            )->count(),
            'latest_report_card_id' => $latestCard?->id,
            'assessment_counts_by_type' => $countsByType,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssessmentHistoryItem>
     */
    protected function collectExamMarks(
        Student $student,
        array $filters,
        bool $restrictToPublishedForGuardians,
    ): Collection {
        $query = ExamMark::query()
            ->with(['exam.examType', 'subject', 'performanceLevel'])
            ->where('student_id', $student->id);

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }

        $query->whereHas('exam', function ($q) use ($filters, $restrictToPublishedForGuardians) {
            if (! empty($filters['academic_year_id'])) {
                $q->where('academic_year_id', (int) $filters['academic_year_id']);
            }
            if (! empty($filters['term_id'])) {
                $q->where('term_id', (int) $filters['term_id']);
            }
            if ($restrictToPublishedForGuardians) {
                $q->where(function ($inner) {
                    $inner->where('publish_result', true)
                        ->orWhereIn('status', ['published', 'locked', 'approved']);
                });
            }
            $this->applyDateRangeOnExam($q, $filters);
        });

        return $query->get()->map(function (ExamMark $mark) {
            $exam = $mark->exam;
            $typeInfo = $this->typeResolver->resolveForExamMark($mark);
            $score = $mark->score_moderated ?? $mark->score_raw;
            $max = $exam?->max_marks !== null ? (float) $exam->max_marks : null;
            $percent = AssessmentHistoryItem::buildScorePercent(
                $score !== null ? (float) $score : null,
                $max
            );

            $assessedOn = AssessmentHistoryItem::formatAssessedOn(
                $exam?->ends_on ?? $exam?->starts_on ?? $mark->updated_at
            );

            $status = (string) ($mark->status ?? 'draft');
            if ($exam && in_array($exam->status, ['published', 'locked'], true)) {
                $status = 'published';
            }

            return new AssessmentHistoryItem(
                id: 'exam_mark:' . $mark->id,
                type: $typeInfo['type'],
                typeLabel: $typeInfo['type_label'],
                title: $exam?->name ?? 'Exam',
                subjectId: $mark->subject_id ? (int) $mark->subject_id : null,
                subjectName: $mark->subject?->name,
                academicYearId: $exam?->academic_year_id ? (int) $exam->academic_year_id : null,
                termId: $exam?->term_id ? (int) $exam->term_id : null,
                assessedOn: $assessedOn,
                scoreRaw: $score !== null ? (float) $score : null,
                scoreMax: $max,
                scoreDisplay: AssessmentHistoryItem::buildScoreDisplay(
                    $score !== null ? (float) $score : null,
                    $max
                ),
                scorePercent: $percent,
                gradeLabel: $mark->grade_label,
                performanceLevel: $this->formatPerformanceLevel($mark->performanceLevel),
                status: $status,
                legacySource: ['table' => 'exam_marks', 'id' => (int) $mark->id],
                remark: $mark->remark ?? $mark->subject_remark,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssessmentHistoryItem>
     */
    protected function collectWeeklyAssessments(Student $student, array $filters): Collection
    {
        $query = Assessment::query()
            ->with('subject')
            ->where('student_id', $student->id);

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }

        if (! empty($filters['from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('assessment_date', '>=', $filters['from'])
                    ->orWhereDate('week_ending', '>=', $filters['from']);
            });
        }
        if (! empty($filters['to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('assessment_date', '<=', $filters['to'])
                    ->orWhereDate('week_ending', '<=', $filters['to']);
            });
        }

        return $query->orderByDesc('assessment_date')->get()->map(function (Assessment $row) {
            $typeInfo = $this->typeResolver->resolveForWeeklyAssessment($row);
            $score = $row->score !== null ? (float) $row->score : null;
            $max = $row->out_of !== null ? (float) $row->out_of : null;
            $percent = $row->score_percent !== null
                ? (float) $row->score_percent
                : AssessmentHistoryItem::buildScorePercent($score, $max);

            return new AssessmentHistoryItem(
                id: 'assessment:' . $row->id,
                type: $typeInfo['type'],
                typeLabel: $typeInfo['type_label'],
                title: $row->assessment_type
                    ? (string) $row->assessment_type
                    : 'Weekly Assessment',
                subjectId: $row->subject_id ? (int) $row->subject_id : null,
                subjectName: $row->subject?->name,
                academicYearId: null,
                termId: null,
                assessedOn: AssessmentHistoryItem::formatAssessedOn(
                    $row->assessment_date ?? $row->week_ending
                ),
                scoreRaw: $score,
                scoreMax: $max,
                scoreDisplay: AssessmentHistoryItem::buildScoreDisplay($score, $max),
                scorePercent: $percent,
                gradeLabel: null,
                performanceLevel: null,
                status: 'published',
                legacySource: ['table' => 'assessments', 'id' => (int) $row->id],
                remark: $row->remarks,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssessmentHistoryItem>
     */
    protected function collectPortfolios(
        Student $student,
        array $filters,
        bool $restrictToPublishedForGuardians,
    ): Collection {
        $query = PortfolioAssessment::query()
            ->with(['subject', 'performanceLevel'])
            ->where('student_id', $student->id);

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', (int) $filters['academic_year_id']);
        }
        if (! empty($filters['term_id'])) {
            $query->where('term_id', (int) $filters['term_id']);
        }
        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }
        if ($restrictToPublishedForGuardians) {
            $query->whereIn('status', ['assessed', 'published']);
        }

        $this->applyDateRangeOnColumn($query, 'assessment_date', $filters);

        return $query->orderByDesc('assessment_date')->get()->map(function (PortfolioAssessment $row) {
            $typeInfo = $this->typeResolver->resolveForPortfolio($row);
            $score = $row->total_score !== null ? (float) $row->total_score : null;

            return new AssessmentHistoryItem(
                id: 'portfolio:' . $row->id,
                type: $typeInfo['type'],
                typeLabel: $typeInfo['type_label'],
                title: $row->title,
                subjectId: $row->subject_id ? (int) $row->subject_id : null,
                subjectName: $row->subject?->name,
                academicYearId: $row->academic_year_id ? (int) $row->academic_year_id : null,
                termId: $row->term_id ? (int) $row->term_id : null,
                assessedOn: AssessmentHistoryItem::formatAssessedOn($row->assessment_date),
                scoreRaw: $score,
                scoreMax: null,
                scoreDisplay: $score !== null
                    ? AssessmentHistoryItem::buildScoreDisplay($score, null)
                    : null,
                scorePercent: null,
                gradeLabel: null,
                performanceLevel: $this->formatPerformanceLevel($row->performanceLevel),
                status: (string) ($row->status ?? 'draft'),
                legacySource: ['table' => 'portfolio_assessments', 'id' => (int) $row->id],
                remark: $row->feedback,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssessmentHistoryItem>
     */
    protected function collectReportCards(
        Student $student,
        array $filters,
        bool $restrictToPublishedForGuardians,
    ): Collection {
        $query = ReportCard::query()
            ->with(['term', 'overallPerformanceLevel'])
            ->where('student_id', $student->id);

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', (int) $filters['academic_year_id']);
        }
        if (! empty($filters['term_id'])) {
            $query->where('term_id', (int) $filters['term_id']);
        }
        if ($restrictToPublishedForGuardians) {
            $query->whereNotNull('published_at');
        }

        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
            $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
            $query->where(function ($q) use ($from, $to) {
                if ($from) {
                    $q->where('published_at', '>=', $from)
                        ->orWhere('updated_at', '>=', $from);
                }
                if ($to) {
                    $q->where(function ($inner) use ($to) {
                        $inner->where('published_at', '<=', $to)
                            ->orWhere('updated_at', '<=', $to);
                    });
                }
            });
        }

        return $query->orderByDesc('id')->get()->map(function (ReportCard $card) {
            $percent = $this->resolveReportCardOverallPercent($card);
            $type = AssessmentTypeResolver::TYPE_REPORT_CARD_TERM;
            $termName = $card->term?->name ?? ('Term #' . $card->term_id);

            return new AssessmentHistoryItem(
                id: 'report_card:' . $card->id,
                type: $type,
                typeLabel: $this->typeResolver->labelFor($type),
                title: 'Term Report — ' . $termName,
                subjectId: null,
                subjectName: null,
                academicYearId: (int) $card->academic_year_id,
                termId: (int) $card->term_id,
                assessedOn: AssessmentHistoryItem::formatAssessedOn(
                    $card->published_at ?? $card->updated_at
                ),
                scoreRaw: null,
                scoreMax: null,
                scoreDisplay: $percent !== null ? round($percent, 2) . '%' : null,
                scorePercent: $percent,
                gradeLabel: $this->resolveSummaryGrade($card),
                performanceLevel: $this->formatPerformanceLevel($card->overallPerformanceLevel),
                status: $card->published_at ? 'published' : 'draft',
                legacySource: ['table' => 'report_cards', 'id' => (int) $card->id],
                remark: $card->teacher_remark,
            );
        });
    }

    protected function resolveReportCardOverallPercent(?ReportCard $card): ?float
    {
        if (! $card) {
            return null;
        }

        $summary = $card->summary;
        if (is_string($summary)) {
            $decoded = json_decode($summary, true);
            $summary = is_array($decoded) ? $decoded : null;
        }

        if (is_array($summary)) {
            if (isset($summary['average']) && is_numeric($summary['average'])) {
                return round((float) $summary['average'], 2);
            }
            if (isset($summary['overall_percentage']) && is_numeric($summary['overall_percentage'])) {
                return round((float) $summary['overall_percentage'], 2);
            }
        }

        return null;
    }

    protected function resolveSummaryGrade(?ReportCard $card): ?string
    {
        if (! $card) {
            return null;
        }

        $summary = $card->summary;
        if (is_string($summary)) {
            $decoded = json_decode($summary, true);
            $summary = is_array($decoded) ? $decoded : null;
        }

        if (is_array($summary) && ! empty($summary['grade'])) {
            return (string) $summary['grade'];
        }

        return $card->overallPerformanceLevel?->code;
    }

    /**
     * @return array<string, string>|null
     */
    protected function formatPerformanceLevel($level): ?array
    {
        if (! $level) {
            return null;
        }

        return [
            'id' => (int) $level->id,
            'code' => (string) ($level->code ?? ''),
            'name' => (string) ($level->name ?? ''),
        ];
    }

    /**
     * @param  mixed  $typeInput
     * @return list<string>
     */
    protected function normalizeTypeFilter(mixed $typeInput): array
    {
        if ($typeInput === null || $typeInput === '') {
            return [];
        }

        if (is_string($typeInput)) {
            return array_values(array_filter(array_map('trim', explode(',', $typeInput))));
        }

        if (is_array($typeInput)) {
            return array_values(array_filter(array_map('strval', $typeInput)));
        }

        return [];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyDateRangeOnExam($query, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('ends_on', '>=', $filters['from'])
                    ->orWhereDate('starts_on', '>=', $filters['from']);
            });
        }
        if (! empty($filters['to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('starts_on', '<=', $filters['to'])
                    ->orWhereDate('ends_on', '<=', $filters['to']);
            });
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyDateRangeOnColumn($query, string $column, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    protected function resolveCurrentTermId(): ?int
    {
        $term = Term::query()->where('is_current', true)->first();

        return $term?->id;
    }

    /**
     * @param  Collection<int, AssessmentHistoryItem>  $items
     * @param  array<string, mixed>  $filters
     */
    protected function paginateCollection(Collection $items, array $filters): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}

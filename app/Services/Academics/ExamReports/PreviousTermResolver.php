<?php

namespace App\Services\Academics\ExamReports;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Collection;

/**
 * Resolve the immediately previous academic term for improvement comparisons.
 */
final class PreviousTermResolver
{
    /**
     * @return array{academic_year_id: int, term_id: int, term_name: string, academic_year: string|int|null}|null
     */
    public function resolve(int $academicYearId, int $termId): ?array
    {
        $term = Term::query()->with('academicYear')->find($termId);
        if (! $term || (int) $term->academic_year_id !== $academicYearId) {
            $term = Term::query()->with('academicYear')->where('id', $termId)->first();
            if (! $term) {
                return null;
            }
            $academicYearId = (int) $term->academic_year_id;
        }

        $siblings = $this->orderedTermsForYear($academicYearId);
        $index = $siblings->search(fn (Term $t) => (int) $t->id === (int) $term->id);
        if ($index === false) {
            return null;
        }

        if ($index > 0) {
            /** @var Term $prev */
            $prev = $siblings->get($index - 1);

            return $this->format($prev);
        }

        $prevYear = $this->previousAcademicYear($academicYearId);
        if (! $prevYear) {
            return null;
        }

        $prevYearTerms = $this->orderedTermsForYear((int) $prevYear->id);
        $prev = $prevYearTerms->last();
        if (! $prev instanceof Term) {
            return null;
        }

        return $this->format($prev);
    }

    /**
     * @return Collection<int, Term>
     */
    public function orderedTermsForYear(int $academicYearId): Collection
    {
        return Term::query()
            ->where('academic_year_id', $academicYearId)
            ->with('academicYear')
            ->get()
            ->sortBy(fn (Term $t) => $this->termSortKey($t))
            ->values();
    }

    private function previousAcademicYear(int $academicYearId): ?AcademicYear
    {
        $current = AcademicYear::query()->find($academicYearId);
        if (! $current) {
            return null;
        }

        $yearValue = is_numeric($current->year) ? (int) $current->year : null;
        if ($yearValue !== null) {
            $prev = AcademicYear::query()->where('year', $yearValue - 1)->first();
            if ($prev) {
                return $prev;
            }
        }

        return AcademicYear::query()
            ->where('id', '<', $academicYearId)
            ->orderByDesc('id')
            ->first();
    }

    private function termSortKey(Term $term): string
    {
        if ($term->opening_date) {
            return '0:'.$term->opening_date->format('Y-m-d').':'.$term->id;
        }

        $name = strtolower(trim((string) $term->name));
        if (preg_match('/\b(?:term|t)\s*([123])\b/', $name, $m)) {
            return '1:'.$m[1].':'.$term->id;
        }
        if (preg_match('/\b(first|1st)\b/', $name)) {
            return '1:1:'.$term->id;
        }
        if (preg_match('/\b(second|2nd)\b/', $name)) {
            return '1:2:'.$term->id;
        }
        if (preg_match('/\b(third|3rd)\b/', $name)) {
            return '1:3:'.$term->id;
        }

        return '2:'.$name.':'.$term->id;
    }

    /**
     * @return array{academic_year_id: int, term_id: int, term_name: string, academic_year: string|int|null}
     */
    private function format(Term $term): array
    {
        return [
            'academic_year_id' => (int) $term->academic_year_id,
            'term_id' => (int) $term->id,
            'term_name' => (string) $term->name,
            'academic_year' => $term->academicYear?->year,
        ];
    }
}

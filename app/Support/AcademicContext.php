<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Collection;

class AcademicContext
{
    public static function years(): Collection
    {
        return AcademicYear::orderByDesc('year')->get();
    }

    public static function resolveYearId(?int $requested = null): ?int
    {
        if ($requested && $requested > 0) {
            return $requested;
        }

        $fromTerm = get_current_academic_year_model();
        if ($fromTerm) {
            return (int) $fromTerm->id;
        }

        $active = AcademicYear::where('is_active', true)->orderByDesc('id')->first();
        if ($active) {
            return (int) $active->id;
        }

        $latest = AcademicYear::orderByDesc('year')->value('id');

        return $latest ? (int) $latest : null;
    }

    public static function termsForYear(?int $yearId): Collection
    {
        if (! $yearId) {
            return collect();
        }

        return Term::query()
            ->where('academic_year_id', $yearId)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get();
    }

    public static function allTermsForSelect(): Collection
    {
        return Term::query()
            ->with('academicYear')
            ->orderByDesc('academic_year_id')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get();
    }

    public static function defaultTermForYear(int $yearId): ?Term
    {
        $terms = self::termsForYear($yearId);
        if ($terms->isEmpty()) {
            return null;
        }

        $current = get_current_term_model();
        if ($current && (int) $current->academic_year_id === $yearId) {
            return $current;
        }

        $byFlag = $terms->firstWhere('is_current', true);
        if ($byFlag) {
            return $byFlag;
        }

        $today = now()->toDateString();
        foreach ($terms as $term) {
            if ($term->opening_date && $term->closing_date) {
                $open = $term->opening_date->toDateString();
                $close = $term->closing_date->toDateString();
                if ($today >= $open && $today <= $close) {
                    return $term;
                }
            }
        }

        $first = $terms->first();
        $last = $terms->last();
        if ($first?->opening_date && $today < $first->opening_date->toDateString()) {
            return $first;
        }
        if ($last?->closing_date && $today > $last->closing_date->toDateString()) {
            return $last;
        }

        return $last;
    }

    public static function resolveTermId(?int $yearId, ?int $requested = null): ?int
    {
        if (! $yearId) {
            return null;
        }

        $terms = self::termsForYear($yearId);
        if ($terms->isEmpty()) {
            return null;
        }

        if ($requested && $requested > 0 && $terms->contains('id', $requested)) {
            return $requested;
        }

        return self::defaultTermForYear($yearId)?->id;
    }

    public static function termLabel(Term $term): string
    {
        $year = $term->academicYear?->year ?? '';

        return $year !== '' ? "{$year} · {$term->name}" : $term->name;
    }

    public static function termBelongsToYear(int $termId, int $yearId): bool
    {
        return Term::query()
            ->where('id', $termId)
            ->where('academic_year_id', $yearId)
            ->exists();
    }

    /**
     * Standard year/term data for filter forms and CRUD views.
     *
     * @return array{
     *     years: Collection,
     *     academicYears: Collection,
     *     terms: Collection,
     *     selectedYearId: int|null,
     *     selectedTermId: int|null,
     *     defaultYearId: int|null,
     *     defaultTermId: int|null
     * }
     */
    public static function forView(
        ?int $requestedYearId = null,
        ?int $requestedTermId = null,
        bool $applyDefaults = true,
    ): array {
        $years = self::years();
        $defaultYearId = self::resolveYearId(null);

        $yearId = ($requestedYearId && $requestedYearId > 0)
            ? $requestedYearId
            : ($applyDefaults ? $defaultYearId : null);

        $termId = self::resolveTermId(
            $yearId,
            ($requestedTermId && $requestedTermId > 0) ? $requestedTermId : null,
        );

        return [
            'years' => $years,
            'academicYears' => $years,
            'terms' => self::allTermsForSelect(),
            'selectedYearId' => $yearId,
            'selectedTermId' => $termId,
            'defaultYearId' => $defaultYearId,
            'defaultTermId' => self::resolveTermId($defaultYearId, null),
        ];
    }

    /**
     * Resolve year/term filter IDs for list pages (defaults on first visit; empty = all).
     *
     * @return array{yearId: int|null, termId: int|null, applyYearFilter: bool, applyTermFilter: bool}
     */
    public static function listFilterIds(
        bool $hasYearParam,
        bool $yearFilled,
        ?int $yearValue,
        bool $hasTermParam,
        bool $termFilled,
        ?int $termValue,
    ): array {
        $defaultYearId = self::resolveYearId(null);

        if ($hasYearParam) {
            $yearId = $yearFilled ? $yearValue : null;
            $applyYearFilter = $yearFilled;
        } else {
            $yearId = $defaultYearId;
            $applyYearFilter = true;
        }

        if ($hasTermParam) {
            $termId = $termFilled ? $termValue : null;
            $applyTermFilter = $termFilled;
        } else {
            $termId = $yearId ? self::resolveTermId($yearId, null) : null;
            $applyTermFilter = (bool) $termId;
        }

        if ($termId && $yearId && ! self::termBelongsToYear($termId, $yearId)) {
            $termId = self::resolveTermId($yearId, null);
        }

        return [
            'yearId' => $yearId,
            'termId' => $termId,
            'applyYearFilter' => $applyYearFilter,
            'applyTermFilter' => $applyTermFilter,
        ];
    }
}

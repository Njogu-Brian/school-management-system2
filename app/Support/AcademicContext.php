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

    /**
     * Terms keyed by calendar/academic year number for filter dropdowns.
     *
     * @return array<string, list<array{id:int,name:string,number:int}>>
     */
    public static function termsByYearNumber(): array
    {
        return Term::query()
            ->with('academicYear:id,year')
            ->orderBy('opening_date')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Term $term) => (string) ($term->academicYear?->year ?? ''))
            ->map(function (Collection $terms) {
                return $terms->map(function (Term $term) {
                    $num = (int) preg_replace('/\D+/', '', (string) $term->name);
                    if ($num < 1 || $num > 3) {
                        $num = 1;
                    }

                    return [
                        'id' => (int) $term->id,
                        'name' => (string) $term->name,
                        'number' => $num,
                    ];
                })->values()->all();
            })
            ->filter(fn ($_, $year) => $year !== '')
            ->all();
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

    /**
     * Default year for list/filter pages.
     *
     * During holidays the calendar advances to the next upcoming term (prep).
     * List pages should still open on the most recently closed term so existing
     * exams/report cards remain visible until the new term starts.
     */
    public static function resolveListYearId(): ?int
    {
        if (function_exists('is_school_in_session') && is_school_in_session()) {
            return self::resolveYearId(null);
        }

        $lastClosed = self::mostRecentlyClosedTerm();
        if ($lastClosed?->academic_year_id) {
            return (int) $lastClosed->academic_year_id;
        }

        return self::resolveYearId(null);
    }

    /**
     * Default term for list/filter pages (see resolveListYearId).
     */
    public static function resolveListTermId(?int $yearId): ?int
    {
        if (! $yearId) {
            return null;
        }

        if (function_exists('is_school_in_session') && is_school_in_session()) {
            return self::resolveTermId($yearId, null);
        }

        $lastClosed = self::mostRecentlyClosedTerm($yearId);
        if ($lastClosed) {
            return (int) $lastClosed->id;
        }

        return self::resolveTermId($yearId, null);
    }

    public static function mostRecentlyClosedTerm(?int $yearId = null): ?Term
    {
        $today = now()->toDateString();

        return Term::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->whereNotNull('closing_date')
            ->whereDate('closing_date', '<', $today)
            ->orderByDesc('closing_date')
            ->orderByDesc('id')
            ->first();
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
            ->orderByDesc('is_current')
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
     * Year/term selects for report filter pages.
     *
     * Preserves submitted IDs (no silent remap). First visit (no year param)
     * uses list defaults so holiday "upcoming" years do not empty the form.
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
    public static function forFilterRequest(
        bool $hasYearParam,
        ?int $yearValue,
        bool $hasTermParam,
        ?int $termValue,
    ): array {
        $years = self::years();
        $listYearId = self::resolveListYearId();

        if ($hasYearParam) {
            $yearId = ($yearValue && $yearValue > 0) ? $yearValue : null;
        } else {
            $yearId = $listYearId;
        }

        if ($hasTermParam) {
            $termId = ($termValue && $termValue > 0) ? $termValue : null;
        } else {
            $termId = $yearId ? self::resolveListTermId($yearId) : null;
        }

        return [
            'years' => $years,
            'academicYears' => $years,
            'terms' => self::allTermsForSelect(),
            'selectedYearId' => $yearId,
            'selectedTermId' => $termId,
            'defaultYearId' => $listYearId,
            'defaultTermId' => self::resolveListTermId($listYearId),
        ];
    }

    /**
     * Resolve year/term filter IDs for list pages (defaults on first visit; empty = all).
     *
     * Explicit empty year/term params mean "All Years/Terms" and must not be
     * re-defaulted by forView() or the shared selects partial.
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
        $defaultYearId = self::resolveListYearId();

        if ($hasYearParam) {
            $yearId = $yearFilled ? $yearValue : null;
            $applyYearFilter = $yearFilled;
        } else {
            $yearId = $defaultYearId;
            $applyYearFilter = (bool) $yearId;
        }

        if ($hasTermParam) {
            $termId = $termFilled ? $termValue : null;
            $applyTermFilter = $termFilled;
        } else {
            $termId = $yearId ? self::resolveListTermId($yearId) : null;
            $applyTermFilter = (bool) $termId;
        }

        if ($termId && $yearId && ! self::termBelongsToYear($termId, $yearId)) {
            $termId = $applyTermFilter
                ? self::resolveListTermId($yearId)
                : null;
            if (! $termId) {
                $applyTermFilter = false;
            }
        }

        return [
            'yearId' => $yearId,
            'termId' => $termId,
            'applyYearFilter' => $applyYearFilter,
            'applyTermFilter' => $applyTermFilter,
        ];
    }
}

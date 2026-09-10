<?php

namespace App\Models\Concerns;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;

trait OrdersByAcademicCalendar
{
    /**
     * Newest academic year first, then Term 1 → Term 2 → Term 3 via opening_date.
     *
     * Do not order by id/created_at: sittings created out of calendar order
     * (e.g. Term 1 papers added after Term 2) would list in the wrong term sequence.
     */
    public function scopeInAcademicOrder(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        return $query
            ->orderByDesc(
                AcademicYear::query()
                    ->select('year')
                    ->whereColumn('academic_years.id', "{$table}.academic_year_id")
                    ->limit(1)
            )
            ->orderByRaw(
                '(SELECT CASE WHEN opening_date IS NULL THEN 1 ELSE 0 END FROM terms WHERE terms.id = `'.$table.'`.term_id LIMIT 1) ASC'
            )
            ->orderBy(
                Term::query()
                    ->select('opening_date')
                    ->whereColumn('terms.id', "{$table}.term_id")
                    ->limit(1)
            )
            ->orderBy(
                Term::query()
                    ->select('name')
                    ->whereColumn('terms.id', "{$table}.term_id")
                    ->limit(1)
            )
            ->orderBy("{$table}.starts_on")
            ->orderBy("{$table}.id");
    }
}

<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the operational current term and whether school is in session.
 *
 * During an inter-term holiday the next upcoming term becomes current so
 * requirements, academics, and fees prepare for that term. School remains
 * out of session until that term's opening date, so attendance stays blocked.
 * Invoice due dates continue to use each term's opening date.
 */
class AcademicCalendarService
{
    /** @var array<string, Term|null> */
    private static array $resolvedTerms = [];

    private static bool $didSyncThisRequest = false;

    /** @var list<array{start: string, end: string, midterm_start: ?string, midterm_end: ?string}>|null */
    private static ?array $sessionWindows = null;

    public static function flush(): void
    {
        self::$resolvedTerms = [];
        self::$didSyncThisRequest = false;
        self::$sessionWindows = null;
    }

    public function currentTerm(CarbonInterface|string|null $date = null, bool $syncFlag = true): ?Term
    {
        $day = $this->normalizeDate($date);
        $cacheKey = $day->toDateString();

        if (array_key_exists($cacheKey, self::$resolvedTerms)) {
            $cached = self::$resolvedTerms[$cacheKey];
            if ($syncFlag && $cached && ! self::$didSyncThisRequest) {
                $this->syncCurrentFlags($cached);
            }

            return $cached;
        }

        $resolved = $this->resolveTermForDate($day);
        self::$resolvedTerms[$cacheKey] = $resolved;

        if ($syncFlag && $resolved) {
            $this->syncCurrentFlags($resolved);
        }

        return $resolved;
    }

    /**
     * True when the date falls inside a term's opening–closing window
     * and is not inside that term's midterm break.
     */
    public function isSchoolInSession(CarbonInterface|string|null $date = null): bool
    {
        return $this->isDateInTermSession($date);
    }

    public function isDateInTermSession(CarbonInterface|string|null $date = null): bool
    {
        $day = $this->normalizeDate($date)->toDateString();

        foreach ($this->sessionWindows() as $window) {
            if ($day < $window['start'] || $day > $window['end']) {
                continue;
            }

            if ($window['midterm_start'] && $window['midterm_end']
                && $day >= $window['midterm_start']
                && $day <= $window['midterm_end']) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function resolveTermForDate(CarbonInterface $day): ?Term
    {
        $date = $day->toDateString();

        $inSession = Term::query()
            ->whereNotNull('opening_date')
            ->whereNotNull('closing_date')
            ->whereDate('opening_date', '<=', $date)
            ->whereDate('closing_date', '>=', $date)
            ->orderBy('opening_date')
            ->first();

        if ($inSession) {
            return $inSession;
        }

        $upcoming = Term::query()
            ->whereNotNull('opening_date')
            ->whereDate('opening_date', '>', $date)
            ->orderBy('opening_date')
            ->first();

        if ($upcoming) {
            return $upcoming;
        }

        $lastClosed = Term::query()
            ->whereNotNull('closing_date')
            ->orderByDesc('closing_date')
            ->first();

        if ($lastClosed) {
            return $lastClosed;
        }

        return Term::query()->where('is_current', true)->first();
    }

    private function syncCurrentFlags(Term $term): void
    {
        if (self::$didSyncThisRequest) {
            return;
        }
        self::$didSyncThisRequest = true;

        try {
            $alreadyCurrent = Term::query()
                ->where('id', $term->id)
                ->where('is_current', true)
                ->exists();

            $otherCurrent = Term::query()
                ->where('is_current', true)
                ->where('id', '!=', $term->id)
                ->exists();

            if ($alreadyCurrent && ! $otherCurrent) {
                $this->syncActiveYear($term);

                return;
            }

            Term::query()->where('is_current', true)->update(['is_current' => false]);
            if (! $term->is_current) {
                $term->is_current = true;
            }
            Term::query()->where('id', $term->id)->update(['is_current' => true]);
            $term->setAttribute('is_current', true);

            $this->syncActiveYear($term);
        } catch (\Throwable) {
            // Read-only connections or missing columns should not break page loads.
        }
    }

    private function syncActiveYear(Term $term): void
    {
        if (! $term->academic_year_id || ! Schema::hasColumn('academic_years', 'is_active')) {
            return;
        }

        $activeId = AcademicYear::query()->where('is_active', true)->value('id');
        if ((int) $activeId === (int) $term->academic_year_id) {
            return;
        }

        AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);
        AcademicYear::query()->where('id', $term->academic_year_id)->update(['is_active' => true]);
    }

    /**
     * @return list<array{start: string, end: string, midterm_start: ?string, midterm_end: ?string}>
     */
    private function sessionWindows(): array
    {
        if (self::$sessionWindows !== null) {
            return self::$sessionWindows;
        }

        $columns = ['opening_date', 'closing_date'];
        if (Schema::hasColumn('terms', 'midterm_start_date')) {
            $columns[] = 'midterm_start_date';
            $columns[] = 'midterm_end_date';
        }

        self::$sessionWindows = Term::query()
            ->whereNotNull('opening_date')
            ->whereNotNull('closing_date')
            ->get($columns)
            ->map(fn (Term $term) => [
                'start' => $term->opening_date->toDateString(),
                'end' => $term->closing_date->toDateString(),
                'midterm_start' => $term->midterm_start_date?->toDateString(),
                'midterm_end' => $term->midterm_end_date?->toDateString(),
            ])
            ->values()
            ->all();

        return self::$sessionWindows;
    }

    private function normalizeDate(CarbonInterface|string|null $date): Carbon
    {
        $tz = config('app.timezone', 'UTC');

        if ($date instanceof CarbonInterface) {
            return Carbon::parse($date->toDateString(), $tz)->startOfDay();
        }

        if (is_string($date) && $date !== '') {
            return Carbon::parse($date, $tz)->startOfDay();
        }

        return now($tz)->startOfDay();
    }
}

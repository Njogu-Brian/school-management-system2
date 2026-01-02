<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\SchoolDay;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AcademicConfigController extends Controller
{
    public function index()
    {
        // Paginate to avoid long renders on instances with many years/terms
        $years = AcademicYear::with(['terms' => function ($q) {
                // Sort terms numerically by the number after "Term "
                $q->orderByRaw('CAST(SUBSTRING(name, 6) AS UNSIGNED) ASC');
            }])
            // Sort years numerically by year column
            ->orderByRaw('CAST(year AS UNSIGNED) DESC')
            ->paginate(25);

        return view('settings.academic.index', compact('years'));
    }

    // --------- Academic Year ---------
    public function createYear() { return view('settings.academic.create_year'); }

    public function storeYear(Request $request)
    {
        $request->validate([
            'year' => 'required|digits:4|unique:academic_years,year',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        AcademicYear::create([
            'year' => $request->year,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('settings.academic.index')->with('success', 'Academic year created.');
    }

   public function updateYear(Request $request, AcademicYear $year)
    {
        $request->validate([
            'year' => 'required|digits:4|unique:academic_years,year,' . $year->id,
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            // deactivate ALL years before setting this one
            AcademicYear::query()->update(['is_active' => false]);
        }

        $year->update([
            'year' => $request->year,
            'is_active' => $isActive,
        ]);

        return redirect()->route('settings.academic.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function editYear(AcademicYear $year)
    {
        return view('settings.academic.edit_year', compact('year'));
    }

    public function destroyYear(AcademicYear $year)
    {
        $year->delete();
        return redirect()->route('settings.academic.index')->with('success', 'Academic year deleted.');
    }

    // --------- Terms ---------
    public function createTerm()
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $suggestedOpeningDate = $this->getNextSuggestedOpeningDate();
        return view('settings.academic.create_term', compact('years', 'suggestedOpeningDate'));
    }

    public function editTerm(Term $term)
    {
        $years = AcademicYear::orderByDesc('year')->get();
        return view('settings.academic.edit_term', compact('term', 'years'));
    }

    public function storeTerm(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_current' => 'nullable|boolean',
            'opening_date' => 'required|date',
            'closing_date' => 'required|date|after:opening_date',
            'midterm_start_date' => 'nullable|date|after_or_equal:opening_date|before_or_equal:closing_date',
            'midterm_end_date' => 'nullable|date|after_or_equal:midterm_start_date|before_or_equal:closing_date',
            'expected_school_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->validateMidtermPairing($request);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            // Only one current term per academic year
            Term::where('academic_year_id', $request->academic_year_id)
                ->update(['is_current' => false]);
        }

        $this->assertTermChronology(
            Carbon::parse($request->opening_date),
            Carbon::parse($request->closing_date),
            (int) $request->academic_year_id
        );

        $term = Term::create([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'is_current' => $isCurrent,
            'opening_date' => $request->opening_date,
            'closing_date' => $request->closing_date,
            'midterm_start_date' => $request->midterm_start_date,
            'midterm_end_date' => $request->midterm_end_date,
            'expected_school_days' => $request->expected_school_days,
            'notes' => $request->notes,
        ]);

        // Generate holidays and inter-term breaks
        if ($term->opening_date && $term->closing_date) {
            SchoolDay::generateKenyanHolidays(Carbon::parse($term->opening_date)->year);
            $this->markInterTermBreaks($term);
            $this->markMidtermBreaks($term);
            $this->syncTermEvents($term);
            $this->syncHolidayEventsForYear((int) Carbon::parse($term->opening_date)->year);
        }

        return redirect()->route('settings.academic.index')
            ->with('success', 'Term created successfully.');
    }

    public function updateTerm(Request $request, Term $term)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_current' => 'nullable|boolean',
            'opening_date' => 'required|date',
            'closing_date' => 'required|date|after:opening_date',
            'midterm_start_date' => 'nullable|date|after_or_equal:opening_date|before_or_equal:closing_date',
            'midterm_end_date' => 'nullable|date|after_or_equal:midterm_start_date|before_or_equal:closing_date',
            'expected_school_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->validateMidtermPairing($request);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            // Deactivate ALL terms (across all years)
            Term::query()->update(['is_current' => false]);
        }

        $this->assertTermChronology(
            Carbon::parse($request->opening_date),
            Carbon::parse($request->closing_date),
            (int) $request->academic_year_id,
            $term->id
        );

        $term->update([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'is_current' => $isCurrent,
            'opening_date' => $request->opening_date,
            'closing_date' => $request->closing_date,
            'midterm_start_date' => $request->midterm_start_date,
            'midterm_end_date' => $request->midterm_end_date,
            'expected_school_days' => $request->expected_school_days,
            'notes' => $request->notes,
        ]);

        if ($term->opening_date && $term->closing_date) {
            SchoolDay::generateKenyanHolidays(Carbon::parse($term->opening_date)->year);
            $this->markInterTermBreaks($term);
            $this->markMidtermBreaks($term);
            $this->syncTermEvents($term);
            $this->syncHolidayEventsForYear((int) Carbon::parse($term->opening_date)->year);
        }

        return redirect()->route('settings.academic.index')
            ->with('success', 'Term updated successfully.');
    }
    public function destroyTerm(Term $term)
    {
        $term->delete();
        return redirect()->route('settings.academic.index')->with('success', 'Term deleted.');
    }

    /**
     * Suggest next opening date based on latest closing date across terms/years.
     */
    private function getNextSuggestedOpeningDate(): ?string
    {
        $lastClosing = Term::whereNotNull('closing_date')
            ->orderByDesc('closing_date')
            ->value('closing_date');

        if (!$lastClosing) {
            return null;
        }

        return Carbon::parse($lastClosing)->addDay()->toDateString();
    }

    /**
     * Mark holidays for the gap between adjacent terms.
     * - Between previous term closing +1 and this term opening -1
     * - Between this term closing +1 and next term opening -1 (if next exists)
     */
    private function markInterTermBreaks(Term $term): void
    {
        if (!$term->opening_date || !$term->closing_date) {
            return;
        }

        $opening = Carbon::parse($term->opening_date);
        $closing = Carbon::parse($term->closing_date);

        // Gap from previous term
        $previous = Term::where('academic_year_id', $term->academic_year_id)
            ->where('id', '!=', $term->id)
            ->whereNotNull('closing_date')
            ->whereDate('closing_date', '<', $opening)
            ->orderByDesc('closing_date')
            ->first();

        if ($previous && $previous->closing_date) {
            $this->markHolidayRange(
                Carbon::parse($previous->closing_date)->addDay(),
                $opening->copy()->subDay(),
                'Term Break',
                $term->academic_year_id
            );
        }

        // Gap to next term
        $next = Term::where('academic_year_id', $term->academic_year_id)
            ->where('id', '!=', $term->id)
            ->whereNotNull('opening_date')
            ->whereDate('opening_date', '>', $closing)
            ->orderBy('opening_date')
            ->first();

        if ($next && $next->opening_date) {
            $this->markHolidayRange(
                $closing->copy()->addDay(),
                Carbon::parse($next->opening_date)->subDay(),
                'Term Break',
                $term->academic_year_id
            );
        }
    }

    /**
     * Mark midterm break dates in SchoolDay.
     */
    private function markMidtermBreaks(Term $term): void
    {
        if (!$term->midterm_start_date || !$term->midterm_end_date) {
            return;
        }

        $start = Carbon::parse($term->midterm_start_date);
        $end = Carbon::parse($term->midterm_end_date);

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            SchoolDay::updateOrCreate(
                ['date' => $cursor->toDateString()],
                [
                    'type' => SchoolDay::TYPE_MIDTERM_BREAK,
                    'name' => "Midterm Break ({$term->name})",
                    'description' => 'Auto-generated midterm break',
                    'is_custom' => false,
                ]
            );
            $cursor->addDay();
        }
    }

    /**
     * Mark a date range as holidays in SchoolDay (skips invalid ranges).
     * Note: This should only be used for inter-term breaks, not dates within terms.
     */
    private function markHolidayRange(Carbon $start, Carbon $end, string $name, ?int $academicYearId): void
    {
        if ($start->gt($end)) {
            return;
        }

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            // Only update if the date is not already marked as a school day or midterm break
            // This prevents overwriting existing school days within terms
            $existing = SchoolDay::where('date', $cursor->toDateString())->first();
            if ($existing && $existing->type === SchoolDay::TYPE_SCHOOL_DAY) {
                // Don't overwrite school days - this shouldn't happen for inter-term breaks
                // but adding as a safeguard
                $cursor->addDay();
                continue;
            }

            SchoolDay::updateOrCreate(
                ['date' => $cursor->toDateString()],
                [
                    'type' => SchoolDay::TYPE_HOLIDAY,
                    'name' => $name,
                    'description' => 'Auto-generated between terms',
                    'is_custom' => true,
                ]
            );
            $this->upsertEvent(
                $name,
                $cursor->toDateString(),
                $cursor->toDateString(),
                'holiday',
                $academicYearId
            );
            $cursor->addDay();
        }
    }

    /**
     * Ensure term and midterm appear on the calendar.
     */
    private function syncTermEvents(Term $term): void
    {
        if ($term->opening_date && $term->closing_date) {
            $this->upsertEvent(
                $term->name,
                Carbon::parse($term->opening_date)->toDateString(),
                Carbon::parse($term->closing_date)->toDateString(),
                'academic',
                $term->academic_year_id
            );
        }

        if ($term->midterm_start_date && $term->midterm_end_date) {
            $this->upsertEvent(
                "Midterm Break ({$term->name})",
                Carbon::parse($term->midterm_start_date)->toDateString(),
                Carbon::parse($term->midterm_end_date)->toDateString(),
                'holiday',
                $term->academic_year_id
            );
        }
    }

    /**
     * Sync public/custom holidays to events for a given year.
     */
    private function syncHolidayEventsForYear(int $year): void
    {
        $holidays = SchoolDay::whereYear('date', $year)
            ->where('type', SchoolDay::TYPE_HOLIDAY)
            ->get();

        $ay = AcademicYear::where('year', $year)->first();
        $ayId = $ay?->id;

        foreach ($holidays as $holiday) {
            $this->upsertEvent(
                $holiday->name ?? 'Holiday',
                $holiday->date->toDateString(),
                $holiday->date->toDateString(),
                'holiday',
                $ayId
            );
        }
    }

    /**
     * Upsert an event into the calendar.
     */
    private function upsertEvent(string $title, string $start, string $end, string $type, ?int $academicYearId): void
    {
        Event::updateOrCreate(
            [
                'title' => $title,
                'start_date' => $start,
                'end_date' => $end,
                'academic_year_id' => $academicYearId,
            ],
            [
                'type' => $type,
                'is_all_day' => true,
                'is_active' => true,
                'visibility' => 'public',
                'target_audience' => null,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * Ensure midterm dates are provided as a pair and ordered.
     */
    private function validateMidtermPairing(Request $request): void
    {
        $midtermStart = $request->midterm_start_date ? Carbon::parse($request->midterm_start_date) : null;
        $midtermEnd = $request->midterm_end_date ? Carbon::parse($request->midterm_end_date) : null;

        if ($midtermStart xor $midtermEnd) {
            throw ValidationException::withMessages([
                'midterm_start_date' => 'Provide both midterm start and end dates.',
                'midterm_end_date' => 'Provide both midterm start and end dates.',
            ]);
        }

        if ($midtermStart && $midtermEnd && $midtermEnd->lt($midtermStart)) {
            throw ValidationException::withMessages([
                'midterm_end_date' => 'Midterm end date must be on or after the midterm start date.',
            ]);
        }
    }

    /**
     * Enforce chronological integrity of terms within and across academic years.
     */
    private function assertTermChronology(Carbon $opening, Carbon $closing, int $academicYearId, ?int $termId = null): void
    {
        // Prevent overlap with existing terms in the same academic year
        $overlap = Term::where('academic_year_id', $academicYearId)
            ->when($termId, fn($q) => $q->where('id', '!=', $termId))
            ->where(function ($q) use ($opening, $closing) {
                $q->whereBetween('opening_date', [$opening, $closing])
                  ->orWhereBetween('closing_date', [$opening, $closing])
                  ->orWhere(function ($sub) use ($opening, $closing) {
                      $sub->where('opening_date', '<=', $opening)
                          ->where('closing_date', '>=', $closing);
                  });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'opening_date' => 'Term dates overlap with another term in the same academic year.',
            ]);
        }

        // Ensure this term starts after the previous term closes
        $previous = Term::where('academic_year_id', $academicYearId)
            ->when($termId, fn($q) => $q->where('id', '!=', $termId))
            ->whereNotNull('closing_date')
            ->whereDate('closing_date', '<=', $opening)
            ->orderByDesc('closing_date')
            ->first();

        if ($previous && Carbon::parse($previous->closing_date)->gte($opening)) {
            throw ValidationException::withMessages([
                'opening_date' => 'Opening date must be after the previous term\'s closing date.',
            ]);
        }

        // Link with previous academic year's final term to keep holiday gaps consistent
        $currentYear = AcademicYear::find($academicYearId);
        if ($currentYear && $currentYear->year) {
            $previousYear = AcademicYear::where('year', '<', $currentYear->year)
                ->orderByDesc('year')
                ->first();

            if ($previousYear) {
                $previousYearLastTerm = Term::where('academic_year_id', $previousYear->id)
                    ->whereNotNull('closing_date')
                    ->orderByDesc('closing_date')
                    ->first();

                if ($previousYearLastTerm && Carbon::parse($previousYearLastTerm->closing_date)->gte($opening)) {
                    throw ValidationException::withMessages([
                        'opening_date' => 'Opening date must be after the last term of the previous academic year.',
                    ]);
                }
            }
        }
    }

    // --------- Term Holiday Management ---------
    public function termHolidays(Request $request)
    {
        $academicYears = AcademicYear::with('terms')->orderByDesc('year')->get();
        $academicYearId = $request->get('academic_year_id', $academicYears->first()?->id);
        $terms = Term::where('academic_year_id', $academicYearId)->orderBy('opening_date')->get();
        $termId = $request->get('term_id', $terms->first()?->id);
        $selectedTerm = $terms->firstWhere('id', $termId);

        $holidays = collect();
        if ($selectedTerm && $selectedTerm->opening_date && $selectedTerm->closing_date) {
            $holidays = SchoolDay::whereBetween('date', [
                Carbon::parse($selectedTerm->opening_date)->toDateString(),
                Carbon::parse($selectedTerm->closing_date)->toDateString(),
            ])
            ->whereIn('type', [
                SchoolDay::TYPE_HOLIDAY,
                SchoolDay::TYPE_CUSTOM_OFF_DAY,
                SchoolDay::TYPE_MIDTERM_BREAK,
            ])
            ->orderBy('date')
            ->get();
        }

        return view('settings.academic.term_holidays', compact(
            'academicYears',
            'academicYearId',
            'terms',
            'termId',
            'selectedTerm',
            'holidays'
        ));
    }

    public function storeTermHoliday(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'date' => 'required|date',
            'type' => 'required|in:holiday,custom_off_day,midterm_break',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $term = Term::findOrFail($request->term_id);
        $this->ensureDateWithinTerm($term, Carbon::parse($request->date));

        SchoolDay::updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'is_custom' => true,
            ]
        );

        // Keep events in sync for visibility
        $this->upsertEvent(
            $request->name,
            $date->toDateString(),
            $date->toDateString(),
            'holiday',
            $term->academic_year_id
        );

        return back()
            ->with('success', 'Holiday saved for the term.')
            ->withInput(['academic_year_id' => $request->academic_year_id, 'term_id' => $term->id]);
    }

    public function updateTermHoliday(Request $request, SchoolDay $schoolDay)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'date' => 'required|date',
            'type' => 'required|in:holiday,custom_off_day,midterm_break',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $term = Term::findOrFail($request->term_id);
        $this->ensureDateWithinTerm($term, Carbon::parse($request->date));

        $schoolDay->update([
            'date' => Carbon::parse($request->date)->toDateString(),
            'type' => $request->type,
            'name' => $request->name,
            'description' => $request->description,
            'is_custom' => $schoolDay->is_kenyan_holiday ? false : true,
        ]);

        // Keep events in sync
        $this->upsertEvent(
            $request->name,
            $schoolDay->date->toDateString(),
            $schoolDay->date->toDateString(),
            'holiday',
            $term->academic_year_id
        );

        return back()
            ->with('success', 'Holiday updated for the term.')
            ->withInput(['academic_year_id' => $request->academic_year_id, 'term_id' => $term->id]);
    }

    private function ensureDateWithinTerm(Term $term, Carbon $date): void
    {
        if (!$term->opening_date || !$term->closing_date) {
            throw ValidationException::withMessages([
                'date' => 'Set the term opening and closing dates before managing holidays.',
            ]);
        }

        if ($date->lt(Carbon::parse($term->opening_date)) || $date->gt(Carbon::parse($term->closing_date))) {
            throw ValidationException::withMessages([
                'date' => 'Holiday must fall within the selected term dates.',
            ]);
        }
    }
}

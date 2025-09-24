<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class AcademicConfigController extends Controller
{
    public function index()
    {
        $years = AcademicYear::with(['terms' => function ($q) {
                // Sort terms numerically by the number after "Term "
                $q->orderByRaw('CAST(SUBSTRING(name, 6) AS UNSIGNED) ASC');
            }])
            // Sort years numerically by year column
            ->orderByRaw('CAST(year AS UNSIGNED) DESC')
            ->get();

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
        return view('settings.academic.create_term', compact('years'));
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
        ]);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            // Only one current term per academic year
            Term::where('academic_year_id', $request->academic_year_id)
                ->update(['is_current' => false]);
        }

        Term::create([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'is_current' => $isCurrent,
        ]);

        return redirect()->route('settings.academic.index')
            ->with('success', 'Term created successfully.');
    }

    public function updateTerm(Request $request, Term $term)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_current' => 'nullable|boolean',
        ]);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            // Deactivate ALL terms (across all years)
            Term::query()->update(['is_current' => false]);
        }

        $term->update([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'is_current' => $isCurrent,
        ]);

        return redirect()->route('settings.academic.index')
            ->with('success', 'Term updated successfully.');
    }
    public function destroyTerm(Term $term)
    {
        $term->delete();
        return redirect()->route('settings.academic.index')->with('success', 'Term deleted.');
    }
}

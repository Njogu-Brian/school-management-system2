<?php

namespace App\Http\Controllers;

use App\Models\TermDay;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class TermDayController extends Controller
{
    public function index()
    {
        $termDays = TermDay::with('academicYear', 'term')->orderBy('opening_date', 'desc')->get();
        $academicYears = AcademicYear::orderBy('year', 'desc')->get();
        $terms = Term::orderBy('name')->get();
        
        return view('settings.term_days.index', compact('termDays', 'academicYears', 'terms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'opening_date' => 'required|date',
            'closing_date' => 'required|date|after:opening_date',
            'expected_school_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        TermDay::create($request->all());

        return back()->with('success', 'Term days record created successfully.');
    }

    public function update(Request $request, TermDay $termDay)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'opening_date' => 'required|date',
            'closing_date' => 'required|date|after:opening_date',
            'expected_school_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $termDay->update($request->all());

        return back()->with('success', 'Term days record updated successfully.');
    }

    public function destroy(TermDay $termDay)
    {
        $termDay->delete();
        return back()->with('success', 'Term days record deleted successfully.');
    }
}

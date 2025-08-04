<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index()
    {
        $terms = Term::with('academicYear')->get();
        return view('settings.terms.index', compact('terms'));
    }

    public function create()
    {
        $years = AcademicYear::orderByDesc('year')->get();
        return view('settings.terms.create', compact('years'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_current' => 'nullable|boolean',
        ]);

        if ($request->has('is_current')) {
            Term::where('academic_year_id', $request->academic_year_id)->update(['is_current' => false]);
        }

        Term::create([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'is_current' => $request->has('is_current'),
        ]);

        return redirect()->route('terms.index')->with('success', 'Term created.');
    }
}

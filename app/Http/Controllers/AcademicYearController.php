<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = AcademicYear::orderByDesc('year')->get();
        return view('settings.academic_years.index', compact('years'));
    }

    public function create()
    {
        return view('settings.academic_years.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|digits:4|unique:academic_years,year',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('is_active')) {
            AcademicYear::query()->update(['is_active' => false]); // Only one active
        }

        AcademicYear::create([
            'year' => $request->year,
            'is_active' => $request->has('is_active'),
        ]);

        // ✅ Fixed: use settings.academic.years.index
        return redirect()->route('settings.academic.years.index')
            ->with('success', 'Academic year created.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        // ✅ Fixed: use settings.academic.years.index
        return redirect()->route('settings.academic.years.index')
            ->with('success', 'Deleted successfully.');
    }
}

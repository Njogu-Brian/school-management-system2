<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeConcession;
use App\Models\Student;
use App\Models\Votehead;
use Illuminate\Http\Request;

class FeeConcessionController extends Controller
{
    public function index(Request $request)
    {
        $query = FeeConcession::with(['student', 'votehead', 'approver', 'creator']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $concessions = $query->latest()->paginate(20)->withQueryString();
        return view('finance.fee_concessions.index', compact('concessions'));
    }

    public function create()
    {
        $students = Student::with('classroom')->orderBy('first_name')->get();
        $voteheads = Votehead::orderBy('name')->get();
        return view('finance.fee_concessions.create', compact('students', 'voteheads'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'votehead_id' => 'nullable|exists:voteheads,id',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validated['type'] === 'percentage' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Percentage cannot exceed 100%']);
        }

        $concession = FeeConcession::create([
            'student_id' => $validated['student_id'],
            'votehead_id' => $validated['votehead_id'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('finance.fee-concessions.index')
            ->with('success', 'Fee concession created successfully.');
    }

    public function show(FeeConcession $feeConcession)
    {
        $feeConcession->load(['student', 'votehead', 'approver', 'creator']);
        return view('finance.fee_concessions.show', compact('feeConcession'));
    }

    public function approve(FeeConcession $feeConcession)
    {
        $feeConcession->update([
            'is_active' => true,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Fee concession approved.');
    }

    public function deactivate(FeeConcession $feeConcession)
    {
        $feeConcession->update(['is_active' => false]);
        return back()->with('success', 'Fee concession deactivated.');
    }
}

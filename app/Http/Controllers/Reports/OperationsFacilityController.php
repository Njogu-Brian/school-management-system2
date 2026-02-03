<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\OperationsFacility;
use Illuminate\Http\Request;

class OperationsFacilityController extends Controller
{
    public function index(Request $request)
    {
        $reports = OperationsFacility::query()
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('week_ending')
            ->limit(200)
            ->get();

        return view('reports.operations_facilities.index', compact('reports'));
    }

    public function create()
    {
        return view('reports.operations_facilities.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_ending' => 'required|date',
            'campus' => 'nullable|in:lower,upper',
            'area' => 'required|string|max:100',
            'status' => 'nullable|in:Good,Fair,Poor',
            'issue_noted' => 'nullable|string',
            'action_needed' => 'nullable|string',
            'responsible_person' => 'nullable|string|max:100',
            'resolved' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        OperationsFacility::create($data);

        return redirect()->route('reports.operations-facilities.index')
            ->with('success', 'Operations & facilities report saved successfully.');
    }
}

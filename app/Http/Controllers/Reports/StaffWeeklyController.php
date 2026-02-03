<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\StaffWeekly;
use App\Models\Staff;
use Illuminate\Http\Request;

class StaffWeeklyController extends Controller
{
    public function index(Request $request)
    {
        $reports = StaffWeekly::with('staff')
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('week_ending')
            ->limit(200)
            ->get();

        return view('reports.staff_weekly.index', compact('reports'));
    }

    public function create()
    {
        $staff = Staff::orderBy('first_name')->get();

        return view('reports.staff_weekly.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_ending' => 'required|date',
            'campus' => 'nullable|in:lower,upper',
            'staff_id' => 'required|exists:staff,id',
            'on_time_all_week' => 'nullable|boolean',
            'lessons_missed' => 'nullable|integer|min:0',
            'books_marked' => 'nullable|boolean',
            'schemes_updated' => 'nullable|boolean',
            'class_control' => 'nullable|in:Good,Fair,Poor',
            'general_performance' => 'nullable|in:Excellent,Good,Fair,Poor',
            'notes' => 'nullable|string',
        ]);

        StaffWeekly::create($data);

        return redirect()->route('reports.staff-weekly.index')
            ->with('success', 'Weekly staff report saved successfully.');
    }
}

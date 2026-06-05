<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitorLogController extends Controller
{
    public function index(Request $request)
    {
        $query = VisitorLog::with('hostStaff')->orderByDesc('checked_in_at');

        if ($request->boolean('on_site')) {
            $query->whereNull('checked_out_at');
        }

        $visitors = $query->paginate(30);
        $onSiteCount = VisitorLog::whereNull('checked_out_at')->count();

        return view('operations.visitors.index', compact('visitors', 'onSiteCount'));
    }

    public function create()
    {
        $staff = Staff::orderBy('first_name')->get();

        return view('operations.visitors.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:100',
            'organization' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:500',
            'host_name' => 'nullable|string|max:255',
            'host_staff_id' => 'nullable|exists:staff,id',
            'badge_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        VisitorLog::create([
            ...$validated,
            'checked_in_at' => now(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('operations.visitors.index')
            ->with('success', 'Visitor checked in successfully.');
    }

    public function checkout(VisitorLog $visitor)
    {
        if ($visitor->checked_out_at) {
            return back()->with('error', 'Visitor already checked out.');
        }

        $visitor->update(['checked_out_at' => now()]);

        return back()->with('success', 'Visitor checked out.');
    }
}

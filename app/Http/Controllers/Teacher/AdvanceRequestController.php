<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StaffAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdvanceRequestController extends Controller
{
    protected function currentStaff()
    {
        $staff = Auth::user()->staff;
        if (!$staff) {
            abort(403, 'No staff profile linked to your account.');
        }

        return $staff;
    }

    public function index()
    {
        $staff = $this->currentStaff();
        $advances = StaffAdvance::with(['approvedBy'])
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('teacher.advances.index', compact('advances', 'staff'));
    }

    public function create()
    {
        $staff = $this->currentStaff();
        return view('teacher.advances.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $staff = $this->currentStaff();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'advance_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Staff may only request a specific amount — installment plans are admin-only.
        $payload = [
            'amount' => $validated['amount'],
            'requested_amount' => $validated['amount'],
            'purpose' => $validated['purpose'] ?? null,
            'description' => $validated['description'] ?? null,
            'advance_date' => $validated['advance_date'],
            'notes' => $validated['notes'] ?? null,
            'repayment_method' => 'lump_sum',
            'installment_count' => null,
            'monthly_deduction_amount' => null,
            'staff_id' => $staff->id,
            'balance' => $validated['amount'],
            'amount_repaid' => 0,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ];

        StaffAdvance::create($payload);

        return redirect()->route($this->advanceRoutePrefix() . '.index')
            ->with('success', 'Advance request submitted for approval.');
    }

    private function advanceRoutePrefix(): string
    {
        return request()->routeIs('senior_teacher.*') ? 'senior_teacher.advances' : 'teacher.advances';
    }
}


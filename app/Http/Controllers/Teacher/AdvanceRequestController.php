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
            'repayment_method' => 'required|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1|required_if:repayment_method,installments',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01|required_if:repayment_method,monthly_deduction',
            'expected_completion_date' => 'nullable|date|after_or_equal:advance_date',
            'notes' => 'nullable|string',
        ]);

        $payload = $validated;
        $payload['staff_id'] = $staff->id;
        $payload['balance'] = $payload['amount'];
        $payload['amount_repaid'] = 0;
        $payload['status'] = 'pending';
        $payload['created_by'] = Auth::id();

        if (
            $payload['repayment_method'] === 'monthly_deduction'
            && !$request->filled('expected_completion_date')
            && !empty($payload['monthly_deduction_amount'])
        ) {
            $months = (int) ceil($payload['amount'] / $payload['monthly_deduction_amount']);
            $payload['expected_completion_date'] = Carbon::parse($payload['advance_date'])->addMonths($months);
        }

        StaffAdvance::create($payload);

        return redirect()->route('teacher.advances.index')
            ->with('success', 'Advance request submitted for approval.');
    }
}


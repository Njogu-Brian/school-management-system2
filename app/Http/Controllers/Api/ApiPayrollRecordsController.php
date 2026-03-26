<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;

class ApiPayrollRecordsController extends Controller
{
    /**
     * Same broad HR access as web `hr` routes (payroll lives under /hr/payroll).
     */
    protected function assertPayrollApiAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Finance Officer', 'Accountant', 'Teacher',
        ])) {
            abort(403, 'You do not have permission to view payroll records.');
        }
    }

    public function index(Request $request)
    {
        $this->assertPayrollApiAccess($request);

        $user = $request->user();
        $privileged = $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Finance Officer', 'Accountant',
        ]);
        if ($user->hasRole('Teacher') && ! $privileged) {
            $ownStaffId = $user->staff?->id;
            if ($ownStaffId) {
                $request->merge(['staff_id' => $ownStaffId]);
            }
        }

        $request->validate([
            'staff_id' => 'nullable|integer|exists:staff,id',
            'status' => 'nullable|string|max:32',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->input('per_page', 20);

        $query = PayrollRecord::with(['staff', 'payrollPeriod']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginated = $query->orderByDesc('id')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($r) => $this->formatRecord($r))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    protected function formatRecord(PayrollRecord $r): array
    {
        $period = $r->payrollPeriod;
        $staff = $r->staff;
        $monthLabel = null;
        if ($period) {
            $monthLabel = sprintf('%04d-%02d', $period->year, $period->month);
        }

        $allowances = (float) $r->housing_allowance + (float) $r->transport_allowance
            + (float) $r->medical_allowance + (float) $r->other_allowances;

        return [
            'id' => $r->id,
            'staff_id' => $r->staff_id,
            'staff_name' => $staff ? $staff->full_name : null,
            'staff_employee_number' => $staff?->staff_id,
            'month' => $monthLabel,
            'period_name' => $period->period_name ?? null,
            'basic_salary' => (float) $r->basic_salary,
            'allowances' => $allowances,
            'deductions' => (float) $r->total_deductions,
            'gross_salary' => (float) $r->gross_salary,
            'net_salary' => (float) $r->net_salary,
            'status' => $r->status,
            'payment_date' => $r->paid_at?->toIso8601String(),
            'created_at' => $r->created_at->toIso8601String(),
            'updated_at' => $r->updated_at->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;

class ApiPayrollRecordsController extends Controller
{
    /**
     * Staff may view their own payslips; HR/finance/senior roles may list broadly.
     */
    protected function assertPayrollApiAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if (
            $user->hasTeacherLikeRole()
            || $user->hasAnyRole([
                'Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant',
            ])
        ) {
            return;
        }
        abort(403, 'You do not have permission to view payroll records.');
    }

    public function index(Request $request)
    {
        $this->assertPayrollApiAccess($request);

        $user = $request->user();
        $privileged = $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Finance Officer', 'Accountant',
        ]);

        $request->validate([
            'staff_id' => 'nullable|integer|exists:staff,id',
            'status' => 'nullable|string|max:32',
            'month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->input('per_page', 20);

        $query = PayrollRecord::with(['staff', 'payrollPeriod']);

        if ($user->hasTeacherLikeRole() && ! $privileged) {
            $ownStaffId = $user->staff?->id;
            if (! $ownStaffId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ]);
            }
            $query->where('staff_id', $ownStaffId);
        } elseif ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('month')) {
            [$year, $month] = array_map('intval', explode('-', $request->input('month')));
            $query->whereHas('payrollPeriod', function ($q) use ($year, $month) {
                $q->where('year', $year)->where('month', $month);
            });
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

    public function show(Request $request, int $id)
    {
        $this->assertPayrollApiAccess($request);

        $user = $request->user();
        $privileged = $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Finance Officer', 'Accountant',
        ]);

        $record = PayrollRecord::with(['staff', 'payrollPeriod'])->findOrFail($id);

        if ($user->hasTeacherLikeRole() && ! $privileged) {
            $ownStaffId = $user->staff?->id;
            if (! $ownStaffId || (int) $record->staff_id !== (int) $ownStaffId) {
                abort(403, 'You can only view your own payslip.');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRecordDetail($record),
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

    protected function formatRecordDetail(PayrollRecord $r): array
    {
        $base = $this->formatRecord($r);

        return array_merge($base, [
            'housing_allowance' => (float) $r->housing_allowance,
            'transport_allowance' => (float) $r->transport_allowance,
            'medical_allowance' => (float) $r->medical_allowance,
            'other_allowances' => (float) $r->other_allowances,
            'allowances_breakdown' => $r->allowances_breakdown,
            'nssf_deduction' => (float) $r->nssf_deduction,
            'nhif_deduction' => (float) $r->nhif_deduction,
            'shif_deduction' => (float) $r->shif_deduction,
            'paye_deduction' => (float) $r->paye_deduction,
            'housing_levy_deduction' => (float) $r->housing_levy_deduction,
            'other_deductions' => (float) $r->other_deductions,
            'deductions_breakdown' => $r->deductions_breakdown,
            'bonus' => (float) $r->bonus,
            'advance_deduction' => (float) $r->advance_deduction,
            'custom_deductions_total' => (float) $r->custom_deductions_total,
            'custom_deductions_breakdown' => $r->custom_deductions_breakdown,
            'days_worked' => $r->days_worked,
            'days_in_period' => $r->days_in_period,
            'payslip_number' => $r->payslip_number,
            'notes' => $r->notes,
            'adjustments_notes' => $r->adjustments_notes,
        ]);
    }
}

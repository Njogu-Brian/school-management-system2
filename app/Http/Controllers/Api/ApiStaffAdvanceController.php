<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAdvance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiStaffAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = StaffAdvance::with(['staff', 'sourceStaff', 'approvedBy', 'createdBy', 'installments']);

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance'])) {
            if ($request->filled('staff_id')) {
                $query->where('staff_id', $request->staff_id);
            }
        } else {
            $ownStaffId = $user->staff?->id;
            if (! $ownStaffId) {
                return response()->json(['success' => true, 'data' => ['data' => [], 'total' => 0]]);
            }
            $query->where('staff_id', $ownStaffId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($a) => $this->format($a))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $advance = StaffAdvance::with(['staff', 'sourceStaff', 'approvedBy', 'createdBy', 'installments'])->findOrFail($id);
        $this->authorizeView($request, $advance);

        return response()->json(['success' => true, 'data' => $this->format($advance)]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance']);

        if ($isAdmin) {
            $validated = $request->validate([
                'staff_id' => 'required|exists:staff,id',
                'amount' => 'required|numeric|min:0.01',
                'requested_amount' => 'nullable|numeric|min:0.01',
                'purpose' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'advance_date' => 'required|date',
                'repayment_method' => 'required|in:lump_sum,installments,monthly_deduction',
                'installment_count' => 'nullable|integer|min:1|required_if:repayment_method,installments',
                'monthly_deduction_amount' => 'nullable|numeric|min:0.01|required_if:repayment_method,monthly_deduction',
                'source_type' => 'required|in:company,staff',
                'source_staff_id' => 'nullable|exists:staff,id|required_if:source_type,staff',
                'repayment_start_year' => 'required|integer|min:2000|max:2100',
                'repayment_start_month' => 'required|integer|min:1|max:12',
                'expected_completion_date' => 'nullable|date|after_or_equal:advance_date',
                'notes' => 'nullable|string',
            ]);

            if (
                $validated['source_type'] === 'staff'
                && (int) ($validated['source_staff_id'] ?? 0) === (int) $validated['staff_id']
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Funding staff cannot be the same as the advance recipient.',
                ], 422);
            }
        } else {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'purpose' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'advance_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);
            $validated['staff_id'] = $user->staff?->id;
            if (! $validated['staff_id']) {
                return response()->json(['success' => false, 'message' => 'No staff profile linked.'], 422);
            }
            $validated['repayment_method'] = 'lump_sum';
            $validated['installment_count'] = null;
            $validated['monthly_deduction_amount'] = null;
            $validated['source_type'] = 'company';
            $validated['source_staff_id'] = null;
            $validated['repayment_start_year'] = null;
            $validated['repayment_start_month'] = null;
        }

        $requested = (float) ($validated['requested_amount'] ?? $validated['amount']);
        $issued = (float) $validated['amount'];

        if (($validated['repayment_method'] ?? '') === 'lump_sum') {
            $validated['installment_count'] = 1;
        }

        $payload = [
            'staff_id' => $validated['staff_id'],
            'amount' => $issued,
            'requested_amount' => $requested,
            'purpose' => $validated['purpose'] ?? null,
            'description' => $validated['description'] ?? null,
            'advance_date' => $validated['advance_date'],
            'repayment_method' => $validated['repayment_method'],
            'installment_count' => $validated['installment_count'] ?? null,
            'monthly_deduction_amount' => $validated['monthly_deduction_amount'] ?? null,
            'source_type' => $validated['source_type'] ?? 'company',
            'source_staff_id' => ($validated['source_type'] ?? 'company') === 'staff'
                ? ($validated['source_staff_id'] ?? null)
                : null,
            'repayment_start_year' => $validated['repayment_start_year'] ?? null,
            'repayment_start_month' => $validated['repayment_start_month'] ?? null,
            'expected_completion_date' => $validated['expected_completion_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'balance' => $issued,
            'amount_repaid' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ];

        if (
            $payload['repayment_method'] === 'monthly_deduction'
            && empty($payload['expected_completion_date'])
            && ! empty($payload['monthly_deduction_amount'])
        ) {
            $months = (int) ceil($payload['amount'] / $payload['monthly_deduction_amount']);
            $payload['expected_completion_date'] = Carbon::parse($payload['advance_date'])->addMonths($months);
        }

        $advance = StaffAdvance::create($payload);
        $advance->load(['staff', 'sourceStaff', 'createdBy', 'installments']);

        return response()->json([
            'success' => true,
            'message' => 'Advance request created.',
            'data' => $this->format($advance),
        ], 201);
    }

    public function approve(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance'])) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $advance = StaffAdvance::findOrFail($id);
        if ($advance->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending advances can be approved.'], 422);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'repayment_method' => 'nullable|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01',
            'source_type' => 'required|in:company,staff',
            'source_staff_id' => 'nullable|exists:staff,id|required_if:source_type,staff',
            'repayment_start_year' => 'required|integer|min:2000|max:2100',
            'repayment_start_month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);

        if (
            $validated['source_type'] === 'staff'
            && (int) ($validated['source_staff_id'] ?? 0) === (int) $advance->staff_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Funding staff cannot be the same as the advance recipient.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if (! $advance->requested_amount) {
                $advance->requested_amount = $advance->amount;
            }

            if (isset($validated['amount'])) {
                $advance->amount = (float) $validated['amount'];
                $advance->balance = $advance->amount - (float) $advance->amount_repaid;
            }

            if (! empty($validated['repayment_method'])) {
                $advance->repayment_method = $validated['repayment_method'];
            }
            if (array_key_exists('installment_count', $validated)) {
                $advance->installment_count = $validated['installment_count'];
            }
            if (array_key_exists('monthly_deduction_amount', $validated)) {
                $advance->monthly_deduction_amount = $validated['monthly_deduction_amount'];
            }

            $advance->source_type = $validated['source_type'];
            $advance->source_staff_id = $validated['source_type'] === 'staff'
                ? $validated['source_staff_id']
                : null;
            $advance->repayment_start_year = $validated['repayment_start_year'];
            $advance->repayment_start_month = $validated['repayment_start_month'];

            if ($advance->repayment_method === 'lump_sum') {
                $advance->installment_count = 1;
            }

            if (! empty($validated['notes'])) {
                $advance->notes = trim(($advance->notes ? $advance->notes."\n" : '').$validated['notes']);
            }

            $advance->approved_by = $user->id;
            $advance->approved_at = now();
            $advance->status = 'active';
            $advance->save();
            $advance->generateInstallmentSchedule();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $advance->refresh()->load(['staff', 'sourceStaff', 'approvedBy', 'createdBy', 'installments']);

        return response()->json([
            'success' => true,
            'message' => 'Advance approved.',
            'data' => $this->format($advance),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance'])) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $advance = StaffAdvance::findOrFail($id);
        if ($advance->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending advances can be rejected.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $advance->status = 'cancelled';
        $advance->notes = trim(($advance->notes ? $advance->notes."\n" : '').'Rejected: '.$validated['reason']);
        $advance->save();

        return response()->json([
            'success' => true,
            'message' => 'Advance rejected.',
            'data' => $this->format($advance->fresh(['staff', 'sourceStaff', 'createdBy', 'installments'])),
        ]);
    }

    protected function authorizeView(Request $request, StaffAdvance $advance): void
    {
        $user = $request->user();
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance'])) {
            return;
        }
        if ((int) $user->staff?->id !== (int) $advance->staff_id) {
            abort(403, 'Not allowed.');
        }
    }

    protected function format(StaffAdvance $a): array
    {
        $requested = (float) ($a->requested_amount ?? $a->amount);
        $issued = (float) $a->amount;

        return [
            'id' => $a->id,
            'staff_id' => $a->staff_id,
            'staff_name' => $a->staff?->full_name ?? $a->staff?->name,
            'amount' => $issued,
            'requested_amount' => $requested,
            'amount_rejected' => max(0, round($requested - $issued, 2)),
            'purpose' => $a->purpose,
            'description' => $a->description,
            'advance_date' => optional($a->advance_date)->format('Y-m-d'),
            'repayment_method' => $a->repayment_method,
            'installment_count' => $a->installment_count,
            'monthly_deduction_amount' => $a->monthly_deduction_amount !== null
                ? (float) $a->monthly_deduction_amount
                : null,
            'source_type' => $a->source_type ?? 'company',
            'source_staff_id' => $a->source_staff_id,
            'source_staff_name' => $a->sourceStaff?->full_name ?? $a->sourceStaff?->name,
            'source_label' => $a->sourceLabel(),
            'repayment_start_year' => $a->repayment_start_year,
            'repayment_start_month' => $a->repayment_start_month,
            'installments' => $a->relationLoaded('installments')
                ? $a->installments->map(fn ($row) => [
                    'sequence' => $row->sequence,
                    'year' => $row->year,
                    'month' => $row->month,
                    'amount' => (float) $row->amount,
                    'status' => $row->status,
                ])->values()->all()
                : [],
            'amount_repaid' => (float) $a->amount_repaid,
            'balance' => (float) $a->balance,
            'status' => $a->status,
            'expected_completion_date' => optional($a->expected_completion_date)->format('Y-m-d'),
            'notes' => $a->notes,
            'approved_at' => optional($a->approved_at)?->toIso8601String(),
            'created_at' => optional($a->created_at)?->toIso8601String(),
        ];
    }
}

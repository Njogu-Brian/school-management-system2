<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\StaffLeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLeaveRequestController extends Controller
{
    public function leaveTypes(Request $request)
    {
        $types = LeaveType::active()->orderBy('name')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'code' => $t->code,
            'max_days' => $t->max_days,
            'is_paid' => (bool) $t->is_paid,
            'requires_approval' => (bool) $t->requires_approval,
        ])->values();

        return response()->json(['success' => true, 'data' => $types]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = LeaveRequest::with(['staff', 'leaveType', 'approvedBy', 'rejectedBy']);

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if ($request->filled('staff_id')) {
                $query->where('staff_id', $request->staff_id);
            }
        } elseif (is_supervisor() && ! $user->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateIds = get_subordinate_staff_ids();
            if (! empty($subordinateIds)) {
                $query->whereIn('staff_id', $subordinateIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            $ownStaffId = $user->staff?->id;
            if (! $ownStaffId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => [],
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 20,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ]);
            }
            $query->where('staff_id', $ownStaffId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($lr) => $this->formatLeave($lr))->values();

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

    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ];

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            $rules['staff_id'] = 'required|exists:staff,id';
        }

        $validated = $request->validate($rules);

        $staffId = $validated['staff_id'] ?? $user->staff?->id;
        if (! $staffId) {
            return response()->json([
                'success' => false,
                'message' => 'No staff profile linked to this account.',
            ], 422);
        }

        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if ((int) $staffId !== (int) $user->staff?->id) {
                return response()->json(['success' => false, 'message' => 'You can only apply leave for yourself.'], 403);
            }
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysRequested = $this->calculateWorkingDays($startDate, $endDate);

        $currentYear = AcademicYear::where('is_active', true)->first();
        $balance = StaffLeaveBalance::where('staff_id', $staffId)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('academic_year_id', $currentYear?->id)
            ->first();

        if ($balance && $balance->remaining_days < $daysRequested) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient leave balance. Available: {$balance->remaining_days} days, requested: {$daysRequested} days.",
            ], 422);
        }

        $leaveRequest = LeaveRequest::create([
            'staff_id' => $staffId,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        $leaveRequest->load(['staff', 'leaveType']);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted.',
            'data' => $this->formatLeave($leaveRequest),
        ], 201);
    }

    public function approve(Request $request, int $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending requests can be approved.'], 422);
        }

        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']) && ! (is_supervisor() && is_my_subordinate($leaveRequest->staff_id))) {
            return response()->json(['success' => false, 'message' => 'Not allowed to approve this request.'], 403);
        }

        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            $currentYear = AcademicYear::where('is_active', true)->first();
            $balance = StaffLeaveBalance::firstOrCreate(
                [
                    'staff_id' => $leaveRequest->staff_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'academic_year_id' => $currentYear?->id,
                ],
                [
                    'entitlement_days' => 0,
                    'used_days' => 0,
                    'remaining_days' => 0,
                    'carried_forward' => 0,
                ]
            );

            $balance->used_days += $leaveRequest->days_requested;
            $balance->calculateRemaining();
            $balance->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $leaveRequest->refresh()->load(['staff', 'leaveType']);

        return response()->json([
            'success' => true,
            'message' => 'Leave approved.',
            'data' => $this->formatLeave($leaveRequest),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending requests can be rejected.'], 422);
        }

        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']) && ! (is_supervisor() && is_my_subordinate($leaveRequest->staff_id))) {
            return response()->json(['success' => false, 'message' => 'Not allowed to reject this request.'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $leaveRequest->load(['staff', 'leaveType']);

        return response()->json([
            'success' => true,
            'message' => 'Leave rejected.',
            'data' => $this->formatLeave($leaveRequest),
        ]);
    }

    protected function formatLeave(LeaveRequest $lr): array
    {
        return [
            'id' => $lr->id,
            'staff_id' => $lr->staff_id,
            'staff_name' => $lr->staff?->full_name,
            'leave_type' => $lr->leaveType?->code ?? 'other',
            'leave_type_name' => $lr->leaveType?->name,
            'leave_type_id' => $lr->leave_type_id,
            'start_date' => $lr->start_date->format('Y-m-d'),
            'end_date' => $lr->end_date->format('Y-m-d'),
            'days' => $lr->days_requested,
            'days_count' => $lr->days_requested,
            'reason' => $lr->reason,
            'status' => $lr->status,
            'created_at' => $lr->created_at->toIso8601String(),
            'updated_at' => $lr->updated_at->toIso8601String(),
        ];
    }

    protected function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();

        while ($current <= $end) {
            if ($current->dayOfWeek != Carbon::SATURDAY && $current->dayOfWeek != Carbon::SUNDAY) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Academics\LessonPlan;
use App\Models\OnlineAdmission;
use App\Models\Requisition;
use Illuminate\Http\Request;

/**
 * Unified approvals inbox for Admin mobile — aggregates leave, lesson plans, admissions.
 */
class ApiApprovalsController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $sourceType = $request->input('source_type', 'all');
        $perPage = min((int) $request->input('per_page', 50), 100);

        $items = [];

        if ($sourceType === 'all' || $sourceType === 'leave_request') {
            $items = array_merge($items, $this->leaveItems($status, $perPage));
        }
        if ($sourceType === 'all' || $sourceType === 'lesson_plan') {
            $items = array_merge($items, $this->lessonPlanItems($status, $perPage));
        }
        if ($sourceType === 'all' || $sourceType === 'online_admission') {
            $items = array_merge($items, $this->admissionItems($status, $perPage));
        }
        if ($sourceType === 'all' || $sourceType === 'requisition') {
            $items = array_merge($items, $this->requisitionItems($status, $perPage));
        }

        usort($items, function ($a, $b) {
            $pr = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            $pa = $pr[$a['priority']] ?? 3;
            $pb = $pr[$b['priority']] ?? 3;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return strcmp($b['requested_at'], $a['requested_at']);
        });

        $items = array_slice($items, 0, $perPage);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Request $request, string $compositeId)
    {
        [$type, $id] = $this->parseComposite($compositeId);
        $item = match ($type) {
            'leave_request' => $this->formatLeaveItem(LeaveRequest::with(['staff', 'leaveType'])->findOrFail((int) $id)),
            'lesson_plan' => $this->formatLessonPlanItem(
                LessonPlan::with(['subject', 'classroom', 'creator'])->findOrFail((int) $id)
            ),
            'online_admission' => $this->formatAdmissionItem(
                OnlineAdmission::with(['preferredClassroom', 'classroom'])->findOrFail((int) $id)
            ),
            'requisition' => $this->formatRequisitionItem(
                Requisition::with(['requestedBy', 'items'])->findOrFail((int) $id)
            ),
            default => null,
        };

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Unknown approval type.'], 404);
        }

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function approve(Request $request, string $compositeId)
    {
        [$type, $id] = $this->parseComposite($compositeId);

        return match ($type) {
            'leave_request' => app(ApiLeaveRequestController::class)->approve($request, (int) $id),
            'lesson_plan' => app(ApiLessonPlansController::class)->approve($request, (int) $id),
            'requisition' => app(ApiRequisitionController::class)->approve($request, (int) $id),
            default => response()->json([
                'success' => false,
                'message' => 'Approve not supported for this approval type in mobile.',
            ], 422),
        };
    }

    public function reject(Request $request, string $compositeId)
    {
        [$type, $id] = $this->parseComposite($compositeId);

        return match ($type) {
            'leave_request' => app(ApiLeaveRequestController::class)->reject($request, (int) $id),
            'lesson_plan' => app(ApiLessonPlansController::class)->reject($request, (int) $id),
            'requisition' => app(ApiRequisitionController::class)->reject($request, (int) $id),
            default => response()->json([
                'success' => false,
                'message' => 'Reject not supported for this approval type in mobile.',
            ], 422),
        };
    }

    protected function parseComposite(string $compositeId): array
    {
        $decoded = urldecode($compositeId);
        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            abort(404, 'Invalid approval id.');
        }

        return [$parts[0], $parts[1]];
    }

    protected function leaveItems(string $status, int $limit): array
    {
        $query = LeaveRequest::with(['staff', 'leaveType'])->orderByDesc('created_at');
        if ($status === 'approved' || $status === 'rejected') {
            $query->where('status', $status);
        } elseif ($status === 'pending') {
            $query->where('status', 'pending');
        }
        return $query->limit($limit)->get()->map(fn ($lr) => $this->formatLeaveItem($lr))->all();
    }

    protected function lessonPlanItems(string $status, int $limit): array
    {
        $query = LessonPlan::with(['subject', 'classroom', 'creator'])->orderByDesc('submitted_at');
        if ($status === 'approved') {
            $query->where('submission_status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('submission_status', 'rejected');
        } elseif ($status === 'pending') {
            $query->where('submission_status', 'submitted');
        }
        return $query->limit($limit)->get()->map(fn ($lp) => $this->formatLessonPlanItem($lp))->all();
    }

    protected function admissionItems(string $status, int $limit): array
    {
        $query = OnlineAdmission::with(['preferredClassroom', 'classroom'])->orderByDesc('application_date');
        if ($status === 'approved') {
            $query->where('application_status', 'enrolled');
        } elseif ($status === 'rejected') {
            $query->where('application_status', 'rejected');
        } elseif ($status === 'pending') {
            $query->whereIn('application_status', ['pending', 'under_review', 'waitlisted']);
        }
        return $query->limit($limit)->get()->map(fn ($a) => $this->formatAdmissionItem($a))->all();
    }

    protected function formatLeaveItem(LeaveRequest $lr): array
    {
        $approvalStatus = in_array($lr->status, ['approved', 'rejected'], true) ? $lr->status : 'pending';

        return [
            'id' => 'leave_request:'.$lr->id,
            'source_type' => 'leave_request',
            'source_id' => $lr->id,
            'title' => $lr->leaveType?->name ?? 'Leave request',
            'subtitle' => ($lr->staff?->full_name ?? 'Staff').' · '.($lr->days_requested ?? 0).' days',
            'status' => $approvalStatus,
            'priority' => 'medium',
            'requested_at' => $lr->created_at->toIso8601String(),
            'due_date' => $lr->end_date?->format('Y-m-d'),
            'requester_name' => $lr->staff?->full_name,
            'summary' => $lr->reason,
            'can_act' => $lr->status === 'pending',
        ];
    }

    protected function formatLessonPlanItem(LessonPlan $lp): array
    {
        $status = match ($lp->submission_status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return [
            'id' => 'lesson_plan:'.$lp->id,
            'source_type' => 'lesson_plan',
            'source_id' => $lp->id,
            'title' => $lp->title ?? 'Lesson plan',
            'subtitle' => trim(implode(' · ', array_filter([
                $lp->creator?->full_name,
                $lp->classroom?->name,
                $lp->subject?->name,
            ]))),
            'status' => $status,
            'priority' => $lp->is_late ? 'high' : 'medium',
            'requested_at' => ($lp->submitted_at ?? $lp->created_at)?->toIso8601String(),
            'due_date' => $lp->planned_date?->format('Y-m-d'),
            'requester_name' => $lp->creator?->full_name,
            'summary' => $lp->classroom?->name,
            'can_act' => $lp->submission_status === 'submitted',
        ];
    }

    protected function requisitionItems(string $status, int $limit): array
    {
        $query = Requisition::with(['requestedBy', 'items'])->orderByDesc('created_at');
        if ($status === 'approved') {
            $query->whereIn('status', ['approved', 'fulfilled']);
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        } elseif ($status === 'pending') {
            $query->where('status', 'pending');
        }

        return $query->limit($limit)->get()->map(fn ($r) => $this->formatRequisitionItem($r))->all();
    }

    protected function formatRequisitionItem(Requisition $r): array
    {
        $approvalStatus = match ($r->status) {
            'approved', 'fulfilled' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return [
            'id' => 'requisition:'.$r->id,
            'source_type' => 'requisition',
            'source_id' => $r->id,
            'title' => $r->requisition_number,
            'subtitle' => ($r->requestedBy?->name ?? 'Staff').' · '.$r->type,
            'status' => $approvalStatus,
            'priority' => 'medium',
            'requested_at' => ($r->requested_at ?? $r->created_at)?->toIso8601String(),
            'requester_name' => $r->requestedBy?->name,
            'summary' => $r->purpose,
            'can_act' => $r->status === 'pending',
        ];
    }

    protected function formatAdmissionItem(OnlineAdmission $a): array
    {
        $status = match ($a->application_status) {
            'enrolled' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        $fullName = trim(implode(' ', array_filter([$a->first_name, $a->middle_name, $a->last_name])));

        return [
            'id' => 'online_admission:'.$a->id,
            'source_type' => 'online_admission',
            'source_id' => $a->id,
            'title' => $fullName,
            'subtitle' => ($a->classroom?->name ?? $a->preferredClassroom?->name ?? '').' · '.$a->application_status,
            'status' => $status,
            'priority' => 'medium',
            'requested_at' => ($a->application_date ?? $a->created_at)?->toIso8601String(),
            'requester_name' => $fullName,
            'summary' => $a->application_source,
            'can_act' => false,
        ];
    }
}

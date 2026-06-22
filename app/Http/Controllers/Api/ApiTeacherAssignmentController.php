<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Academics\TeacherAssignmentService;
use Illuminate\Http\Request;

class ApiTeacherAssignmentController extends Controller
{
    public function __construct(
        private readonly TeacherAssignmentService $assignmentService,
    ) {}

    protected function assertManageAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator', 'academic administrator'])) {
            abort(403, 'You do not have permission to manage teacher assignments.');
        }
    }

    /**
     * GET /api/teacher-assignments/stream-slots
     */
    public function streamSlots(Request $request)
    {
        $this->assertManageAccess($request);

        $slots = $this->assignmentService->getStreamSlots()->map(fn ($s) => [
            'key' => $s->key,
            'classroom_id' => $s->classroom_id,
            'stream_id' => $s->stream_id,
            'label' => $s->label,
            'subjects' => $this->assignmentService
                ->getSubjectsForSlot($s->classroom_id, $s->stream_id)
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'subject_id' => $sub->subject_id,
                    'name' => $sub->name,
                    'code' => $sub->code,
                ])
                ->values(),
        ]);

        return response()->json(['data' => $slots->values()]);
    }

    /**
     * GET /api/staff/{id}/teaching-assignments
     */
    public function show(Request $request, int $id)
    {
        $this->assertManageAccess($request);

        $staff = Staff::with('user.roles')->findOrFail($id);

        return response()->json([
            'data' => [
                'staff_id' => $staff->id,
                'staff_name' => $staff->full_name,
                'has_teaching_role' => $this->assignmentService->staffHasTeachingRole($staff),
                'assignments' => $this->assignmentService->getAssignmentsForStaff($staff->id),
            ],
        ]);
    }

    /**
     * PUT /api/staff/{id}/teaching-assignments
     */
    public function update(Request $request, int $id)
    {
        $this->assertManageAccess($request);

        Staff::findOrFail($id);

        $data = $request->validate([
            'slots' => 'nullable|array',
            'slots.*.classroom_id' => 'required|integer|exists:classrooms,id',
            'slots.*.stream_id' => 'nullable|integer|exists:streams,id',
            'slots.*.is_class_teacher' => 'nullable|boolean',
            'slots.*.is_assistant_teacher' => 'nullable|boolean',
            'slots.*.subject_ids' => 'nullable|array',
            'slots.*.subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $slotPayloads = [];
        foreach ($data['slots'] ?? [] as $slot) {
            $slotPayloads[] = [
                'classroom_id' => (int) $slot['classroom_id'],
                'stream_id' => $slot['stream_id'] ?? null,
                'is_class_teacher' => ! empty($slot['is_class_teacher']),
                'is_assistant_teacher' => ! empty($slot['is_assistant_teacher']),
                'subject_ids' => array_map('intval', $slot['subject_ids'] ?? []),
            ];
        }

        $this->assignmentService->saveAssignmentsForStaff($id, $slotPayloads);

        return response()->json([
            'message' => 'Teaching assignments saved.',
            'data' => $this->assignmentService->getAssignmentsForStaff($id),
        ]);
    }
}

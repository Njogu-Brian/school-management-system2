<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Academics\TeacherAssignmentService;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function __construct(
        private readonly TeacherAssignmentService $assignmentService,
    ) {
        $this->middleware('permission:subjects.edit')->only(['edit', 'update']);
    }

    /**
     * Teacher-centric assignment hub (pick a teacher to manage all roles at once).
     */
    public function index()
    {
        $staffTeachers = $this->assignmentService->getTeachingStaff();

        return view('academics.teacher_assignments.index', compact('staffTeachers'));
    }

    /**
     * Unified form: streams, subjects, class teacher, assistant teacher.
     */
    public function edit(Staff $staff)
    {
        $streamSlots = $this->assignmentService->getStreamSlots();
        $assignments = $this->assignmentService->getAssignmentsForStaff($staff->id);

        $subjectsBySlot = [];
        foreach ($streamSlots as $slot) {
            $subjectsBySlot[$slot->key] = $this->assignmentService
                ->getSubjectsForSlot($slot->classroom_id, $slot->stream_id)
                ->all();
        }

        $selectedSlotKeys = collect($assignments['slots'])->pluck('key')->all();
        $slotData = collect($assignments['slots'])->keyBy('key')->all();

        return view('academics.teacher_assignments.edit', compact(
            'staff',
            'streamSlots',
            'subjectsBySlot',
            'selectedSlotKeys',
            'slotData',
        ));
    }

    public function update(Request $request, Staff $staff)
    {
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

        $this->assignmentService->saveAssignmentsForStaff($staff->id, $slotPayloads);

        $redirect = $request->input('redirect_to');
        if ($redirect === 'staff.edit') {
            return redirect()
                ->route('staff.edit', $staff->id)
                ->with('success', 'Teaching assignments updated for ' . $staff->full_name . '.');
        }

        return redirect()
            ->route('academics.teacher-assignments.edit', $staff->id)
            ->with('success', 'Teaching assignments saved successfully.');
    }
}

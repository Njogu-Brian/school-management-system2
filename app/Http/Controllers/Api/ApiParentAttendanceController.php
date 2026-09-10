<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReasonCode;
use App\Models\Student;
use App\Services\ParentAbsenceService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ApiParentAttendanceController extends Controller
{
    public function __construct(protected ParentAbsenceService $absence) {}

    public function reasonCodes(Request $request)
    {
        $codes = AttendanceReasonCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'requires_excuse', 'is_medical']);

        return response()->json([
            'success' => true,
            'data' => $codes,
        ]);
    }

    public function history(Request $request, Student $student)
    {
        $this->assertParentAccess($request, $student);

        $rows = $this->absence->historyForStudent($student)->map(fn ($a) => $this->serializeAttendance($a));

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $this->assertParentAccess($request, $student);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:3|max:1000',
            'reason_code_id' => 'nullable|integer|exists:attendance_reason_codes,id',
        ]);

        try {
            $result = $this->absence->reportAbsence(
                $student,
                $request->user(),
                $validated['start_date'],
                $validated['end_date'],
                $validated['reason'],
                isset($validated['reason_code_id']) ? (int) $validated['reason_code_id'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absence reported. The school has been notified.',
            'data' => [
                'school_days' => $result['school_days'],
                'skipped' => $result['skipped'],
                'records' => $result['records']->map(fn ($a) => $this->serializeAttendance($a))->values(),
            ],
        ]);
    }

    protected function serializeAttendance($attendance): array
    {
        return [
            'id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'date' => optional($attendance->date)->toDateString() ?? (string) $attendance->date,
            'status' => $attendance->status,
            'is_excused' => (bool) $attendance->is_excused,
            'reason' => $attendance->reason,
            'excuse_notes' => $attendance->excuse_notes,
            'reason_code_id' => $attendance->reason_code_id,
            'reason_code' => $attendance->reasonCode?->name,
            'marked_by' => $attendance->marked_by,
            'marked_at' => optional($attendance->marked_at)?->toIso8601String(),
        ];
    }

    protected function assertParentAccess(Request $request, Student $student): void
    {
        $user = $request->user();
        abort_unless($user, 403, 'You do not have access to this student.');

        // Absence report/history is always guardian-scoped (linked children only),
        // including dual-role Super Admin / staff accounts using Home mode.
        abort_unless(
            $user->canAccessStudent((int) $student->id),
            403,
            'You can only report absences for children linked to your parent account.'
        );
    }
}

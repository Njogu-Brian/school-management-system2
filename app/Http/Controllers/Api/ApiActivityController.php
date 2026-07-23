<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\ActivityFeeAttendance;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\SwimmingAttendance;
use App\Models\Votehead;
use App\Services\SwimmingAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mobile "Activities" endpoints for teachers.
 *
 * Maps two web-portal concepts behind one list so teachers can mark daily
 * attendance from the app:
 *   - Extra-curricular activity fees ({@see Votehead::scopeActivityFees()}, e.g.
 *     ballet, skating, yoghurt). Roster = students billed the activity optional
 *     fee for the current year/term; attendance is stored in
 *     `activity_fee_attendances` exactly like the web {@see \App\Http\Controllers\Activities\ActivityFeeController}.
 *   - Swimming ({@see \App\Http\Controllers\Swimming\SwimmingAttendanceController}),
 *     which is per-classroom and bills wallets on mark. We reuse
 *     {@see SwimmingAttendanceService::syncBulkAttendance()} so billing/refunds
 *     behave identically to the web store.
 *
 * Both stores are presence-based, so the P/A/L status the app sends is mapped to
 * attended/not-attended: `present` and `late` mark the student as attended,
 * `absent`/`unmarked` remove them. Reads return `present` for attended students.
 */
class ApiActivityController extends Controller
{
    public function __construct(
        protected SwimmingAttendanceService $swimmingAttendance,
    ) {
    }

    protected function currentYear(): int
    {
        return (int) setting('current_year', date('Y'));
    }

    protected function currentTerm(): int
    {
        return (int) setting('current_term', 1);
    }

    /**
     * GET /api/activities — activities the teacher can mark today.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $items = [];

        $voteheads = Votehead::query()->activityFees()->orderBy('name')->get();
        foreach ($voteheads as $votehead) {
            $items[] = [
                'id' => 'activity-' . $votehead->id,
                'type' => 'activity',
                'name' => $votehead->name,
                'classroom_id' => null,
                'classroom_name' => null,
                'fee_amount' => $votehead->fee_amount ?? null,
            ];
        }

        foreach ($this->accessibleClassrooms($user) as $classroom) {
            $items[] = [
                'id' => 'swimming-' . $classroom->id,
                'type' => 'swimming',
                'name' => 'Swimming · ' . $classroom->name,
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'fee_amount' => null,
            ];
        }

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * GET /api/activities/{activity}/students — roster for the activity.
     */
    public function students(Request $request, string $activity)
    {
        $parsed = $this->parseActivityId($activity);
        if (! $parsed) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }

        $user = $request->user();
        [$type, $refId] = $parsed;

        if ($type === 'swimming') {
            $classroom = Classroom::find($refId);
            if (! $classroom || ! $this->canAccessClassroom($user, $classroom->id)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
            }
            $students = $this->swimmingRosterQuery($user, $classroom)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            return response()->json(['success' => true, 'data' => $this->mapStudents($students)]);
        }

        $votehead = $this->resolveActivityVotehead($refId);
        if (! $votehead) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }
        $studentIds = $this->activityRosterStudentIds($user, $votehead);
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json(['success' => true, 'data' => $this->mapStudents($students)]);
    }

    /**
     * GET /api/activities/{activity}/attendance?date= — existing marks.
     */
    public function attendance(Request $request, string $activity)
    {
        $request->validate(['date' => 'nullable|date']);
        $parsed = $this->parseActivityId($activity);
        if (! $parsed) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }

        $user = $request->user();
        [$type, $refId] = $parsed;
        $date = Carbon::parse($request->get('date', now()->toDateString()))->toDateString();

        if ($type === 'swimming') {
            $classroom = Classroom::find($refId);
            if (! $classroom || ! $this->canAccessClassroom($user, $classroom->id)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
            }
            $records = SwimmingAttendance::where('classroom_id', $classroom->id)
                ->whereDate('attendance_date', $date)
                ->pluck('student_id')
                ->map(fn ($id) => ['student_id' => (int) $id, 'status' => 'present'])
                ->values();

            return response()->json(['success' => true, 'data' => $records]);
        }

        $votehead = $this->resolveActivityVotehead($refId);
        if (! $votehead) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }
        $records = ActivityFeeAttendance::where('votehead_id', $votehead->id)
            ->whereDate('attendance_date', $date)
            ->pluck('student_id')
            ->map(fn ($id) => ['student_id' => (int) $id, 'status' => 'present'])
            ->values();

        return response()->json(['success' => true, 'data' => $records]);
    }

    /**
     * POST /api/activities/{activity}/attendance — save attendance.
     * Body: { date, records: [{ student_id, status }] } with status present|absent|late|unmarked.
     */
    public function storeAttendance(Request $request, string $activity)
    {
        $request->validate([
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|integer|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,unmarked',
        ]);

        $parsed = $this->parseActivityId($activity);
        if (! $parsed) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }

        $user = $request->user();
        [$type, $refId] = $parsed;
        $date = Carbon::parse($request->date)->toDateString();

        // present/late count as attended; absent/unmarked are removed.
        $attendedIds = collect($request->records)
            ->filter(fn ($r) => in_array($r['status'], ['present', 'late'], true))
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($type === 'swimming') {
            $classroom = Classroom::find($refId);
            if (! $classroom || ! $this->canAccessClassroom($user, $classroom->id)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
            }
            $allowed = $this->swimmingRosterQuery($user, $classroom)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $attendedIds = array_values(array_intersect($attendedIds, $allowed));

            $results = $this->swimmingAttendance->syncBulkAttendance(
                $classroom,
                Carbon::parse($date),
                $attendedIds,
                $user,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Swimming attendance saved for ' . count($attendedIds) . ' student(s).',
                    'count' => count($attendedIds),
                    'marked' => count($results['marked'] ?? []),
                    'unmarked' => count($results['unmarked'] ?? []),
                ],
            ]);
        }

        $votehead = $this->resolveActivityVotehead($refId);
        if (! $votehead) {
            return response()->json(['success' => false, 'message' => 'Unknown activity.'], 404);
        }

        $allowed = $this->activityRosterStudentIds($user, $votehead)
            ->map(fn ($id) => (int) $id)
            ->all();
        $attendedIds = array_values(array_intersect($attendedIds, $allowed));

        DB::transaction(function () use ($votehead, $date, $attendedIds, $user) {
            ActivityFeeAttendance::query()
                ->where('votehead_id', $votehead->id)
                ->whereDate('attendance_date', $date)
                ->whereNotIn('student_id', $attendedIds ?: [0])
                ->delete();

            foreach ($attendedIds as $studentId) {
                ActivityFeeAttendance::query()->updateOrCreate(
                    [
                        'votehead_id' => $votehead->id,
                        'student_id' => $studentId,
                        'attendance_date' => $date,
                    ],
                    [
                        'marked_by' => $user->id,
                        'marked_at' => now(),
                    ],
                );
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Activity attendance saved for ' . count($attendedIds) . ' student(s).',
                'count' => count($attendedIds),
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: int}|null [type, refId]
     */
    protected function parseActivityId(string $activity): ?array
    {
        if (preg_match('/^(activity|swimming)-(\d+)$/', $activity, $m)) {
            return [$m[1], (int) $m[2]];
        }

        return null;
    }

    protected function resolveActivityVotehead(int $id): ?Votehead
    {
        return Votehead::query()
            ->where('id', $id)
            ->where('is_activity_fee', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Students billed the activity optional fee for the current year/term, scoped for teachers.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function activityRosterStudentIds($user, Votehead $votehead)
    {
        $q = OptionalFee::query()
            ->where('votehead_id', $votehead->id)
            ->where('year', $this->currentYear())
            ->where('term', $this->currentTerm())
            ->where('status', 'billed')
            ->whereHas('student', fn ($sq) => $sq->where('archive', 0));

        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Supervisor'])) {
            $q->whereHas('student', function ($sq) use ($user) {
                $user->applyTeacherStudentFilter($sq);
            });
        }

        return $q->pluck('student_id')->unique()->values();
    }

    protected function swimmingRosterQuery($user, Classroom $classroom)
    {
        $q = Student::query()
            ->where('classroom_id', $classroom->id)
            ->where('archive', 0);

        if (! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            $user->applyTeacherStudentFilter($q);
        }

        return $q;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Classroom>
     */
    protected function accessibleClassrooms($user)
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return Classroom::orderBy('name')->get();
        }

        $ids = array_unique(array_merge(
            $user->getAssignedClassroomIds(),
            $user->getSupervisedClassroomIds(),
        ));

        if (empty($ids)) {
            return collect();
        }

        return Classroom::whereIn('id', $ids)->orderBy('name')->get();
    }

    protected function canAccessClassroom($user, int $classroomId): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        $ids = array_unique(array_merge(
            $user->getAssignedClassroomIds(),
            $user->getSupervisedClassroomIds(),
        ));

        return in_array($classroomId, $ids);
    }

    protected function mapStudents($students): array
    {
        return $students->map(fn (Student $s) => [
            'id' => $s->id,
            'full_name' => $s->full_name,
            'admission_number' => $s->admission_number,
        ])->values()->all();
    }
}

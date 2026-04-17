<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Services\StudentBalanceService;
use Illuminate\Http\Request;

class ApiSeniorTeacherController extends Controller
{
    protected function ensureSeniorTeacherScope(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Senior Teacher', 'senior teacher', 'Senior teacher', 'Supervisor', 'supervisor'])) {
            abort(403, 'You do not have permission to view senior-teacher supervision data.');
        }
    }

    public function supervisedClassrooms(Request $request)
    {
        $this->ensureSeniorTeacherScope($request);
        $user = $request->user();

        $classroomIds = array_map('intval', $user->getSupervisedClassroomIds());
        if ($classroomIds === []) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $rows = Classroom::query()
            ->whereIn('id', $classroomIds)
            ->with(['primaryStreams', 'teachers'])
            ->orderBy('name')
            ->get()
            ->flatMap(function (Classroom $classroom) {
                $teacherName = optional($classroom->teachers->first())->name;
                $streams = $classroom->primaryStreams;

                if ($streams->isEmpty()) {
                    return [[
                        'id' => (int) $classroom->id,
                        'name' => $classroom->name,
                        'grade_level' => null,
                        'stream' => null,
                        'student_count' => (int) Student::where('classroom_id', $classroom->id)->count(),
                        'teacher_name' => $teacherName,
                    ]];
                }

                return $streams->map(function ($stream) use ($classroom, $teacherName) {
                    return [
                        // Keep row id unique by stream so mobile list keys do not collide.
                        'id' => (int) ($classroom->id * 100000 + $stream->id),
                        'name' => $classroom->name,
                        'grade_level' => null,
                        'stream' => $stream->name,
                        'student_count' => (int) Student::where('classroom_id', $classroom->id)
                            ->where('stream_id', $stream->id)
                            ->count(),
                        'teacher_name' => $teacherName,
                    ];
                });
            })
            ->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function supervisedStaff(Request $request)
    {
        $this->ensureSeniorTeacherScope($request);
        $user = $request->user();

        $staffIds = array_map('intval', $user->getSupervisedStaffIds());
        if ($staffIds === []) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $staff = Staff::query()
            ->whereIn('id', $staffIds)
            ->with(['department', 'jobTitle'])
            ->orderBy('first_name')
            ->get()
            ->map(function (Staff $s) {
                return [
                    'id' => (int) $s->id,
                    'staff_id' => $s->staff_id,
                    'full_name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    'email' => $s->work_email,
                    'designation' => $s->jobTitle?->name,
                    'department' => $s->department?->name,
                    'status' => $s->status,
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => $staff]);
    }

    public function feeBalances(Request $request)
    {
        $this->ensureSeniorTeacherScope($request);
        $user = $request->user();

        $classroomIds = array_values(array_unique(array_merge(
            array_map('intval', $user->getSupervisedClassroomIds()),
            array_map('intval', $user->getAssignedClassroomIds())
        )));

        if ($classroomIds === []) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $students = Student::query()
            ->whereIn('classroom_id', $classroomIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom'])
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $student) {
                return [
                    'student_id' => (int) $student->id,
                    'student_name' => trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                    'admission_number' => $student->admission_number,
                    'class_name' => $student->classroom?->name,
                    'balance' => round((float) StudentBalanceService::getTotalOutstandingBalance($student), 2),
                    'currency' => 'KES',
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => $students]);
    }

    public function supervisedStudents(Request $request)
    {
        $this->ensureSeniorTeacherScope($request);
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = $user->getSupervisedStudents()->with(['classroom', 'stream']);
        if ($request->filled('class_id')) {
            $query->where('classroom_id', (int) $request->input('class_id'));
        }

        $rows = $query->orderBy('first_name')->paginate($perPage);
        $data = $rows->getCollection()->map(function (Student $s) {
            return [
                'id' => (int) $s->id,
                'admission_number' => $s->admission_number,
                'full_name' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
                'status' => $s->archive ? 'archived' : 'active',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }

    /**
     * Fee clearance: pending list for supervised scope (privacy-safe: no amounts).
     */
    public function pendingFeeClearances(Request $request)
    {
        $this->ensureSeniorTeacherScope($request);

        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
            'class_id' => 'nullable|exists:classrooms,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $term = $request->term_id
            ? Term::find($request->term_id)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        if (!$term) {
            return response()->json(['success' => false, 'message' => 'No term found.'], 422);
        }

        $user = $request->user();
        $classroomIds = array_values(array_unique(array_merge(
            array_map('intval', $user->getSupervisedClassroomIds()),
            array_map('intval', $user->getAssignedClassroomIds())
        )));

        if ($classroomIds === []) {
            return response()->json(['success' => true, 'data' => ['data' => [], 'total' => 0]]);
        }

        if ($request->filled('class_id')) {
            $cid = (int) $request->class_id;
            $classroomIds = in_array($cid, $classroomIds, true) ? [$cid] : [];
        }

        if ($classroomIds === []) {
            return response()->json(['success' => true, 'data' => ['data' => [], 'total' => 0]]);
        }

        $perPage = (int) ($request->input('per_page', 50));

        $query = StudentTermFeeClearance::query()
            ->where('term_id', $term->id)
            ->where('status', 'pending')
            ->whereHas('student', function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds)
                    ->where('archive', 0)
                    ->where('is_alumni', false);
            })
            ->with(['student.classroom', 'student.stream'])
            ->orderByDesc('computed_at');

        $rows = $query->paginate($perPage);

        $data = $rows->getCollection()->map(function (StudentTermFeeClearance $c) {
            $s = $c->student;
            return [
                'student_id' => (int) $s->id,
                'student_name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'admission_number' => $s->admission_number,
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
                'status' => $c->status,
                'final_clearance_deadline' => $c->final_clearance_deadline?->toDateString(),
                'computed_at' => $c->computed_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }
}


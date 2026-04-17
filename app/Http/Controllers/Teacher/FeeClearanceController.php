<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Services\FeeClearanceStatusService;
use Illuminate\Http\Request;

class FeeClearanceController extends Controller
{
    public function __construct(protected FeeClearanceStatusService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $term = Term::where('is_current', true)->orderByDesc('id')->first();

        $assignedClassroomIds = $user->getAssignedClassroomIds();
        $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();

        $selectedClassId = (int) ($request->get('classroom_id') ?: (count($assignedClassroomIds) === 1 ? $assignedClassroomIds[0] : 0));
        $selectedStreamId = $request->filled('stream_id') ? (int) $request->get('stream_id') : null;

        $students = collect();
        $snapshots = collect();

        if ($selectedClassId) {
            if ($user->hasTeacherLikeRole() && ! $user->canTeacherAccessClassroom($selectedClassId)) {
                abort(403, 'You are not assigned to this class.');
            }

            $studentQuery = Student::where('classroom_id', $selectedClassId)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->when($selectedStreamId !== null, fn ($q) => $q->where('stream_id', $selectedStreamId));

            if ($user->hasTeacherLikeRole()) {
                $user->applyTeacherStudentFilter($studentQuery);
            }

            $students = $studentQuery
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            if ($term && $students->isNotEmpty()) {
                $snapshots = StudentTermFeeClearance::where('term_id', $term->id)
                    ->whereIn('student_id', $students->pluck('id'))
                    ->get()
                    ->keyBy('student_id');

                // Ensure snapshot exists for display (keeps UI consistent even before scheduler runs)
                foreach ($students as $st) {
                    if (! $snapshots->has($st->id)) {
                        $snapshots->put($st->id, $this->service->upsertSnapshot($st, $term));
                    }
                }
            }
        }

        return view('teacher.fee_clearance.index', [
            'term' => $term,
            'classrooms' => $classrooms,
            'selectedClassId' => $selectedClassId,
            'selectedStreamId' => $selectedStreamId,
            'students' => $students,
            'snapshots' => $snapshots,
        ]);
    }
}


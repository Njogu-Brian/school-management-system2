<?php

namespace App\Http\Controllers;

use App\Models\Academics\Classroom;
use App\Models\DropOffPoint;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentDropOffController extends Controller
{
    public function index(Request $request)
    {
        DropOffPoint::ownMeans();
        $classrooms = Classroom::orderBy('name')->get();
        $classroomId = $request->integer('classroom_id') ?: null;
        $dropOffPoints = DropOffPoint::orderBy('name')->get(['id', 'name']);
        $ownMeansPointId = DropOffPoint::ownMeans()->id;

        $students = collect();
        $assignmentMap = collect();

        if ($classroomId) {
            $students = Student::query()
                ->with(['classroom', 'stream', 'dropOffPoint'])
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where('classroom_id', $classroomId)
                ->orderBy('first_name')
                ->get();

            $assignmentMap = StudentAssignment::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        return view('transport.student_dropoffs.index', [
            'classrooms' => $classrooms,
            'classroomId' => $classroomId,
            'students' => $students,
            'assignmentMap' => $assignmentMap,
            'dropOffPoints' => $dropOffPoints,
            'ownMeansPointId' => $ownMeansPointId,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'points' => 'required|array|min:1',
            'points.*.student_id' => 'required|integer|exists:students,id',
            'points.*.morning_drop_off_point_id' => 'nullable|integer|exists:drop_off_points,id',
            'points.*.evening_drop_off_point_id' => 'nullable|integer|exists:drop_off_points,id',
        ]);

        $ownMeansId = DropOffPoint::ownMeans()->id;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($validated, $ownMeansId, &$updated, &$errors) {
            foreach ($validated['points'] as $row) {
                $studentId = (int) $row['student_id'];
                $student = Student::withoutGlobalScope('active')->find($studentId);
                if (!$student) {
                    continue;
                }

                $morning = $this->resolvePointId($row['morning_drop_off_point_id'] ?? null, $ownMeansId);
                $evening = $this->resolvePointId($row['evening_drop_off_point_id'] ?? null, $ownMeansId);

                $assignment = StudentAssignment::firstOrNew(['student_id' => $studentId]);
                $assignment->morning_drop_off_point_id = $morning;
                $assignment->evening_drop_off_point_id = $evening;

                if ((int) $morning === (int) $ownMeansId) {
                    $assignment->morning_trip_id = null;
                }
                if ((int) $evening === (int) $ownMeansId) {
                    $assignment->evening_trip_id = null;
                }

                $assignment->save();

                // Keep legacy student field in sync with evening (or morning) non-own-means point.
                $legacyId = ((int) $evening !== (int) $ownMeansId)
                    ? $evening
                    : (((int) $morning !== (int) $ownMeansId) ? $morning : null);
                $student->drop_off_point_id = $legacyId;
                $student->drop_off_point = $legacyId
                    ? optional(DropOffPoint::find($legacyId))->name
                    : DropOffPoint::OWN_MEANS_NAME;
                $student->save();

                $result = TransportFeeService::recalculateForStudent(
                    $studentId,
                    null,
                    null,
                    true,
                    'calculated',
                    'Recalculated after student drop-off update'
                );

                if (!$result['updated'] && !empty($result['result']['errors'])) {
                    $errors[] = ($student->full_name ?? "Student #{$studentId}") . ': ' . implode(' ', $result['result']['errors']);
                }

                $updated++;
            }
        });

        $redirect = redirect()
            ->route('transport.student-dropoffs.index', array_filter([
                'classroom_id' => $validated['classroom_id'] ?? null,
            ]))
            ->with('success', "Updated drop-off points for {$updated} student(s). Run Post Pending Fees if list prices changed.");

        if ($errors) {
            $redirect->with('error', 'Some fees could not be calculated.')
                ->with('transport_fee_errors', $errors);
        }

        return $redirect;
    }

    private function resolvePointId(mixed $raw, int $ownMeansId): int
    {
        if ($raw === null || $raw === '') {
            return $ownMeansId;
        }

        return (int) $raw;
    }
}

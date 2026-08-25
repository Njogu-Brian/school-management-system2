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
        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Pickup and drop-off points are listed here. Assign students from Transport → Assignments.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'points' => 'required|array|min:1',
            'points.*.student_id' => 'required|integer|exists:students,id',
            'points.*.morning_drop_off_point_id' => 'nullable',
            'points.*.evening_drop_off_point_id' => 'nullable',
            'points.*.morning_drop_off_point_name' => 'nullable|string|max:255',
            'points.*.evening_drop_off_point_name' => 'nullable|string|max:255',
        ]);

        $ownMeansId = DropOffPoint::ownMeans()->id;
        $updated = 0;
        $createdPoints = 0;

        DB::transaction(function () use ($validated, $ownMeansId, &$updated, &$createdPoints) {
            foreach ($validated['points'] as $row) {
                $studentId = (int) $row['student_id'];
                $student = Student::withoutGlobalScope('active')->find($studentId);
                if (!$student) {
                    continue;
                }

                $morning = $this->resolvePointSelection(
                    $row['morning_drop_off_point_id'] ?? null,
                    $row['morning_drop_off_point_name'] ?? null,
                    $ownMeansId,
                    $createdPoints
                );
                $evening = $this->resolvePointSelection(
                    $row['evening_drop_off_point_id'] ?? null,
                    $row['evening_drop_off_point_name'] ?? null,
                    $ownMeansId,
                    $createdPoints
                );

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

                // Keep enrollment preference in sync (seed only — not a third transport stop).
                $seedId = ((int) $evening !== (int) $ownMeansId)
                    ? $evening
                    : (((int) $morning !== (int) $ownMeansId) ? $morning : null);
                $student->drop_off_point_id = $seedId;
                $student->drop_off_point = $seedId
                    ? optional(DropOffPoint::find($seedId))->name
                    : DropOffPoint::OWN_MEANS_NAME;
                $student->save();

                // Soft fee sync — never block transport saves with finance errors.
                TransportFeeService::recalculateForStudent(
                    $studentId,
                    null,
                    null,
                    true,
                    'calculated',
                    'Recalculated after student drop-off update'
                );

                $updated++;
            }
        });

        $message = "Updated morning pickup / evening drop-off for {$updated} student(s).";
        if ($createdPoints > 0) {
            $message .= " Created {$createdPoints} new drop-off point(s).";
        }

        return redirect()
            ->route('transport.student-dropoffs.index', array_filter([
                'classroom_id' => $validated['classroom_id'] ?? null,
            ]))
            ->with('success', $message);
    }

    /**
     * Accept existing id, or a new name (learns/creates the point).
     */
    private function resolvePointSelection(mixed $idOrBlank, mixed $name, int $ownMeansId, int &$createdPoints): int
    {
        $name = is_string($name) ? trim($name) : '';
        if ($name !== '') {
            $existed = DropOffPoint::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()
                || DropOffPoint::nameIsOwnMeans($name);
            $point = TransportFeeService::resolveDropOffPoint($name);
            if ($point) {
                if (!$existed) {
                    $createdPoints++;
                }

                return (int) $point->id;
            }
        }

        if ($idOrBlank === null || $idOrBlank === '' || $idOrBlank === '__new__') {
            return $ownMeansId;
        }

        if (!is_numeric($idOrBlank) || !DropOffPoint::whereKey((int) $idOrBlank)->exists()) {
            return $ownMeansId;
        }

        return (int) $idOrBlank;
    }
}

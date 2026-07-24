<?php

namespace App\Http\Controllers;

use App\Models\DropOffPoint;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::with(['vehicle', 'driver.user'])->orderBy('trip_name')->get();
        return view('trips.index', compact('trips'));
    }

    public function create()
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        return view('trips.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:Morning,Evening',
            'direction' => 'nullable|in:pickup,dropoff',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $data = $request->only(['vehicle_id', 'driver_id', 'direction', 'type']);
        $data['trip_name'] = $request->input('name');
        [$data['type'], $data['direction']] = $this->normalizeTypeAndDirection(
            $data['type'] ?? null,
            $data['direction'] ?? null
        );

        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }

        Trip::create($data);
        return redirect()->route('transport.trips.index')->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $trip->load(['vehicle', 'driver.user']);
        return view('trips.edit', compact('trip', 'vehicles'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'type' => 'nullable|in:Morning,Evening',
            'direction' => 'nullable|in:pickup,dropoff',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        [$type, $direction] = $this->normalizeTypeAndDirection(
            $request->input('type'),
            $request->input('direction')
        );

        $data = [
            'trip_name' => $request->input('name'),
            'type' => $type,
            'vehicle_id' => $request->input('vehicle_id') ?: null,
            'driver_id' => $request->input('driver_id') ?: null,
            'direction' => $direction,
        ];

        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }

        $trip->update($data);

        return redirect()->route('transport.trips.index')->with('success', 'Trip updated successfully!');
    }

    /**
     * Keep Morning/Evening type aligned with pickup/dropoff direction.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function normalizeTypeAndDirection(?string $type, ?string $direction): array
    {
        $type = $type ?: null;
        $direction = $direction ?: null;

        if ($type && !$direction) {
            $direction = $type === 'Morning' ? 'pickup' : 'dropoff';
        } elseif ($direction && !$type) {
            $type = $direction === 'pickup' ? 'Morning' : 'Evening';
        }

        return [$type, $direction];
    }

    public function destroy(Trip $trip)
    {
        try {
            $detached = 0;

            DB::transaction(function () use ($trip, &$detached) {
                $detached = $trip->detachStudentAssignments();
                $trip->delete();
            });

            $message = 'Trip deleted successfully.';
            if ($detached > 0) {
                $message .= " Cleared {$detached} student assignment reference(s).";
            }

            return redirect()->route('transport.trips.index')->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('transport.trips.index')
                ->with('error', 'Could not delete trip: ' . $e->getMessage());
        }
    }

    /**
     * Open a trip to search and bulk-assign students.
     */
    public function assign(Trip $trip)
    {
        $trip->load(['vehicle', 'driver.user']);
        $leg = $trip->assignmentLeg() ?? 'morning';

        $assigned = StudentAssignment::query()
            ->with([
                'student.classroom',
                'student.stream',
                'student.dropOffPoint',
                'morningDropOffPoint',
                'eveningDropOffPoint',
            ])
            ->where(function ($q) use ($trip) {
                $q->where('morning_trip_id', $trip->id)
                    ->orWhere('evening_trip_id', $trip->id);
                if (Schema::hasColumn('student_assignments', 'trip_id')) {
                    $q->orWhere('trip_id', $trip->id);
                }
            })
            ->get()
            ->filter(fn ($a) => $a->student)
            ->values();

        $stopLabel = function (StudentAssignment $row) use ($leg): string {
            $student = $row->student;
            if ($leg === 'evening') {
                return trim((string) (
                    optional($row->eveningDropOffPoint)->name
                    ?? optional($student->dropOffPoint)->name
                    ?? $student->drop_off_point_other
                    ?? ''
                )) ?: 'Unassigned';
            }

            return trim((string) (
                optional($row->morningDropOffPoint)->name
                ?? optional($student->dropOffPoint)->name
                ?? $student->drop_off_point_other
                ?? ''
            )) ?: 'Unassigned';
        };

        $assigned = $assigned
            ->sort(function (StudentAssignment $a, StudentAssignment $b) use ($stopLabel) {
                $stopCmp = strcasecmp($stopLabel($a), $stopLabel($b));
                if ($stopCmp !== 0) {
                    return $stopCmp;
                }

                return strcasecmp((string) ($a->student->full_name ?? ''), (string) ($b->student->full_name ?? ''));
            })
            ->values();

        $stopCounts = $assigned
            ->groupBy(fn (StudentAssignment $row) => $stopLabel($row))
            ->map(fn ($group) => $group->count())
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

        DropOffPoint::ownMeans();
        $dropOffPoints = DropOffPoint::orderBy('name')->get(['id', 'name']);

        return view('trips.assign', [
            'trip' => $trip,
            'assigned' => $assigned,
            'defaultLeg' => $leg,
            'dropOffPoints' => $dropOffPoints,
            'stopCounts' => $stopCounts,
            'stopLegLabel' => $leg === 'evening' ? 'Evening drop-off' : 'Morning pickup',
        ]);
    }

    /**
     * Search students for trip assignment (includes morning/evening points).
     */
    public function assignSearch(Request $request, Trip $trip)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $searchTerm = '%' . addcslashes(mb_strtolower($q, 'UTF-8'), '%_\\') . '%';
        $normalizedAdmission = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $q), 'UTF-8');

        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->where(function ($s) use ($searchTerm, $normalizedAdmission) {
                $s->whereRaw('LOWER(first_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(admission_number) LIKE ?', [$searchTerm]);
                if ($normalizedAdmission !== '') {
                    $s->orWhereRaw(
                        'LOWER(REPLACE(REPLACE(REPLACE(admission_number, " ", ""), "-", ""), "/", "")) LIKE ?',
                        ['%' . $normalizedAdmission . '%']
                    );
                }
            })
            ->with([
                'classroom',
                'stream',
                'dropOffPoint',
                'assignments.morningDropOffPoint',
                'assignments.eveningDropOffPoint',
            ])
            ->orderBy('first_name')
            ->limit(30)
            ->get();

        return response()->json($students->map(fn (Student $st) => $this->formatStudentForTripAssign($st, $trip)));
    }

    /**
     * Suggest students with similar pickup/drop-off points to a selected student.
     */
    public function assignSuggest(Request $request, Trip $trip)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'leg' => 'nullable|in:morning,evening',
            'point_id' => 'nullable|integer|exists:drop_off_points,id',
        ]);

        $leg = $request->input('leg', $trip->assignmentLeg() ?? 'morning');
        $seed = Student::withoutGlobalScope('active')
            ->with(['dropOffPoint', 'assignments.morningDropOffPoint', 'assignments.eveningDropOffPoint'])
            ->findOrFail((int) $request->student_id);

        $seedAssignment = $seed->assignments->first();
        $pointId = $request->filled('point_id') ? (int) $request->point_id : null;
        $pointName = null;

        if ($pointId) {
            $pointName = optional(DropOffPoint::find($pointId))->name;
        } elseif ($leg === 'evening') {
            $pointId = $seedAssignment?->evening_drop_off_point_id ?: $seed->drop_off_point_id;
            $pointName = optional($seedAssignment?->eveningDropOffPoint)->name
                ?? optional($seed->dropOffPoint)->name
                ?? $seed->drop_off_point_other;
        } else {
            $pointId = $seedAssignment?->morning_drop_off_point_id ?: $seed->drop_off_point_id;
            $pointName = optional($seedAssignment?->morningDropOffPoint)->name
                ?? optional($seed->dropOffPoint)->name
                ?? $seed->drop_off_point_other;
        }

        if (!$pointId && (!$pointName || trim((string) $pointName) === '')) {
            return response()->json([
                'seed_student_id' => $seed->id,
                'point_label' => null,
                'students' => [],
            ]);
        }

        $alreadyOnTrip = StudentAssignment::query()
            ->where(function ($q) use ($trip) {
                $q->where('morning_trip_id', $trip->id)->orWhere('evening_trip_id', $trip->id);
            })
            ->pluck('student_id')
            ->all();

        $excludeIds = array_values(array_unique(array_merge($alreadyOnTrip, [$seed->id])));

        $query = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotIn('id', $excludeIds)
            ->with([
                'classroom',
                'stream',
                'dropOffPoint',
                'assignments.morningDropOffPoint',
                'assignments.eveningDropOffPoint',
            ]);

        if ($pointId) {
            $query->where(function ($q) use ($pointId, $leg) {
                $q->where('drop_off_point_id', $pointId)
                    ->orWhereHas('assignments', function ($a) use ($pointId, $leg) {
                        if ($leg === 'evening') {
                            $a->where('evening_drop_off_point_id', $pointId);
                        } else {
                            $a->where('morning_drop_off_point_id', $pointId);
                        }
                    });
            });
        } elseif ($pointName) {
            $name = trim((string) $pointName);
            $query->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(drop_off_point_other) = ?', [mb_strtolower($name)])
                    ->orWhereHas('dropOffPoint', function ($d) use ($name) {
                        $d->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                    });
            });
        }

        $students = $query->orderBy('first_name')->limit(40)->get();

        return response()->json([
            'seed_student_id' => $seed->id,
            'point_label' => $pointName,
            'leg' => $leg,
            'students' => $students->map(fn (Student $st) => $this->formatStudentForTripAssign($st, $trip))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStudentForTripAssign(Student $st, Trip $trip): array
    {
        $full = trim(implode(' ', array_filter([$st->first_name, $st->middle_name, $st->last_name])));
        $assignment = $st->relationLoaded('assignments') ? $st->assignments->first() : $st->assignments()->first();

        $morningPoint = optional($assignment?->morningDropOffPoint)->name
            ?? optional($st->dropOffPoint)->name
            ?? $st->drop_off_point_other;
        $eveningPoint = optional($assignment?->eveningDropOffPoint)->name
            ?? optional($st->dropOffPoint)->name
            ?? $st->drop_off_point_other;

        $onTripMorning = (int) ($assignment?->morning_trip_id) === (int) $trip->id;
        $onTripEvening = (int) ($assignment?->evening_trip_id) === (int) $trip->id;

        return [
            'id' => $st->id,
            'full_name' => $full,
            'admission_number' => $st->admission_number ?? '',
            'classroom_name' => $st->classroom?->name,
            'stream_name' => $st->stream?->name,
            'morning_point' => $morningPoint,
            'evening_point' => $eveningPoint,
            'morning_point_id' => $assignment?->morning_drop_off_point_id ?: $st->drop_off_point_id,
            'evening_point_id' => $assignment?->evening_drop_off_point_id ?: $st->drop_off_point_id,
            'on_trip_morning' => $onTripMorning,
            'on_trip_evening' => $onTripEvening,
            'on_trip' => $onTripMorning || $onTripEvening,
        ];
    }

    /**
     * Save bulk student assignments onto this trip.
     */
    public function assignStore(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'leg' => 'required|in:morning,evening',
            'morning_drop_off_point_ids' => 'nullable|array',
            'morning_drop_off_point_ids.*' => 'nullable|integer|exists:drop_off_points,id',
            'evening_drop_off_point_ids' => 'nullable|array',
            'evening_drop_off_point_ids.*' => 'nullable|integer|exists:drop_off_points,id',
        ]);

        $leg = $validated['leg'];
        $column = $leg === 'morning' ? 'morning_trip_id' : 'evening_trip_id';
        $studentIds = array_map('intval', $validated['student_ids']);
        $morningPoints = $validated['morning_drop_off_point_ids'] ?? [];
        $eveningPoints = $validated['evening_drop_off_point_ids'] ?? [];
        $assigned = 0;

        DB::transaction(function () use ($studentIds, $column, $trip, $morningPoints, $eveningPoints, &$assigned) {
            foreach ($studentIds as $studentId) {
                $student = Student::withoutGlobalScope('active')->find($studentId);
                if (!$student) {
                    continue;
                }

                $assignment = StudentAssignment::firstOrNew(['student_id' => $studentId]);
                $assignment->{$column} = $trip->id;

                if (array_key_exists($studentId, $morningPoints) || array_key_exists((string) $studentId, $morningPoints)) {
                    $raw = $morningPoints[$studentId] ?? $morningPoints[(string) $studentId] ?? null;
                    $assignment->morning_drop_off_point_id = $raw !== null && $raw !== '' ? (int) $raw : null;
                } elseif (!$assignment->morning_drop_off_point_id && $student->drop_off_point_id) {
                    $assignment->morning_drop_off_point_id = $student->drop_off_point_id;
                }

                if (array_key_exists($studentId, $eveningPoints) || array_key_exists((string) $studentId, $eveningPoints)) {
                    $raw = $eveningPoints[$studentId] ?? $eveningPoints[(string) $studentId] ?? null;
                    $assignment->evening_drop_off_point_id = $raw !== null && $raw !== '' ? (int) $raw : null;
                } elseif (!$assignment->evening_drop_off_point_id && $student->drop_off_point_id) {
                    $assignment->evening_drop_off_point_id = $student->drop_off_point_id;
                }

                $assignment->save();
                $assigned++;

                TransportFeeService::recalculateForStudent(
                    $studentId,
                    null,
                    null,
                    true,
                    'calculated',
                    'Recalculated after trip student assign'
                );
            }
        });

        return redirect()
            ->route('transport.trips.assign', $trip)
            ->with('success', "Assigned {$assigned} student(s) to {$trip->trip_name}. Run Post Pending Fees if transport list prices changed.");
    }

    /**
     * Update morning/evening drop-off points for students already on this trip.
     */
    public function assignUpdatePoints(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'points' => 'required|array|min:1',
            'points.*.student_id' => 'required|integer|exists:students,id',
            'points.*.morning_drop_off_point_id' => 'nullable|integer|exists:drop_off_points,id',
            'points.*.evening_drop_off_point_id' => 'nullable|integer|exists:drop_off_points,id',
        ]);

        $updated = 0;

        DB::transaction(function () use ($validated, $trip, &$updated) {
            foreach ($validated['points'] as $row) {
                $studentId = (int) $row['student_id'];
                $assignment = StudentAssignment::where('student_id', $studentId)
                    ->where(function ($q) use ($trip) {
                        $q->where('morning_trip_id', $trip->id)
                            ->orWhere('evening_trip_id', $trip->id);
                    })
                    ->first();

                if (!$assignment) {
                    continue;
                }

                $morning = $row['morning_drop_off_point_id'] ?? null;
                $evening = $row['evening_drop_off_point_id'] ?? null;
                $assignment->morning_drop_off_point_id = $morning !== null && $morning !== '' ? (int) $morning : null;
                $assignment->evening_drop_off_point_id = $evening !== null && $evening !== '' ? (int) $evening : null;
                $assignment->save();
                $updated++;

                TransportFeeService::recalculateForStudent(
                    $studentId,
                    null,
                    null,
                    true,
                    'calculated',
                    'Recalculated after trip assign point update'
                );
            }
        });

        return redirect()
            ->route('transport.trips.assign', $trip)
            ->with('success', "Updated pickup/drop-off points for {$updated} student(s). Run Post Pending Fees if transport list prices changed.");
    }

    /**
     * Remove a student from this trip.
     */
    public function unassign(Request $request, Trip $trip, Student $student)
    {
        $assignment = StudentAssignment::where('student_id', $student->id)->first();
        if ($assignment) {
            if ($assignment->morning_trip_id == $trip->id) {
                $assignment->morning_trip_id = null;
            }
            if ($assignment->evening_trip_id == $trip->id) {
                $assignment->evening_trip_id = null;
            }
            if (Schema::hasColumn('student_assignments', 'trip_id') && $assignment->trip_id == $trip->id) {
                $assignment->trip_id = null;
            }
            $assignment->save();

            TransportFeeService::recalculateForStudent(
                $student->id,
                null,
                null,
                true,
                'calculated',
                'Recalculated after trip student unassign'
            );
        }

        return redirect()
            ->route('transport.trips.assign', $trip)
            ->with('success', 'Student removed from this trip.');
    }
}

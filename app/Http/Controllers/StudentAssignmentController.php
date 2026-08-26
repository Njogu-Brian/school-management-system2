<?php

namespace App\Http\Controllers;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\AcademicYear;
use App\Models\DropOffPoint;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Term;
use App\Models\TransportFee;
use App\Models\Trip;
use App\Services\TransportAssignmentWriter;
use App\Services\TransportFeeCalculator;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        DropOffPoint::ownMeans();

        $tab = $request->input('tab', 'class');
        if (! in_array($tab, ['class', 'student'], true)) {
            $tab = 'class';
        }

        [$year, $term, $academicYearId] = $this->resolveRequestedYearTerm($request);
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $allTerms = Term::with('academicYear')->whereNotNull('opening_date')->orderBy('opening_date')->get();

        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $streams = Stream::orderBy('name')->get(['id', 'name', 'classroom_id']);
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $ownMeansPoint = DropOffPoint::ownMeans();

        $trips = Trip::with('vehicle')->orderBy('trip_name')->get();
        $morningTrips = $trips->filter(fn (Trip $t) => $t->assignmentLeg() !== 'evening')->values();
        $eveningTrips = $trips->filter(fn (Trip $t) => $t->assignmentLeg() !== 'morning')->values();

        $selectedClassroomId = $request->integer('classroom_id') ?: null;
        $selectedStreamId = $request->integer('stream_id') ?: null;
        $incompleteOnly = $request->boolean('incomplete');

        $students = collect();
        $assignments = collect();
        $fees = collect();

        if ($tab === 'class' && $selectedClassroomId) {
            $query = Student::query()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where('classroom_id', $selectedClassroomId)
                ->with(['classroom', 'stream', 'assignment.morningDropOffPoint', 'assignment.eveningDropOffPoint', 'assignment.morningTrip', 'assignment.eveningTrip']);

            if ($selectedStreamId) {
                $query->where('stream_id', $selectedStreamId);
            }

            $students = $query->orderBy('first_name')->orderBy('last_name')->get();

            $assignments = StudentAssignment::keyedForStudents($students->pluck('id'), $year, $term, true);

            $fees = TransportFee::query()
                ->whereIn('student_id', $students->pluck('id'))
                ->where('year', $year)
                ->where('term', $term)
                ->get()
                ->keyBy('student_id');

            if ($incompleteOnly) {
                $students = $students->filter(function (Student $student) use ($assignments, $ownMeansPoint) {
                    return $this->isIncomplete($assignments->get($student->id), (int) $ownMeansPoint->id);
                })->values();
            }
        }

        $selectedStudent = null;
        $selectedAssignment = null;
        $selectedFee = null;
        $searchQ = trim((string) $request->input('q', ''));

        if ($tab === 'student') {
            $studentId = $request->integer('student_id') ?: null;
            if ($studentId) {
                $selectedStudent = Student::query()
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->with(['classroom', 'stream', 'assignment'])
                    ->find($studentId);
            }

            if ($selectedStudent) {
                $selectedAssignment = StudentAssignment::forStudent((int) $selectedStudent->id, $year, $term, true);
                if ($selectedAssignment
                    && ((int) $selectedAssignment->year !== (int) $year || (int) $selectedAssignment->term !== (int) $term)
                ) {
                    $selectedAssignment->setAttribute('prefilled_from_prior_term', true);
                }
                $selectedFee = TransportFee::where('student_id', $selectedStudent->id)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->first();
            }
        }

        return view('student_assignments.index', compact(
            'tab',
            'year',
            'term',
            'academicYearId',
            'academicYears',
            'allTerms',
            'classrooms',
            'streams',
            'dropOffPoints',
            'ownMeansPoint',
            'morningTrips',
            'eveningTrips',
            'selectedClassroomId',
            'selectedStreamId',
            'incompleteOnly',
            'students',
            'assignments',
            'fees',
            'selectedStudent',
            'selectedAssignment',
            'selectedFee',
            'searchQ'
        ));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $searchTerm = '%'.addcslashes(mb_strtolower($q, 'UTF-8'), '%_\\').'%';
        $normalizedAdmission = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $q) ?? '', 'UTF-8');

        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->where(function ($s) use ($searchTerm, $normalizedAdmission) {
                $s->whereRaw('LOWER(first_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(middle_name,''),' ',COALESCE(last_name,''))) LIKE ?", [$searchTerm])
                    ->orWhereRaw('LOWER(admission_number) LIKE ?', [$searchTerm]);
                if ($normalizedAdmission !== '') {
                    $s->orWhereRaw(
                        'LOWER(REPLACE(REPLACE(REPLACE(admission_number, " ", ""), "-", ""), "/", "")) LIKE ?',
                        ['%'.$normalizedAdmission.'%']
                    );
                }
            })
            ->with(['classroom', 'stream'])
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        [$year, $term] = $this->resolveRequestedYearTerm($request);
        $assignments = StudentAssignment::keyedForStudents($students->pluck('id'), $year, $term, true);

        return response()->json($students->map(function (Student $student) use ($assignments) {
            $assignment = $assignments->get($student->id);

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'classroom' => optional($student->classroom)->name,
                'stream' => optional($student->stream)->name,
                'morning_trip_id' => $assignment?->morning_trip_id,
                'evening_trip_id' => $assignment?->evening_trip_id,
                'morning_drop_off_point_id' => $assignment?->morning_drop_off_point_id,
                'evening_drop_off_point_id' => $assignment?->evening_drop_off_point_id,
            ];
        }));
    }

    public function quoteAmount(Request $request)
    {
        $morningId = TransportAssignmentWriter::nullableId($request->input('morning_drop_off_point_id'));
        $eveningId = TransportAssignmentWriter::nullableId($request->input('evening_drop_off_point_id'));
        $result = TransportFeeCalculator::calculate($morningId, $eveningId);

        return response()->json([
            'amount' => $result['can_calculate'] ? $result['amount'] : null,
            'label' => $result['breakdown']['label'] ?? null,
            'can_calculate' => $result['can_calculate'],
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function create(Request $request)
    {
        return redirect()->route('transport.student-assignments.index', array_filter([
            'tab' => 'student',
            'student_id' => $request->input('student_id'),
            'q' => $request->input('q'),
        ]));
    }

    public function store(Request $request)
    {
        return $this->saveIndividual($request);
    }

    public function edit(StudentAssignment $student_assignment)
    {
        return redirect()->route('transport.student-assignments.index', array_filter([
            'tab' => 'student',
            'student_id' => $student_assignment->student_id,
            'year' => $student_assignment->year,
            'term' => $student_assignment->term,
        ]));
    }

    public function update(Request $request, StudentAssignment $student_assignment)
    {
        $request->merge(['student_id' => $student_assignment->student_id]);

        return $this->saveIndividual($request);
    }

    public function show(StudentAssignment $student_assignment)
    {
        $student_assignment->load([
            'student.classroom',
            'morningTrip.vehicle',
            'eveningTrip.vehicle',
            'morningDropOffPoint',
            'eveningDropOffPoint',
        ]);

        return view('student_assignments.show', ['assignment' => $student_assignment]);
    }

    public function destroy(StudentAssignment $student_assignment)
    {
        $student = $student_assignment->student;
        if ($student) {
            TransportAssignmentWriter::clear(
                $student,
                true,
                $student_assignment->year ? (int) $student_assignment->year : null,
                $student_assignment->term ? (int) $student_assignment->term : null
            );
        } else {
            $student_assignment->delete();
        }

        return redirect()
            ->route('transport.student-assignments.index')
            ->with('success', 'Transport assignment removed.');
    }

    public function bulkAssign(Request $request)
    {
        return redirect()->route('transport.student-assignments.index', array_filter([
            'tab' => 'class',
            'classroom_id' => $request->input('classroom_id'),
            'stream_id' => $request->input('stream_id'),
            'year' => $request->input('year'),
            'term' => $request->input('term'),
        ]));
    }

    public function bulkAssignStore(Request $request)
    {
        DropOffPoint::ownMeans();
        $ownMeansId = (int) DropOffPoint::ownMeans()->id;

        $request->validate([
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'assignments' => 'required|array|min:1',
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.morning_trip_id' => 'nullable|exists:trips,id',
            'assignments.*.evening_trip_id' => 'nullable|exists:trips,id',
            'assignments.*.morning_drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'assignments.*.evening_drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'assignments.*.amount' => 'nullable|numeric|min:0',
            'year' => 'nullable|integer|min:2000|max:2100',
            'term' => 'nullable|integer|min:1|max:3',
        ]);

        $saved = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($request->input('assignments', []) as $row) {
                $student = Student::withoutGlobalScope('active')->find($row['student_id'] ?? null);
                if (! $student) {
                    $skipped++;
                    continue;
                }

                $morningPoint = TransportAssignmentWriter::nullableId($row['morning_drop_off_point_id'] ?? null);
                $eveningPoint = TransportAssignmentWriter::nullableId($row['evening_drop_off_point_id'] ?? null);
                $morningTrip = TransportAssignmentWriter::nullableId($row['morning_trip_id'] ?? null);
                $eveningTrip = TransportAssignmentWriter::nullableId($row['evening_trip_id'] ?? null);
                $amount = array_key_exists('amount', $row) ? TransportAssignmentWriter::parseAmount($row['amount']) : null;

                $hasAnything = $morningPoint || $eveningPoint || $morningTrip || $eveningTrip || $amount !== null;
                if (! $hasAnything) {
                    $skipped++;
                    continue;
                }

                if ($morningPoint && (int) $morningPoint !== $ownMeansId && ! $morningTrip) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        $student->full_name.': morning trip is required unless morning pickup is OWN MEANS.'
                    );
                }
                if ($eveningPoint && (int) $eveningPoint !== $ownMeansId && ! $eveningTrip) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        $student->full_name.': evening trip is required unless evening drop-off is OWN MEANS.'
                    );
                }

                TransportAssignmentWriter::save($student, [
                    'morning_trip_id' => $morningTrip,
                    'evening_trip_id' => $eveningTrip,
                    'morning_drop_off_point_id' => $morningPoint,
                    'evening_drop_off_point_id' => $eveningPoint,
                    'amount' => $row['amount'] ?? null,
                    'source' => 'assignment',
                    'note' => 'Saved from class assignment',
                    'skip_invoice' => true,
                    'year' => $request->input('year'),
                    'term' => $request->input('term'),
                ]);
                $saved++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', 'Could not save assignments: '.$e->getMessage());
        }

        return redirect()
            ->route('transport.student-assignments.index', array_filter([
                'tab' => 'class',
                'classroom_id' => $request->input('classroom_id'),
                'stream_id' => $request->input('stream_id'),
                'year' => $request->input('year'),
                'term' => $request->input('term'),
            ]))
            ->with('success', "Saved {$saved} student assignment(s). List prices updated. Run Post Pending Fees to update invoices.");
    }

    private function saveIndividual(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'morning_drop_off_point_id' => 'required|exists:drop_off_points,id',
            'evening_drop_off_point_id' => 'required|exists:drop_off_points,id',
            'morning_trip_id' => [
                Rule::requiredIf(fn () => TransportAssignmentWriter::tripRequiredForPoint($request->input('morning_drop_off_point_id'))),
                'nullable',
                'exists:trips,id',
            ],
            'evening_trip_id' => [
                Rule::requiredIf(fn () => TransportAssignmentWriter::tripRequiredForPoint($request->input('evening_drop_off_point_id'))),
                'nullable',
                'exists:trips,id',
            ],
            'amount' => 'required|numeric|min:0',
            'year' => 'nullable|integer|min:2000|max:2100',
            'term' => 'nullable|integer|min:1|max:3',
        ]);

        $student = Student::withoutGlobalScope('active')->findOrFail($validated['student_id']);

        TransportAssignmentWriter::save($student, [
            'morning_trip_id' => $validated['morning_trip_id'] ?? null,
            'evening_trip_id' => $validated['evening_trip_id'] ?? null,
            'morning_drop_off_point_id' => $validated['morning_drop_off_point_id'],
            'evening_drop_off_point_id' => $validated['evening_drop_off_point_id'],
            'amount' => $validated['amount'],
            'source' => 'assignment',
            'note' => 'Saved from student assignment',
            'skip_invoice' => true,
            'year' => $validated['year'] ?? $request->input('year'),
            'term' => $validated['term'] ?? $request->input('term'),
        ]);

        return redirect()
            ->route('transport.student-assignments.index', array_filter([
                'tab' => 'student',
                'student_id' => $student->id,
                'year' => $validated['year'] ?? $request->input('year'),
                'term' => $validated['term'] ?? $request->input('term'),
            ]))
            ->with('success', $student->full_name.' assigned. Amount can be 0. Run Post Pending Fees to update invoices.');
    }

    private function resolveRequestedYearTerm(Request $request): array
    {
        if ($request->filled('year_term') && preg_match('/^(\d+)\|(\d+)$/', (string) $request->input('year_term'), $m)) {
            return TransportFeeService::resolveYearAndTerm((int) $m[1], (int) $m[2]);
        }

        return TransportFeeService::resolveYearAndTerm(
            $request->filled('year') ? (int) $request->input('year') : null,
            $request->filled('term') ? (int) $request->input('term') : null
        );
    }

    private function isIncomplete(?StudentAssignment $assignment, int $ownMeansId): bool
    {
        if (! $assignment) {
            return true;
        }

        $morningPoint = $assignment->morning_drop_off_point_id;
        $eveningPoint = $assignment->evening_drop_off_point_id;
        if (! $morningPoint || ! $eveningPoint) {
            return true;
        }

        if ((int) $morningPoint !== $ownMeansId && ! $assignment->morning_trip_id) {
            return true;
        }
        if ((int) $eveningPoint !== $ownMeansId && ! $assignment->evening_trip_id) {
            return true;
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Services\PhoneNumberService;
use App\Services\StudentBalanceService;
use App\Services\StudentSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiStudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $user = $request->user();

        $query = Student::with(['parent', 'classroom', 'stream', 'category'])
            ->where('archive', 0)
            ->where('is_alumni', false);

        // Teachers and Senior Teachers only see students from their assigned classes/streams
        if ($user && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($query);
        }

        if ($user && $user->shouldScopeAsParent()) {
            $ids = $user->accessibleStudentIds();
            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids);
            }
        }

        if ($request->filled('search')) {
            app(StudentSearchService::class)->applySearch($query, (string) $request->search);
        }
        if ($request->filled('name')) {
            app(StudentSearchService::class)->applySearch($query, (string) $request->name);
        }
        if ($request->filled('classroom_id') || $request->filled('class_id')) {
            $query->where('classroom_id', $request->classroom_id ?? $request->class_id);
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }

        $paginated = $query->orderBy('first_name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($s) => $this->formatStudent($s, $user))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $student = Student::with([
            'parent',
            'classroom',
            'stream',
            'category',
            'trip.vehicle',
            'dropOffPoint',
            'assignments.morningTrip.vehicle',
            'assignments.eveningTrip.vehicle',
            'assignments.morningDropOffPoint',
            'assignments.eveningDropOffPoint',
        ])->findOrFail($id);
        $user = $request->user();

        // Teachers can only view students from their assigned classes
        if ($user && $user->hasTeacherLikeRole()) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (!$query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        if ($user && $user->shouldScopeAsParent()) {
            if (! $user->canAccessStudent((int) $id)) {
                abort(403, 'You do not have access to this student.');
            }
        }

        return response()->json(['success' => true, 'data' => $this->formatStudent($student, $user)]);
    }

    /**
     * Used by ApiStudentWriteController to return the same shape as GET /students/{id}.
     */
    public function serializeStudent(Student $student): array
    {
        return $this->formatStudent($student);
    }

    /**
     * Aggregates for profile tabs (attendance %, fee balance).
     */
    public function stats(Request $request, int $id)
    {
        $student = Student::findOrFail($id);
        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        if ($user && $user->shouldScopeAsParent()) {
            if (! $user->canAccessStudent($id)) {
                abort(403, 'You do not have access to this student.');
            }
        }

        $from = Carbon::now()->subDays(90)->startOfDay();
        $to = Carbon::now()->endOfDay();
        $calendar = app(\App\Services\StudentAttendanceCalendarService::class);
        $expectedSchoolDays = $calendar->expectedSchoolDaysBetween($student, $from, $to, null);

        $records = Attendance::where('student_id', $student->id)
            ->where('date', '>=', $from->toDateString())
            ->get();
        $num = $records->where('status', Attendance::STATUS_PRESENT)->count();
        $late = $records->where('status', Attendance::STATUS_LATE)->count();
        $attending = $num + $late;
        $attendancePct = $expectedSchoolDays > 0 ? round(100 * $attending / $expectedSchoolDays, 1) : null;

        $data = [
            'attendance_percentage' => $attendancePct,
            'expected_school_days' => $expectedSchoolDays,
            'attendance_records_count' => $records->count(),
            'attendance_days_marked' => $records->count(),
            'exam_average' => null,
        ];

        if ($user && $user->canViewStudentFeeAmounts()) {
            $feesTotal = (float) StudentBalanceService::getTotalOutstandingBalance($student, false);
            $feesDue = (float) StudentBalanceService::getTotalOutstandingBalance($student, true);
            $feesUpcoming = max(0, round($feesTotal - $feesDue, 2));
            // Keep fees_balance as currently due (what parents must pay now).
            $data['fees_balance'] = round($feesDue, 2);
            $data['fees_due'] = round($feesDue, 2);
            $data['fees_upcoming'] = $feesUpcoming;
            $data['fees_total_outstanding'] = round($feesTotal, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Calendar dots for a month (present / absent / late / excused).
     */
    public function attendanceCalendar(Request $request, int $id)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $student = Student::findOrFail($id);
        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        if ($user && $user->shouldScopeAsParent()) {
            if (! $user->canAccessStudent($id)) {
                abort(403, 'You do not have access to this student.');
            }
        }

        $year = (int) $request->year;
        $month = (int) $request->month;

        $marked = Attendance::where('student_id', $student->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        $calendar = app(\App\Services\StudentAttendanceCalendarService::class);
        $cursor = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $end = $cursor->copy()->endOfMonth();
        $rows = [];
        for ($d = $cursor->copy(); $d->lte($end); $d->addDay()) {
            $date = $d->toDateString();
            $row = $marked->get($date);
            $rows[] = [
                'date' => $date,
                'status' => $row?->status,
                'is_excused' => (bool) ($row?->is_excused),
                'is_school_day' => $calendar->isValidSchoolDay($d),
                'weekday' => (int) $d->dayOfWeek,
            ];
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    protected function formatStudent(Student $s, ?\App\Models\User $user = null): array
    {
        $user = $user ?? auth()->user();
        $fullName = trim(($s->first_name ?? '') . ' ' . ($s->middle_name ?? '') . ' ' . ($s->last_name ?? ''));
        $parent = $s->parent;

        $guardians = [];
        if ($parent) {
            if (! empty($parent->father_name)) {
                $guardians[] = [
                    'id' => $parent->id * 10 + 1,
                    'name' => $parent->father_name,
                    'full_name' => $parent->father_name,
                    'relationship' => 'father',
                    'phone' => $parent->father_phone ?? '',
                    'email' => $parent->father_email ?? '',
                    'is_primary' => ($parent->primary_contact_person ?? '') === 'father',
                ];
            }
            if (! empty($parent->mother_name)) {
                $guardians[] = [
                    'id' => $parent->id * 10 + 2,
                    'name' => $parent->mother_name,
                    'full_name' => $parent->mother_name,
                    'relationship' => 'mother',
                    'phone' => $parent->mother_phone ?? '',
                    'email' => $parent->mother_email ?? '',
                    'is_primary' => ($parent->primary_contact_person ?? '') === 'mother',
                ];
            }
            if (! empty($parent->guardian_name)) {
                $guardians[] = [
                    'id' => $parent->id * 10 + 3,
                    'name' => $parent->guardian_name,
                    'full_name' => $parent->guardian_name,
                    'relationship' => $parent->guardian_relationship ?? 'guardian',
                    'phone' => $parent->guardian_phone ?? '',
                    'email' => $parent->guardian_email ?? '',
                    'is_primary' => true,
                ];
            }
        }

        $blood = $s->blood_group ?? null;
        $phoneSvc = app(PhoneNumberService::class);
        $feeInfo = $this->resolveFeeStatus($s);
        $fCc = $parent ? ($parent->father_phone_country_code ?? '+254') : '+254';
        $mCc = $parent ? ($parent->mother_phone_country_code ?? '+254') : '+254';
        $gCc = $parent ? ($parent->guardian_phone_country_code ?? '+254') : '+254';

        return [
            'id' => $s->id,
            'admission_number' => $s->admission_number ?? '',
            'first_name' => $s->first_name ?? '',
            'last_name' => $s->last_name ?? '',
            'middle_name' => $s->middle_name,
            'full_name' => $fullName,
            'date_of_birth' => $s->dob ? $s->dob->format('Y-m-d') : '',
            'gender' => $s->gender ?? 'other',
            'class_id' => $s->classroom_id,
            'classroom_id' => $s->classroom_id,
            'stream_id' => $s->stream_id,
            'category_id' => $s->category_id,
            'trip_id' => $s->trip_id,
            'trip_name' => $s->trip?->trip_name,
            'trip_vehicle' => $s->trip?->vehicle?->vehicle_number,
            'drop_off_point_id' => $s->drop_off_point_id,
            'drop_off_point_name' => $s->dropOffPoint?->name
                ?? (($s->drop_off_point_other && strtoupper(trim((string) $s->drop_off_point_other)) === 'OWN MEANS')
                    ? 'Own means'
                    : null),
            'drop_off_point_other' => $s->drop_off_point_other,
            'transport' => $this->formatTransport($s),
            'class_name' => $s->classroom->name ?? null,
            'stream_name' => $s->stream->name ?? null,
            'status' => $s->archive ? 'archived' : 'active',
            'category' => $s->category->name ?? null,
            'avatar' => $s->photo_url,
            'phone' => $parent ? ($parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null) : null,
            'email' => $parent ? ($parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null) : null,
            'address' => $s->residential_area ?? null,
            'residential_area' => $s->residential_area,
            'preferred_hospital' => $s->preferred_hospital,
            'nemis_number' => $s->nemis_number,
            'knec_assessment_number' => $s->knec_assessment_number,
            'kcpe_kjsea_year' => $s->kcpe_kjsea_year,
            'nationality' => $s->nationality,
            'county_of_birth' => $s->county_of_birth,
            'sub_county_of_birth' => $s->sub_county_of_birth,
            'location_of_birth' => $s->location_of_birth,
            'birth_certificate_entry_no' => $s->birth_certificate_entry_no,
            'medical_condition' => $s->medical_condition,
            'learner_interests' => $s->learner_interests,
            'orphan_status' => $s->orphan_status,
            'disability_type' => $s->disability_type,
            'has_special_needs' => (bool) $s->has_special_needs,
            'religion' => $s->religion,
            'has_allergies' => (bool) $s->has_allergies,
            'allergies_notes' => $s->allergies_notes,
            'is_fully_immunized' => $s->is_fully_immunized !== null ? (bool) $s->is_fully_immunized : null,
            'emergency_contact_name' => $s->emergency_contact_name,
            'emergency_contact_phone' => $s->emergency_contact_phone,
            'emergency_contact_phone_local' => $phoneSvc->extractLocalNumber($s->emergency_contact_phone, '+254'),
            'blood_group' => $blood,
            'admission_date' => $s->admission_date ? $s->admission_date->format('Y-m-d') : null,
            'enrollment_year' => $s->enrollment_year ?? null,
            'fee_status' => $feeInfo['status'],
            'outstanding_balance' => ($user && $user->canViewStudentFeeAmounts()) ? $feeInfo['balance'] : null,
            'parent' => $parent ? [
                'father_name' => $parent->father_name,
                'mother_name' => $parent->mother_name,
                'father_phone' => $parent->father_phone,
                'mother_phone' => $parent->mother_phone,
                'father_email' => $parent->father_email,
                'mother_email' => $parent->mother_email,
                'guardian_name' => $parent->guardian_name,
                'guardian_phone' => $parent->guardian_phone,
                'father_whatsapp' => $parent->father_whatsapp,
                'mother_whatsapp' => $parent->mother_whatsapp,
                'guardian_whatsapp' => $parent->guardian_whatsapp,
                'guardian_email' => $parent->guardian_email,
                'guardian_relationship' => $parent->guardian_relationship,
                'marital_status' => $parent->marital_status,
                'father_id_number' => $parent->father_id_number,
                'mother_id_number' => $parent->mother_id_number,
                'guardian_id_number' => $parent->guardian_id_number,
                'father_id_type' => $parent->father_id_type,
                'mother_id_type' => $parent->mother_id_type,
                'guardian_id_type' => $parent->guardian_id_type,
                'father_country_of_residence' => $parent->father_country_of_residence,
                'mother_country_of_residence' => $parent->mother_country_of_residence,
                'guardian_country_of_residence' => $parent->guardian_country_of_residence,
                'father_first_name' => $parent->father_first_name,
                'father_middle_name' => $parent->father_middle_name,
                'father_last_name' => $parent->father_last_name,
                'mother_first_name' => $parent->mother_first_name,
                'mother_middle_name' => $parent->mother_middle_name,
                'mother_last_name' => $parent->mother_last_name,
                'guardian_first_name' => $parent->guardian_first_name,
                'guardian_middle_name' => $parent->guardian_middle_name,
                'guardian_last_name' => $parent->guardian_last_name,
                'father_phone_country_code' => $parent->father_phone_country_code ?? '+254',
                'mother_phone_country_code' => $parent->mother_phone_country_code ?? '+254',
                'guardian_phone_country_code' => $parent->guardian_phone_country_code ?? '+254',
                'father_phone_local' => $phoneSvc->extractLocalNumber($parent->father_phone, $fCc),
                'mother_phone_local' => $phoneSvc->extractLocalNumber($parent->mother_phone, $mCc),
                'guardian_phone_local' => $phoneSvc->extractLocalNumber($parent->guardian_phone, $gCc),
                'father_whatsapp_local' => $phoneSvc->extractLocalNumber($parent->father_whatsapp, $fCc),
                'mother_whatsapp_local' => $phoneSvc->extractLocalNumber($parent->mother_whatsapp, $mCc),
                'guardian_whatsapp_local' => $phoneSvc->extractLocalNumber($parent->guardian_whatsapp, $gCc),
            ] : null,
            'guardians' => $guardians,
            'created_at' => $s->created_at->toIso8601String(),
            'updated_at' => $s->updated_at->toIso8601String(),
        ];
    }

    /**
     * Human-readable transport assignment for parents / student 360.
     *
     * @return array<string, mixed>
     */
    protected function formatTransport(Student $s): array
    {
        $assignment = $s->relationLoaded('assignments')
            ? $s->assignments->first()
            : $s->assignments()->with([
                'morningTrip.vehicle',
                'eveningTrip.vehicle',
                'morningDropOffPoint',
                'eveningDropOffPoint',
            ])->first();

        $legacyMode = null;
        if ($s->drop_off_point_other && strtoupper(trim((string) $s->drop_off_point_other)) === 'OWN MEANS') {
            $legacyMode = 'own_means';
        } elseif ($s->trip_id || $s->drop_off_point_id) {
            $legacyMode = 'trip';
        }

        return [
            'mode' => $legacyMode,
            'summary' => $this->transportSummary($s, $assignment),
            'morning' => $assignment ? [
                'trip_id' => $assignment->morning_trip_id,
                'trip_name' => $assignment->morningTrip?->trip_name,
                'vehicle' => $assignment->morningTrip?->vehicle?->vehicle_number,
                'drop_off_point_id' => $assignment->morning_drop_off_point_id,
                'drop_off_point' => $assignment->morningDropOffPoint?->name,
            ] : null,
            'evening' => $assignment ? [
                'trip_id' => $assignment->evening_trip_id,
                'trip_name' => $assignment->eveningTrip?->trip_name,
                'vehicle' => $assignment->eveningTrip?->vehicle?->vehicle_number,
                'drop_off_point_id' => $assignment->evening_drop_off_point_id,
                'drop_off_point' => $assignment->eveningDropOffPoint?->name,
            ] : null,
            'legacy' => [
                'trip_id' => $s->trip_id,
                'trip_name' => $s->trip?->trip_name,
                'vehicle' => $s->trip?->vehicle?->vehicle_number,
                'drop_off_point_id' => $s->drop_off_point_id,
                'drop_off_point' => $s->dropOffPoint?->name,
                'drop_off_point_other' => $s->drop_off_point_other,
            ],
        ];
    }

    protected function transportSummary(Student $s, $assignment): string
    {
        if ($assignment && ($assignment->morning_trip_id || $assignment->evening_trip_id)) {
            $parts = [];
            if ($assignment->morningTrip) {
                $parts[] = 'Morning: '.$assignment->morningTrip->trip_name
                    .($assignment->morningDropOffPoint?->name ? ' · '.$assignment->morningDropOffPoint->name : '');
            }
            if ($assignment->eveningTrip) {
                $parts[] = 'Evening: '.$assignment->eveningTrip->trip_name
                    .($assignment->eveningDropOffPoint?->name ? ' · '.$assignment->eveningDropOffPoint->name : '');
            }

            return implode(' · ', $parts);
        }

        if ($s->drop_off_point_other && strtoupper(trim((string) $s->drop_off_point_other)) === 'OWN MEANS') {
            return 'Own means';
        }

        $bits = array_filter([
            $s->trip?->trip_name,
            $s->trip?->vehicle?->vehicle_number,
            $s->dropOffPoint?->name,
            $s->drop_off_point_other,
        ]);

        return $bits ? implode(' · ', $bits) : 'No transport assigned';
    }

    /**
     * Resolve outstanding balance + cleared/pending flag via StudentBalanceService.
     * Cached per-request so re-formatting the same student is cheap.
     */
    protected function resolveFeeStatus(Student $s): array
    {
        static $cache = [];
        if (isset($cache[$s->id])) {
            return $cache[$s->id];
        }
        try {
            $balance = (float) \App\Services\StudentBalanceService::getTotalOutstandingBalance($s);
        } catch (\Throwable $e) {
            $balance = 0.0;
        }
        $status = $balance > 0 ? 'pending' : 'cleared';
        return $cache[$s->id] = ['status' => $status, 'balance' => round($balance, 2)];
    }
}

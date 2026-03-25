<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Services\PhoneNumberService;
use App\Services\StudentBalanceService;
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
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $user->applyTeacherStudentFilter($query);
        }

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('middle_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('admission_number', 'like', $search);
            });
        }
        if ($request->filled('name')) {
            $search = '%' . addcslashes($request->name, '%_\\') . '%';
            $query->where(fn($q) => $q->where('first_name', 'like', $search)
                ->orWhere('middle_name', 'like', $search)
                ->orWhere('last_name', 'like', $search));
        }
        if ($request->filled('classroom_id') || $request->filled('class_id')) {
            $query->where('classroom_id', $request->classroom_id ?? $request->class_id);
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }

        $paginated = $query->orderBy('first_name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($s) => $this->formatStudent($s))->values();

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
        $student = Student::with(['parent', 'classroom', 'stream', 'category'])->findOrFail($id);
        $user = $request->user();

        // Teachers can only view students from their assigned classes
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (!$query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        return response()->json(['success' => true, 'data' => $this->formatStudent($student)]);
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
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
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

        $feesBalance = (float) StudentBalanceService::getTotalOutstandingBalance($student);

        return response()->json([
            'success' => true,
            'data' => [
                'attendance_percentage' => $attendancePct,
                'expected_school_days' => $expectedSchoolDays,
                'attendance_records_count' => $records->count(),
                'attendance_days_marked' => $records->count(),
                'fees_balance' => round($feesBalance, 2),
                'exam_average' => null,
            ],
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
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $query = Student::where('id', $id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }

        $year = (int) $request->year;
        $month = (int) $request->month;

        $rows = Attendance::where('student_id', $student->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->map(fn ($a) => [
                'date' => $a->date->format('Y-m-d'),
                'status' => $a->status,
                'is_excused' => (bool) $a->is_excused,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    protected function formatStudent(Student $s): array
    {
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
            'drop_off_point_id' => $s->drop_off_point_id,
            'drop_off_point_other' => $s->drop_off_point_other,
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
}

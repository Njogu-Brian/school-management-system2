<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\DropOffPoint;
use App\Models\Trip;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\FeeConcession;
use App\Services\FeePostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\SMSService;
use App\Services\ArchiveStudentService;
use App\Services\RestoreStudentService;
use App\Services\TransportFeeService;
use Illuminate\Support\Facades\Mail;
use App\Models\CommunicationTemplate;
use App\Mail\GenericMail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentTemplateExport;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    protected $smsService;
    protected $archiveService;
    protected $restoreService;

    public function __construct(
        SMSService $smsService,
        ArchiveStudentService $archiveService,
        RestoreStudentService $restoreService
    )
    {
        $this->smsService = $smsService;
        $this->archiveService = $archiveService;
        $this->restoreService = $restoreService;
    }

    /**
     * List students with filtering and pagination
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = $request->has('showArchived')
            ? Student::withArchived()->with(['parent','classroom','stream','category'])
            : Student::with(['parent','classroom','stream','category'])->where('archive', 0);

        if ($request->filled('name')) {
            $name = $request->name;
            $searchTerm = '%' . addcslashes($name, '%_\\') . '%';
            $query->where(fn($q) => $q->where('first_name','like', $searchTerm)
                ->orWhere('middle_name','like', $searchTerm)
                ->orWhere('last_name','like', $searchTerm));
        }

        if ($request->filled('admission_number')) {
            $searchTerm = '%' . addcslashes($request->admission_number, '%_\\') . '%';
            $query->where('admission_number','like', $searchTerm);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id); // fixed key
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }

        // eager
        $students = $query->orderBy('first_name')->paginate($perPage)->withQueryString();

        $classrooms = Classroom::orderBy('name')->get();
        $streams    = Stream::orderBy('name')->get();

        // birthdays this week (for badge) - optimized query
        // Get day of year for start and end of week in current year
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startDay = $startOfWeek->dayOfYear;
        $endDay = $endOfWeek->dayOfYear;
        
        // Handle year boundary crossing
        if ($endDay < $startDay) {
            // Week crosses year boundary
            $thisWeekBirthdays = Student::whereNotNull('dob')
                ->where(function($q) use ($startDay, $endDay) {
                    $q->whereRaw('DAYOFYEAR(dob) >= ?', [$startDay])
                      ->orWhereRaw('DAYOFYEAR(dob) <= ?', [$endDay]);
                })
                ->pluck('id')
                ->toArray();
        } else {
            $thisWeekBirthdays = Student::whereNotNull('dob')
                ->whereRaw('DAYOFYEAR(dob) BETWEEN ? AND ?', [$startDay, $endDay])
                ->pluck('id')
                ->toArray();
        }

        return view('students.index', compact('students','classrooms','streams','thisWeekBirthdays'));
    }

    /**
     * Parents contact list: child name, class, admission number, father/mother name, phone, email, WhatsApp.
     * Filter by class. No guardian column.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function parentsContact(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $query = Student::with(['parent', 'classroom', 'stream'])
            ->where('archive', 0)
            ->where('is_alumni', false);

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('name')) {
            $name = $request->name;
            $searchTerm = '%' . addcslashes($name, '%_\\') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('middle_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm);
            });
        }
        if ($request->filled('admission_number')) {
            $searchTerm = '%' . addcslashes($request->admission_number, '%_\\') . '%';
            $query->where('admission_number', 'like', $searchTerm);
        }

        $students = $query->orderBy('first_name')->paginate($perPage)->withQueryString();
        $classrooms = Classroom::orderBy('name')->get();

        return view('students.parents_contact', compact('students', 'classrooms'));
    }

    /**
     * Archived students listing
     */
    public function archived(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $students = Student::withArchived()
            ->where('archive', 1)
            ->where('is_alumni', false)
            ->with(['parent','classroom','stream','category'])
            ->orderByDesc('archived_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('students.archived', compact('students'));
    }

    /**
     * Alumni and archived students listing with comprehensive view
     */
    public function alumniAndArchived(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $type = $request->input('type', 'all'); // all, alumni, archived
        
        $query = Student::withArchived()
            ->where(function($q) use ($type) {
                if ($type === 'alumni') {
                    $q->where('is_alumni', true);
                } elseif ($type === 'archived') {
                    $q->where('archive', 1)->where('is_alumni', false);
                } else {
                    // Show both alumni and archived
                    $q->where(function($subQ) {
                        $subQ->where('is_alumni', true)
                             ->orWhere('archive', 1);
                    });
                }
            })
            ->with(['parent','classroom','stream','category']);

        // Apply filters
        if ($request->filled('name')) {
            $name = $request->name;
            $searchTerm = '%' . addcslashes($name, '%_\\') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        if ($request->filled('admission_number')) {
            $searchTerm = '%' . addcslashes($request->admission_number, '%_\\') . '%';
            $query->where('admission_number', 'like', $searchTerm);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }

        // Order by: alumni_date for alumni, archived_at for archived
        $students = $query->orderByRaw('CASE 
            WHEN is_alumni = 1 THEN alumni_date 
            WHEN archive = 1 THEN archived_at 
            ELSE created_at 
        END DESC')
        ->paginate($perPage)
        ->withQueryString();

        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();

        return view('students.alumni_and_archived', compact('students', 'type', 'classrooms', 'streams'));
    }

    /**
     * Get student details for AJAX modal view
     */
    public function detailsAjax($id)
    {
        try {
            $student = Student::withArchived()
                ->with(['parent', 'classroom', 'stream', 'category', 'family'])
                ->findOrFail($id);

            // Financial data
            $totalOutstanding = $student->getTotalOutstandingBalance();
            $invoiceBalance = $student->getInvoiceBalance();
            $balanceBroughtForward = $student->getBalanceBroughtForward();
            $recentInvoices = \App\Models\Invoice::where('student_id', $student->id)->latest()->limit(10)->get();
            $recentPayments = \App\Models\Payment::where('student_id', $student->id)->latest()->limit(10)->get();

            // Attendance data
            $recentAttendance = \App\Models\Attendance::where('student_id', $student->id)
                ->latest('date')
                ->limit(30)
                ->get();
            $attendanceStats = [
                'present' => $recentAttendance->where('status', 'present')->count(),
                'absent' => $recentAttendance->where('status', 'absent')->count(),
                'late' => $recentAttendance->where('status', 'late')->count(),
                'total' => $recentAttendance->count(),
            ];
            if ($attendanceStats['total'] > 0) {
                $attendanceStats['percent'] = round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1);
            } else {
                $attendanceStats['percent'] = 0;
            }

            // Academic history
            $academicHistory = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->with(['classroom', 'stream', 'academicYear'])
                ->latest('enrollment_date')
                ->limit(10)
                ->get();

            // Disciplinary records
            $disciplinaryRecords = \App\Models\StudentDisciplinaryRecord::where('student_id', $student->id)
                ->with(['reportedBy', 'actionTakenBy'])
                ->latest()
                ->limit(10)
                ->get();

            $html = view('students.partials.details_modal_content', compact(
                'student',
                'totalOutstanding',
                'invoiceBalance',
                'balanceBroughtForward',
                'recentInvoices',
                'recentPayments',
                'recentAttendance',
                'attendanceStats',
                'academicHistory',
                'disciplinaryRecords'
            ))->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            \Log::error('Error loading student details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading student details']);
        }
    }

    /**
     * Show form to create a new student
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $students   = Student::all(); // optional, not needed with modal search
        $categories = StudentCategory::orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $streams    = Stream::orderBy('name')->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $trips = Trip::orderBy('trip_name')->get();
        $countryCodes = $this->getCountryCodes();

        return view('students.create', [
            'students' => $students,
            'categories' => $categories,
            'classrooms' => $classrooms,
            'streams' => $streams,
            'dropOffPoints' => $dropOffPoints,
            'trips' => $trips,
            'countryCodes' => $countryCodes,
        ]);
    }
    /**
     * Store a new student
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // If this request came from the filters (no student payload), bail early to avoid noisy validation/logs
        if (!$request->filled('first_name') || !$request->filled('last_name')) {
            return redirect()->route('students.index');
        }
        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }
        // Normalize stream_id: empty string or non-numeric (e.g. "No Active Streams") => null
        $streamId = $request->input('stream_id');
        if ($streamId === '' || $streamId === null || !is_numeric($streamId) || (int)$streamId < 1) {
            $request->merge(['stream_id' => null]);
        }
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|string',
                'dob' => 'required|date',
                'classroom_id' => 'required|exists:classrooms,id',
                'stream_id' => 'nullable|exists:streams,id',
                'category_id' => 'required|exists:student_categories,id',
                'trip_id' => 'nullable|exists:trips,id',
                'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
                'drop_off_point_other' => 'nullable|string|max:255',
                'father_phone_country_code' => 'nullable|string|max:8',
                'mother_phone_country_code' => 'nullable|string|max:8',
                'guardian_phone_country_code' => 'nullable|string|max:8',
                'marital_status' => 'nullable|in:married,single_parent,co_parenting',
                'father_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'mother_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'guardian_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'father_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'mother_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'guardian_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
                'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'has_allergies' => 'nullable|boolean',
                'allergies_notes' => 'nullable|string',
                'is_fully_immunized' => 'nullable|boolean',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => ['nullable','string','max:80','regex:/^[\+]?[\d\s\-\(\)]{4,25}(?:\s+[a-zA-Z\s\-\(\)\.\,]+)?$/'],
                'residential_area' => 'nullable|string|max:255',
                'preferred_hospital' => 'nullable|string|max:255',
                'nemis_number' => 'nullable|string',
                'knec_assessment_number' => 'nullable|string',
                'transport_fee_amount' => 'nullable|numeric|min:0',
                // Extended demographics
                'religion' => 'nullable|string|max:255',
                'allergies' => 'nullable|string',
                'chronic_conditions' => 'nullable|string',
                'previous_schools' => 'nullable|string',
                'transfer_reason' => 'nullable|string',
                'has_special_needs' => 'nullable|boolean',
                'special_needs_description' => 'nullable|string',
                'learning_disabilities' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,graduated,transferred,expelled,suspended',
                'admission_date' => 'nullable|date',
            ]);

            // Require stream if classroom has streams (primary + pivot)
            $classroomId = (int)$request->classroom_id;
            $streamId = $request->stream_id;
            $classroom = \App\Models\Academics\Classroom::withCount(['streams', 'primaryStreams'])->find($classroomId);
            $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
            if ($classroomHasStreams && !$streamId) {
                return back()->withInput()->with('error', 'Please select a stream for the chosen classroom.');
            }

            // Enforce at least one parent/guardian name+phone
            $parentName = $request->father_name ?: $request->mother_name ?: $request->guardian_name;
            $parentPhone = $request->father_phone ?: $request->mother_phone ?: $request->guardian_phone;
            if (!$parentName || !$parentPhone) {
                return back()->withInput()->with('error', 'At least one parent/guardian name and phone is required.');
            }

            // Handle family linkage
            $familyId = $request->input('family_id');

            // If chosen a student to copy family from
            if (!$familyId && $request->filled('copy_family_from_student_id')) {
                $ref = Student::withArchived()->find($request->copy_family_from_student_id);
                if ($ref && $ref->family_id) {
                    $familyId = $ref->family_id;
                } elseif ($ref && $request->boolean('create_family_from_parent')) {
                    // Create a family and assign both (if ref lacks family_id)
                    $fam = \App\Models\Family::create([
                        'guardian_name' => $ref->parent->guardian_name ?? ($ref->parent->father_name ?? $ref->parent->mother_name ?? 'Family '.$ref->admission_number),
                        'phone' => $ref->parent->father_phone ?? $ref->parent->mother_phone ?? $ref->parent->guardian_phone,
                        'email' => $ref->parent->father_email ?? $ref->parent->mother_email ?? $ref->parent->guardian_email,
                    ]);
                    $ref->update(['family_id'=>$fam->id]);
                    $familyId = $fam->id;
                }
            } elseif (!$familyId && $request->boolean('create_family_from_parent')) {
                // Create new family for THIS student using parent info (will be populated after parent is created)
                $fam = \App\Models\Family::create([
                    'guardian_name' => 'New Family', // Will be auto-populated when first student is linked
                    'phone'         => null,
                    'email'         => null,
                ]);
                $familyId = $fam->id;
            }
            // Normalize country codes
            $fatherCountryCode = $this->normalizeCountryCode($request->input('father_phone_country_code', '+254'));
            $motherCountryCode = $this->normalizeCountryCode($request->input('mother_phone_country_code', '+254'));
            $guardianCountryCode = $this->normalizeCountryCode($request->input('guardian_phone_country_code', '+254'));
            
            // Create ParentInfo with defaults for country codes and normalized phone numbers
            $fatherPhone = $this->formatPhoneWithCode($request->father_phone, $fatherCountryCode);
            $fatherWhatsapp = $this->formatPhoneWithCode($request->father_whatsapp, $fatherCountryCode);
            $motherPhone = $this->formatPhoneWithCode($request->mother_phone, $motherCountryCode);
            $motherWhatsapp = $this->formatPhoneWithCode($request->mother_whatsapp, $motherCountryCode);
            $guardianPhone = $this->formatPhoneWithCode($request->guardian_phone, $guardianCountryCode);
            $guardianWhatsapp = $this->formatPhoneWithCode($request->guardian_whatsapp, $guardianCountryCode);
            $parentData = [
                'father_name' => $request->father_name,
                'father_phone' => $fatherPhone,
                'father_whatsapp' => $fatherWhatsapp,
                'father_email' => $request->father_email,
                'father_id_number' => $request->father_id_number,
                'mother_name' => $request->mother_name,
                'mother_phone' => $motherPhone,
                'mother_whatsapp' => $motherWhatsapp,
                'mother_email' => $request->mother_email,
                'mother_id_number' => $request->mother_id_number,
                'guardian_name' => $request->guardian_name,
                'guardian_phone' => $guardianPhone,
                'guardian_whatsapp' => $guardianWhatsapp,
                'guardian_email' => $request->guardian_email,
                'guardian_relationship' => $request->guardian_relationship,
                'marital_status' => $request->marital_status,
                'father_phone_country_code' => $fatherCountryCode,
                'mother_phone_country_code' => $motherCountryCode,
                'guardian_phone_country_code' => $guardianCountryCode,
            ];

            $parent = ParentInfo::create($parentData);
            $userId = auth()->id();
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'father_phone', $request->father_phone, $fatherPhone, $fatherCountryCode, 'student_create', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'father_whatsapp', $request->father_whatsapp, $fatherWhatsapp, $fatherCountryCode, 'student_create', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'mother_phone', $request->mother_phone, $motherPhone, $motherCountryCode, 'student_create', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'mother_whatsapp', $request->mother_whatsapp, $motherWhatsapp, $motherCountryCode, 'student_create', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'guardian_phone', $request->guardian_phone, $guardianPhone, $guardianCountryCode, 'student_create', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $parent->id, 'guardian_whatsapp', $request->guardian_whatsapp, $guardianWhatsapp, $guardianCountryCode, 'student_create', $userId);

            $admission_number = $this->generateNextAdmissionNumber();

            $dropOffPointLabel = null;
            if ($request->filled('drop_off_point_other')) {
                $dropOffPointLabel = $request->drop_off_point_other;
            } elseif ($request->filled('drop_off_point_id')) {
                $dropOffPointLabel = optional(DropOffPoint::find($request->drop_off_point_id))->name;
            }

            $studentData = $request->only([
                'first_name', 'middle_name', 'last_name', 'gender', 'dob',
                'classroom_id', 'stream_id', 'category_id',
                'trip_id', 'drop_off_point_id', 'drop_off_point_other',
                'has_allergies', 'allergies_notes', 'is_fully_immunized',
                'emergency_contact_name',
                'residential_area', 'preferred_hospital',
                'nemis_number', 'knec_assessment_number',
                'religion',
                'allergies', 'chronic_conditions',
                'previous_schools', 'transfer_reason', 'has_special_needs',
                'special_needs_description', 'learning_disabilities',
                'status', 'admission_date'
            ]);
            
            // Normalize gender to lowercase
            if (isset($studentData['gender'])) {
                $studentData['gender'] = strtolower(trim($studentData['gender']));
            }
            
            // Normalize DOB - empty string to null
            if (isset($studentData['dob']) && empty($studentData['dob'])) {
                $studentData['dob'] = null;
            }
            
            $emergencyPhone = $this->formatPhoneWithCode(
                $request->emergency_contact_phone,
                $request->input('emergency_contact_country_code', '+254')
            );
            $student = Student::create(array_merge(
                $studentData,
                [
                    'admission_number' => $admission_number,
                    'parent_id' => $parent->id,
                    'family_id' => $familyId,
                    'drop_off_point' => $dropOffPointLabel,
                    'emergency_contact_phone' => $emergencyPhone,
                ]
            ));
            $this->logPhoneNormalization(Student::class, $student->id, 'emergency_contact_phone', $request->emergency_contact_phone, $emergencyPhone, $request->input('emergency_contact_country_code', '+254'), 'student_create', $userId);
            
            // Handle photo upload
            if ($request->hasFile('photo')) {
                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }
                $student->photo_path = $request->file('photo')->store('students/photos', 'public');
                $student->save();
            }
            
            $this->handleParentIdUploads($parent, $request);

            // Auto-populate family details from parent if family was created
            if ($familyId) {
                $family = \App\Models\Family::find($familyId);
                if ($family && (!$family->guardian_name || $family->guardian_name === 'New Family')) {
                    $family->update([
                        'guardian_name' => $parent->guardian_name ?? $parent->father_name ?? $parent->mother_name ?? 'Family',
                        'phone' => $family->phone ?: ($parent->guardian_phone ?? $parent->father_phone ?? $parent->mother_phone),
                        'email' => $family->email ?: ($parent->guardian_email ?? $parent->father_email ?? $parent->mother_email),
                    ]);
                }
            }

            $this->sendAdmissionCommunication($student, $parent);
            
            // Charge fees for newly admitted student (this will create the invoice)
            try {
                \App\Services\FeePostingService::chargeFeesForNewStudent($student);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to charge fees for new student: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                ]);
            }
            
            // Create transport fee AFTER invoice is created, so it can be synced properly
            if ($request->boolean('needs_transport') && $request->filled('transport_fee_amount')) {
                try {
                    TransportFeeService::upsertFee([
                        'student_id' => $student->id,
                        'amount' => $request->transport_fee_amount,
                        'drop_off_point_id' => $student->drop_off_point_id,
                        'drop_off_point_name' => $dropOffPointLabel,
                        'source' => 'admission',
                        'note' => 'Captured during student creation',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Transport fee capture failed during student creation', [
                        'student_id' => $student->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($request->filled('save_add_another')) {
                return redirect()->route('students.create')->with('success', 'Student created. You can add another now.');
            }

            return redirect()->route('students.index')->with('success', 'Student created successfully.');
        } catch (\Exception $e) {
            Log::error('Student Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to create student. Please check logs for details.');
        }
    }

    /**
     * Edit student
     */
    public function edit($id)
    {
        $student      = Student::withArchived()->with(['parent','classroom','stream','category'])->findOrFail($id);
        $categories   = StudentCategory::orderBy('name')->get();
        $classrooms   = Classroom::orderBy('name')->get();
        $streams      = Stream::orderBy('name')->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $trips = Trip::orderBy('trip_name')->get();
        $countryCodes = $this->getCountryCodes();
        $familyMembers = $student->family_id
            ? Student::where('family_id',$student->family_id)->where('id','!=',$student->id)->get()
            : collect();

        return view('students.edit', compact('student','categories','classrooms','streams','familyMembers','dropOffPoints','trips','countryCodes'));
    }
    /**
     * Update student
     */
   public function update(Request $request, $id)
    {
        \Log::info('Student Update: Method called', [
            'student_id' => $id,
            'method' => $request->method(),
            'has_csrf' => $request->has('_token'),
            'request_data_keys' => array_keys($request->all()),
        ]);

        try {
            $student = Student::withArchived()->findOrFail($id);
            \Log::info('Student Update: Student found', ['student_id' => $student->id, 'name' => $student->full_name]);

            if ($request->input('drop_off_point_id') === 'other') {
                $request->merge(['drop_off_point_id' => null]);
            }
            // Normalize stream_id: empty string or non-numeric (e.g. "No Active Streams") => null
            $streamId = $request->input('stream_id');
            if ($streamId === '' || $streamId === null || !is_numeric($streamId) || (int)$streamId < 1) {
                $request->merge(['stream_id' => null]);
            }

            \Log::info('Student Update: Starting validation');
            $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => [
                'nullable',
                'exists:streams,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->filled('classroom_id')) {
                        $stream = Stream::find($value);
                        if ($stream) {
                            $classroomId = $request->classroom_id;
                            $isValidStream = $stream->classroom_id == $classroomId || 
                                            $stream->classrooms->contains('id', $classroomId);
                            if (!$isValidStream) {
                                $fail('The selected stream does not belong to the selected classroom.');
                            }
                        }
                    } elseif ($value && !$request->filled('classroom_id')) {
                        // If stream is set but classroom is not being changed, validate against current classroom
                        $student = Student::find($id ?? null);
                        if ($student && $student->classroom_id) {
                            $stream = Stream::find($value);
                            if ($stream) {
                                $isValidStream = $stream->classroom_id == $student->classroom_id || 
                                                $stream->classrooms->contains('id', $student->classroom_id);
                                if (!$isValidStream) {
                                    $fail('The selected stream does not belong to the student\'s current classroom.');
                                }
                            }
                        }
                    }
                },
            ],
            'category_id' => 'required|exists:student_categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'father_phone_country_code' => 'nullable|string|max:8',
            'mother_phone_country_code' => 'nullable|string|max:8',
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'father_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable','string','max:80','regex:/^[\+]?[\d\s\-\(\)]{4,25}(?:\s+[a-zA-Z\s\-\(\)\.\,]+)?$/'],
                'residential_area' => 'required|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
            // Extended demographics
            'religion' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'previous_schools' => 'nullable|string',
            'transfer_reason' => 'nullable|string',
            'has_special_needs' => 'nullable|boolean',
            'special_needs_description' => 'nullable|string',
            'learning_disabilities' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,graduated,transferred,expelled,suspended',
            'admission_date' => 'nullable|date',
            'graduation_date' => 'nullable|date',
            'transfer_date' => 'nullable|date',
            'transfer_to_school' => 'nullable|string|max:255',
            'status_change_reason' => 'nullable|string',
            'is_readmission' => 'nullable|boolean',
        ]);
            \Log::info('Student Update: Validation passed', ['validated_keys' => array_keys($validated)]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Student Update: Validation failed', [
                'student_id' => $id,
                'errors' => $e->errors(),
                'input' => $request->except(['_token', '_method', 'password', 'password_confirmation']),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Student Update: Exception during validation', [
                'student_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        try {
        $currentYear = get_current_academic_year();
        $currentTerm = get_current_term_number();
        $currentTermModel = get_current_term_model();
        $oldCategoryId = $student->category_id;
        $incomingCategoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $newCategoryId = ($incomingCategoryId && $incomingCategoryId > 0) ? $incomingCategoryId : $oldCategoryId;

        $hasCurrentInvoice = $currentYear && $currentTerm && Invoice::where('student_id', $student->id)
            ->where('year', $currentYear)
            ->where('term', $currentTerm)
            ->exists();
        $termOpen = $currentTermModel && $currentTermModel->closing_date
            ? now()->lte($currentTermModel->closing_date)
            : true;
        $shouldRebill = $hasCurrentInvoice && $termOpen;

        if ($incomingCategoryId && $incomingCategoryId > 0 && $oldCategoryId !== $newCategoryId && $shouldRebill && !$request->boolean('confirm_category_change')) {
            $preview = $this->buildCategoryChangePreview($student, $newCategoryId, $currentYear, $currentTerm);

            return view('students.category_change_preview', [
                'student' => $student,
                'oldCategory' => StudentCategory::find($oldCategoryId),
                'newCategory' => StudentCategory::find($newCategoryId),
                'existingInvoice' => $preview['existing_invoice'],
                'diffs' => $preview['diffs'],
                'beforeTotal' => $preview['before_total'],
                'afterTotal' => $preview['after_total'],
                'discountWarning' => $preview['discount_warning'],
                'payload' => $request->all() + ['confirm_category_change' => 1],
            ]);
        }

        $updateData = $request->only([
            'first_name', 'middle_name', 'last_name', 'gender', 'dob',
            'classroom_id', 'stream_id', 'category_id',
            'trip_id', 'drop_off_point_id', 'drop_off_point_other',
            'has_allergies', 'allergies_notes', 'is_fully_immunized',
            'emergency_contact_name',
            'residential_area', 'preferred_hospital',
            'nemis_number', 'knec_assessment_number',
            'religion',
            'allergies', 'chronic_conditions',
            'previous_schools', 'transfer_reason', 'has_special_needs',
            'special_needs_description', 'learning_disabilities',
            'status', 'admission_date', 'graduation_date', 'transfer_date',
            'transfer_to_school', 'status_change_reason', 'is_readmission'
        ]);

        if (!$incomingCategoryId || $incomingCategoryId <= 0) {
            unset($updateData['category_id']);
        }
        
        // Normalize gender to lowercase
        if (isset($updateData['gender'])) {
            $updateData['gender'] = strtolower(trim($updateData['gender']));
        }
        
        // Normalize DOB - empty string to null
        if (isset($updateData['dob']) && empty($updateData['dob'])) {
            $updateData['dob'] = null;
        }
        $emergencyPhone = $this->formatPhoneWithCode(
            $request->emergency_contact_phone,
            '+254'
        );
        $updateData['emergency_contact_phone'] = $emergencyPhone;
        $this->logPhoneNormalization(
            Student::class,
            $student->id,
            'emergency_contact_phone',
            $student->emergency_contact_phone,
            $emergencyPhone,
            '+254',
            'student_update',
            auth()->id()
        );

        // Enforce at least one parent/guardian name+phone
        $parentName = $request->father_name ?: $request->mother_name ?: $request->guardian_name;
        $parentPhone = $request->father_phone ?: $request->mother_phone ?: $request->guardian_phone;
        if (!$parentName || !$parentPhone) {
            return back()->withInput()->with('error', 'At least one parent/guardian name and phone is required.');
        }

        // Require stream if classroom has streams (primary + pivot)
        $classroomId = (int)$request->classroom_id;
        $streamId = $request->stream_id;
        $classroom = \App\Models\Academics\Classroom::withCount(['streams', 'primaryStreams'])->find($classroomId);
        $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
        if ($classroomHasStreams && !$streamId) {
            return back()->withInput()->with('error', 'Please select a stream for the chosen classroom.');
        }

        $dropOffPointLabel = null;
        if ($request->filled('drop_off_point_other')) {
            $dropOffPointLabel = $request->drop_off_point_other;
        } elseif ($request->filled('drop_off_point_id')) {
            $dropOffPointLabel = optional(DropOffPoint::find($request->drop_off_point_id))->name;
        }
        $updateData['drop_off_point'] = $dropOffPointLabel;

        $categoryChanged = $incomingCategoryId && $incomingCategoryId > 0 && $oldCategoryId !== $newCategoryId;
        if ($categoryChanged && $shouldRebill && $request->boolean('confirm_category_change')) {
            $this->applyCategoryChangeRebilling($student, $newCategoryId, $currentYear, $currentTerm);
        }
        
        // If classroom is being changed, validate/clear stream
        if ($request->filled('classroom_id') && $request->classroom_id != $student->classroom_id) {
            $newClassroomId = $request->classroom_id;
            // If stream is set, ensure it belongs to the new classroom
            if ($request->filled('stream_id')) {
                $stream = Stream::find($request->stream_id);
                if ($stream) {
                    $isValidStream = $stream->classroom_id == $newClassroomId || 
                                    $stream->classrooms->contains('id', $newClassroomId);
                    if (!$isValidStream) {
                        // Clear stream if it doesn't belong to new classroom
                        $updateData['stream_id'] = null;
                    }
                }
            } else {
                // If classroom changes but no stream specified, clear existing stream
                $updateData['stream_id'] = null;
            }
        } elseif (!$request->filled('classroom_id') && $request->filled('stream_id')) {
            // If only stream is being changed, validate it belongs to current classroom
            if ($student->classroom_id) {
                $stream = Stream::find($request->stream_id);
                if ($stream) {
                    $isValidStream = $stream->classroom_id == $student->classroom_id || 
                                    $stream->classrooms->contains('id', $student->classroom_id);
                    if (!$isValidStream) {
                        return back()->withInput()->with('error', 'The selected stream does not belong to the student\'s current classroom.');
                    }
                }
            }
        }
        
        \Log::info('Student Update: About to update student', [
            'student_id' => $student->id,
            'update_data' => $updateData,
        ]);
        
        $student->update($updateData);
        \Log::info('Student Update: Student record updated', ['student_id' => $student->id]);

        // When transport is removed from the student (no drop-off, no trip), remove transport from their fee invoice
        $transportRemoved = !$student->drop_off_point_id && !$student->trip_id;
        if ($transportRemoved && $currentYear && $currentTerm) {
            try {
                TransportFeeService::upsertFee([
                    'student_id' => $student->id,
                    'amount' => 0,
                    'year' => $currentYear,
                    'term' => $currentTerm,
                    'drop_off_point_id' => null,
                    'drop_off_point_name' => null,
                    'source' => 'manual',
                    'note' => 'Transport removed from student profile',
                    'skip_invoice' => false,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Student update: could not remove transport from invoice', [
                    'student_id' => $student->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $student->photo_path = $request->file('photo')->store('students/photos', 'public');
            $student->save();
        }
        
        // Family mapping on update
        $familyId = $request->input('family_id');

        if (!$familyId && $request->filled('copy_family_from_student_id')) {
            $ref = Student::withArchived()->find($request->copy_family_from_student_id);
            if ($ref && $ref->family_id) {
                $familyId = $ref->family_id;
            } elseif ($ref && $request->boolean('create_family_from_parent')) {
                $fam = \App\Models\Family::create([
                    'guardian_name' => $ref->parent->guardian_name ?? ($ref->parent->father_name ?? $ref->parent->mother_name ?? 'Family '.$ref->admission_number),
                    'phone' => $ref->parent->father_phone ?? $ref->parent->mother_phone ?? $ref->parent->guardian_phone,
                    'email' => $ref->parent->father_email ?? $ref->parent->mother_email ?? $ref->parent->guardian_email,
                ]);
                $ref->update(['family_id'=>$fam->id]);
                $familyId = $fam->id;
            }
        } elseif (!$familyId && $request->boolean('create_family_from_parent')) {
            $fam = \App\Models\Family::create([
                'guardian_name' => $request->guardian_name ?? $request->father_name ?? $request->mother_name ?? 'New Family',
                'phone'         => $this->formatPhoneWithCode($request->guardian_phone, $request->input('guardian_phone_country_code', '+254'))
                    ?? $this->formatPhoneWithCode($request->father_phone, $request->input('father_phone_country_code', '+254'))
                    ?? $this->formatPhoneWithCode($request->mother_phone, $request->input('mother_phone_country_code', '+254')),
                'email'         => $request->guardian_email ?? $request->father_email ?? $request->mother_email,
            ]);
            $familyId = $fam->id;
        }

        if ($familyId) {
            $student->update(['family_id' => $familyId]);
        }

        if ($student->parent) {
            // Normalize country codes
            $fatherCountryCode = $this->normalizeCountryCode($request->input('father_phone_country_code', '+254'));
            $motherCountryCode = $this->normalizeCountryCode($request->input('mother_phone_country_code', '+254'));
            $guardianCountryCode = $this->normalizeCountryCode($request->input('guardian_phone_country_code', '+254'));
            $fatherPhone = $this->formatPhoneWithCode($request->father_phone, $fatherCountryCode);
            $fatherWhatsapp = $this->formatPhoneWithCode($request->father_whatsapp, $fatherCountryCode);
            $motherPhone = $this->formatPhoneWithCode($request->mother_phone, $motherCountryCode);
            $motherWhatsapp = $this->formatPhoneWithCode($request->mother_whatsapp, $motherCountryCode);
            $guardianPhone = $this->formatPhoneWithCode($request->guardian_phone, $guardianCountryCode);
            $guardianWhatsapp = $this->formatPhoneWithCode($request->guardian_whatsapp, $guardianCountryCode);
            
            $parentUpdateData = [
                'father_name' => $request->father_name,
                'father_phone' => $fatherPhone,
                'father_whatsapp' => $fatherWhatsapp,
                'father_email' => $request->father_email,
                'father_id_number' => $request->father_id_number,
                'mother_name' => $request->mother_name,
                'mother_phone' => $motherPhone,
                'mother_whatsapp' => $motherWhatsapp,
                'mother_email' => $request->mother_email,
                'mother_id_number' => $request->mother_id_number,
                'guardian_name' => $request->guardian_name,
                'guardian_phone' => $guardianPhone,
                'guardian_whatsapp' => $guardianWhatsapp,
                'guardian_email' => $request->guardian_email,
                'guardian_relationship' => $request->guardian_relationship,
                'marital_status' => $request->marital_status,
                'father_phone_country_code' => $fatherCountryCode,
                'mother_phone_country_code' => $motherCountryCode,
                'guardian_phone_country_code' => $guardianCountryCode,
            ];
            $userId = auth()->id();
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'father_phone', $student->parent->father_phone, $fatherPhone, $fatherCountryCode, 'student_update', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'father_whatsapp', $student->parent->father_whatsapp, $fatherWhatsapp, $fatherCountryCode, 'student_update', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'mother_phone', $student->parent->mother_phone, $motherPhone, $motherCountryCode, 'student_update', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'mother_whatsapp', $student->parent->mother_whatsapp, $motherWhatsapp, $motherCountryCode, 'student_update', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'guardian_phone', $student->parent->guardian_phone, $guardianPhone, $guardianCountryCode, 'student_update', $userId);
            $this->logPhoneNormalization(ParentInfo::class, $student->parent->id, 'guardian_whatsapp', $student->parent->guardian_whatsapp, $guardianWhatsapp, $guardianCountryCode, 'student_update', $userId);

            $student->parent->update($parentUpdateData);
            \Log::info('Student Update: Parent info updated', ['parent_id' => $student->parent->id]);
            $this->handleParentIdUploads($student->parent, $request);
        }

        \Log::info('Student Update: Update completed successfully', [
            'student_id' => $student->id,
            'changes' => $student->getChanges(),
        ]);

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Student Update: Exception during update process', [
                'student_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withInput()->with('error', 'Failed to update student: ' . $e->getMessage());
        }
    }

    /**
     * Build preview data for changing student category mid-term.
     */
    private function buildCategoryChangePreview(Student $student, int $newCategoryId, int $year, int $term): array
    {
        $invoice = Invoice::with(['items.votehead'])
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->first();

        $proposed = $this->getProposedItemsForCategory($student, $newCategoryId, $year, $term);
        $diffs = $this->buildDiffsForInvoice($invoice, $proposed, $student->id);

        $beforeTotal = 0;
        $discountWarning = false;
        if ($invoice) {
            $beforeTotal = $invoice->items
                ->where('status', 'active')
                ->sum(fn ($i) => ($i->amount ?? 0) - ($i->discount_amount ?? 0));
            $beforeTotal -= ($invoice->discount_amount ?? 0);
            $beforeTotal = max(0, $beforeTotal);

            $discountWarning = ($invoice->discount_amount ?? 0) > 0
                || $invoice->items->sum('discount_amount') > 0
                || FeeConcession::where('student_id', $student->id)->where('is_active', true)->exists();
        }

        $afterTotal = $proposed->sum('amount');

        return [
            'existing_invoice' => $invoice,
            'diffs' => $diffs,
            'before_total' => $beforeTotal,
            'after_total' => $afterTotal,
            'discount_warning' => $discountWarning,
        ];
    }

    /**
     * Apply category change: deactivate discounts and repost current-term invoice to new category structure.
     */
    private function applyCategoryChangeRebilling(Student $student, int $newCategoryId, int $year, int $term): void
    {
        $preview = $this->buildCategoryChangePreview($student, $newCategoryId, $year, $term);
        $diffs = $preview['diffs'];

        // Deactivate discounts and zero invoice discount amounts for current term
        FeeConcession::where('student_id', $student->id)->update(['is_active' => false]);
        if ($preview['existing_invoice']) {
            $preview['existing_invoice']->items()->update(['discount_amount' => 0]);
            $preview['existing_invoice']->update(['discount_amount' => 0]);
        }

        // Update student category before committing
        $student->update(['category_id' => $newCategoryId]);

        if ($diffs->isNotEmpty()) {
            $fps = new FeePostingService();
            $fps->commitWithTracking(
                $diffs,
                $year,
                $term,
                true,
                null,
                ['student_id' => $student->id]
            );
        }
    }

    /**
     * Handle parent ID document uploads, removing old files when replaced.
     */
    private function handleParentIdUploads(ParentInfo $parent, Request $request): void
    {
        $updates = [];

        if ($request->hasFile('father_id_document')) {
            if ($parent->father_id_document) {
                Storage::disk('private')->delete($parent->father_id_document);
            }
            $updates['father_id_document'] = $request->file('father_id_document')->store('parent_ids', 'private');
        }

        if ($request->hasFile('mother_id_document')) {
            if ($parent->mother_id_document) {
                Storage::disk('private')->delete($parent->mother_id_document);
            }
            $updates['mother_id_document'] = $request->file('mother_id_document')->store('parent_ids', 'private');
        }

        if (!empty($updates)) {
            $parent->update($updates);
        }
    }

    /**
     * Build proposed items for the new category and current class/stream.
     */
    private function getProposedItemsForCategory(Student $student, int $categoryId, int $year, int $term): \Illuminate\Support\Collection
    {
        $structureQuery = FeeStructure::with('charges.votehead')
            ->where('classroom_id', $student->classroom_id)
            ->where('is_active', true)
            ->where('year', $year)
            ->where('student_category_id', $categoryId);

        if ($student->stream_id) {
            $structureQuery->where(function ($q) use ($student) {
                $q->where('stream_id', $student->stream_id)
                    ->orWhereNull('stream_id');
            });
        } else {
            $structureQuery->whereNull('stream_id');
        }

        $structure = $structureQuery->orderByRaw('CASE WHEN stream_id IS NOT NULL THEN 0 ELSE 1 END')->first();
        if (!$structure) {
            return collect();
        }

        $tempStudent = clone $student;
        $tempStudent->category_id = $categoryId;

        return $structure->charges
            ->where('term', $term)
            ->filter(function ($charge) use ($tempStudent, $year, $term) {
                $votehead = $charge->votehead;
                if (!$votehead || !$votehead->is_mandatory) {
                    return false;
                }
                if ($this->isTransportVotehead($votehead)) {
                    return false;
                }
                return $votehead->canChargeForStudent($tempStudent, $year, $term);
            })
            ->map(function ($charge) {
                return [
                    'votehead_id' => $charge->votehead_id,
                    'votehead_name' => optional($charge->votehead)->name,
                    'amount' => (float) $charge->amount,
                    'origin' => 'structure',
                ];
            })
            ->values();
    }

    /**
     * Build diff collection between current invoice and proposed items.
     */
    private function buildDiffsForInvoice(?Invoice $invoice, \Illuminate\Support\Collection $proposed, int $studentId): \Illuminate\Support\Collection
    {
        $existingItems = $invoice
            ? $invoice->items->where('status', 'active')->map(function ($item) {
                return [
                    'id' => $item->id,
                    'votehead_id' => $item->votehead_id,
                    'amount' => (float) $item->amount,
                    'origin' => $item->source ?? 'structure',
                    'votehead_code' => $item->votehead?->code,
                    'votehead_name' => $item->votehead?->name,
                    'votehead_category' => $item->votehead?->category,
                ];
            })
            : collect();

        $diffs = collect();

        foreach ($proposed as $item) {
            $existing = $existingItems->firstWhere('votehead_id', $item['votehead_id']);
            if ($this->isTransportVoteheadFromArray($item)) {
                continue;
            }
            if ($existing) {
                if ($this->isTransportVoteheadFromArray($existing)) {
                    continue;
                }
                if (round($existing['amount'], 2) !== round($item['amount'], 2)) {
                    $diffs->push([
                        'action' => 'updated',
                        'student_id' => $studentId,
                        'votehead_id' => $item['votehead_id'],
                        'old_amount' => $existing['amount'],
                        'new_amount' => $item['amount'],
                        'invoice_item_id' => $existing['id'],
                        'origin' => $item['origin'] ?? 'structure',
                    ]);
                }
            } else {
                $diffs->push([
                    'action' => 'added',
                    'student_id' => $studentId,
                    'votehead_id' => $item['votehead_id'],
                    'old_amount' => 0,
                    'new_amount' => $item['amount'],
                    'invoice_item_id' => null,
                    'origin' => $item['origin'] ?? 'structure',
                ]);
            }
        }

        foreach ($existingItems as $existing) {
            if ($this->isTransportVoteheadFromArray($existing)) {
                continue;
            }
            if (!$proposed->contains('votehead_id', $existing['votehead_id'])) {
                // Keep optional/manual items when only structure/category diffs are expected
                if (in_array($existing['origin'], ['optional', 'manual'], true)) {
                    continue;
                }
                $diffs->push([
                    'action' => 'removed',
                    'student_id' => $studentId,
                    'votehead_id' => $existing['votehead_id'],
                    'old_amount' => $existing['amount'],
                    'new_amount' => 0,
                    'invoice_item_id' => $existing['id'],
                    'origin' => $existing['origin'] ?? 'structure',
                ]);
            }
        }

        return $diffs;
    }

    private function isTransportVotehead(?\App\Models\Votehead $votehead): bool
    {
        if (!$votehead) {
            return false;
        }

        $code = strtolower((string) $votehead->code);
        $name = strtolower((string) $votehead->name);
        $category = strtolower((string) $votehead->category);

        return $code === 'transport' || $name === 'transport' || $category === 'transport';
    }

    private function isTransportVoteheadFromArray(array $item): bool
    {
        $code = strtolower((string) ($item['votehead_code'] ?? ''));
        $name = strtolower((string) ($item['votehead_name'] ?? ''));
        $category = strtolower((string) ($item['votehead_category'] ?? ''));

        return $code === 'transport' || $name === 'transport' || $category === 'transport';
    }

    /**
     * Archive student
     */
    public function archive($id, Request $request)
    {
        $student = Student::withArchived()->findOrFail($id);
        try {
            $result = $this->archiveService->archive(
                $student,
                $request->input('reason'),
                auth()->id(),
                $request->input('archived_notes')
            );
            return redirect()->route('students.archived')->with('success', 'Student archived successfully.');
        } catch (\Throwable $e) {
            Log::error('Archive failed: '.$e->getMessage(), ['student_id' => $student->id]);
            return back()->with('error', 'Failed to archive student: '.$e->getMessage());
        }
    }

    /**
     * Restore student
     */
    public function restore($id, Request $request)
    {
        $student = Student::withArchived()->findOrFail($id);
        try {
            $this->restoreService->restore($student, $request->input('reason'), auth()->id());
            return redirect()->route('students.index')->with('success', 'Student restored successfully.');
        } catch (\Throwable $e) {
            Log::error('Restore failed: '.$e->getMessage(), ['student_id' => $student->id]);
            return back()->with('error', 'Failed to restore student: '.$e->getMessage());
        }
    }
    /**
     * Generate admission number
     */
    private function generateNextAdmissionNumber()
    {
        $prefix = Setting::get('student_id_prefix', 'ADM');
        $start = Setting::getInt('student_id_start', 1000);
        $counter = Setting::incrementValue('student_id_counter', 1, $start);
        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Bulk upload form
     */
    public function bulkForm()
    {
        return view('students.bulk');
    }

    /**
     * Bulk upload template
     */
    public function bulkTemplate()
    {
        $classrooms = Classroom::pluck('name')->toArray();
        $streams = Stream::pluck('name')->toArray();
        $categories = StudentCategory::pluck('name')->toArray();

        $sample = [[
            'admission_number' => '',
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Smith',
            'gender' => 'Male',
            'dob' => '2010-01-01',
            'classroom' => $classrooms[0] ?? '',
            'stream' => $streams[0] ?? '',
            'category' => $categories[0] ?? '',
            'transport_fee_amount' => '0',
            'residential_area' => 'Nairobi',
            'allergies' => '',
            'allergies_notes' => '',
            'has_allergies' => 'No',
            'is_fully_immunized' => 'Yes',
            'preferred_hospital' => 'Nairobi Hospital',
            'emergency_contact_name' => 'Aunt Jane',
            'emergency_contact_phone' => '+254712345678',
            'marital_status' => 'married',
            'father_name' => 'Mr. Smith',
            'father_phone_country_code' => '+254',
            'father_phone' => '712345678',
            'father_whatsapp' => '712345678',
            'father_email' => 'father@example.com',
            'father_id_number' => '12345678',
            'mother_name' => 'Mrs. Smith',
            'mother_phone_country_code' => '+254',
            'mother_phone' => '798765432',
            'mother_whatsapp' => '798765432',
            'mother_email' => 'mother@example.com',
            'mother_id_number' => '87654321',
            'guardian_name' => '',
            'guardian_phone_country_code' => '+254',
            'guardian_phone' => '',
            'guardian_whatsapp' => '',
            'guardian_email' => '',
            'guardian_relationship' => ''
        ]];

        return Excel::download(new StudentTemplateExport($sample, $classrooms, $streams, $categories), 'students_upload_template.xlsx');
    }

    /**
     * Bulk preview parse
     */
    public function bulkParse(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $rows = Excel::toArray([], $request->file('upload_file'))[0];
        $headers = array_map('trim', array_map('strtolower', $rows[0]));
        unset($rows[0]);

        $students = [];
        $existingAdNos = Student::pluck('admission_number')->toArray();

        foreach ($rows as $row) {
            $rowData = array_combine($headers, $row);

            // Map IDs
            $classroomId = Classroom::where('name', $rowData['classroom'] ?? '')->value('id');
            $streamId    = Stream::where('name', $rowData['stream'] ?? '')->value('id');
            $categoryId  = StudentCategory::where('name', $rowData['category'] ?? '')->value('id');

            $rowData['classroom_id']   = $classroomId;
            $rowData['stream_id']      = $streamId;
            $rowData['category_id']    = $categoryId;

            // Keep names for preview
            $rowData['classroom_name'] = $rowData['classroom'] ?? '';
            $rowData['stream_name']    = $rowData['stream'] ?? '';
            $rowData['category_name']  = $rowData['category'] ?? '';

            // ✅ Handle DOB conversion
            if (!empty($rowData['dob'])) {
                if (is_numeric($rowData['dob'])) {
                    // Excel serial number to date
                    $rowData['dob'] = gmdate('Y-m-d', ($rowData['dob'] - 25569) * 86400);
                } else {
                    try {
                        $rowData['dob'] = \Carbon\Carbon::parse($rowData['dob'])->toDateString();
                    } catch (\Exception $e) {
                        $rowData['dob'] = null;
                    }
                }
            } else {
                $rowData['dob'] = null;
            }

            // Handle boolean fields
            $rowData['has_special_needs'] = isset($rowData['has_special_needs']) ? 
                (in_array(strtolower($rowData['has_special_needs']), ['yes', '1', 'true', 'y']) ? 1 : 0) : 0;
            $rowData['has_allergies'] = isset($rowData['has_allergies']) ? 
                (in_array(strtolower($rowData['has_allergies']), ['yes', '1', 'true', 'y']) ? 1 : 0) : 0;
            $rowData['is_fully_immunized'] = isset($rowData['is_fully_immunized']) ? 
                (in_array(strtolower($rowData['is_fully_immunized']), ['yes', '1', 'true', 'y']) ? 1 : 0) : 0;

            // Normalize country codes (handle +ke or ke)
            $rowData['father_phone_country_code'] = $this->normalizeCountryCode($rowData['father_phone_country_code'] ?? '+254');
            $rowData['mother_phone_country_code'] = $this->normalizeCountryCode($rowData['mother_phone_country_code'] ?? '+254');
            $rowData['guardian_phone_country_code'] = $this->normalizeCountryCode($rowData['guardian_phone_country_code'] ?? '+254');
            $rowData['emergency_contact_country_code'] = $this->normalizeCountryCode($rowData['emergency_contact_country_code'] ?? '+254');
            $rowData['marital_status'] = $rowData['marital_status'] ?? null;

            // Normalize phones with country codes
            $rowData['father_phone'] = $this->formatPhoneWithCode($rowData['father_phone'] ?? null, $rowData['father_phone_country_code']);
            $rowData['father_whatsapp'] = $this->formatPhoneWithCode($rowData['father_whatsapp'] ?? null, $rowData['father_phone_country_code']);
            $rowData['mother_phone'] = $this->formatPhoneWithCode($rowData['mother_phone'] ?? null, $rowData['mother_phone_country_code']);
            $rowData['mother_whatsapp'] = $this->formatPhoneWithCode($rowData['mother_whatsapp'] ?? null, $rowData['mother_phone_country_code']);
            $rowData['guardian_phone'] = $this->formatPhoneWithCode($rowData['guardian_phone'] ?? null, $rowData['guardian_phone_country_code']);
            $rowData['guardian_whatsapp'] = $this->formatPhoneWithCode($rowData['guardian_whatsapp'] ?? null, $rowData['guardian_phone_country_code']);
            $rowData['emergency_contact_phone'] = $this->formatPhoneWithCode($rowData['emergency_contact_phone'] ?? null, $rowData['emergency_contact_country_code']);
            
            // Handle status field
            $rowData['status'] = $rowData['status'] ?? 'active';
            if (!in_array($rowData['status'], ['active', 'inactive', 'graduated', 'transferred', 'expelled', 'suspended'])) {
                $rowData['status'] = 'active';
            }

            // Transport fee (optional column)
            $transportFee = $rowData['transport_fee_amount'] ?? $rowData['transport_fee'] ?? null;
            $rowData['transport_fee_amount'] = is_numeric($transportFee) ? (float) $transportFee : null;

            // Validate required fields
            $rowData['valid'] =
                !empty($rowData['first_name']) &&
                !empty($rowData['last_name']) &&
                !empty($rowData['gender']) &&
                !empty($classroomId) &&
                !empty($categoryId);

            $rowData['existing'] = in_array($rowData['admission_number'], $existingAdNos);

            $students[] = $rowData;
        }

        $allValid = collect($students)->every(fn($s) => $s['valid']);
        return view('students.bulk_preview', compact('students', 'allValid'));
    }

    /**
     * Bulk import save
     */
    public function bulkImport(Request $request)
    {
        $data = $request->input('students', []);
        $imported = 0;
        $duplicates = [];

        foreach ($data as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            if (!$row['valid']) continue;

            $existing = Student::where('first_name', $row['first_name'])
                ->where('last_name', $row['last_name'])
                ->whereDate('dob', $row['dob'])
                ->first();

            if ($existing) {
                $duplicates[] = $row['first_name'] . ' ' . $row['last_name'] . ' (DOB: ' . $row['dob'] . ')';
                continue;
            }

            $isNew = empty($row['admission_number']);
            $admissionNumber = $isNew
                ? $this->generateNextAdmissionNumber()
                : $row['admission_number'];

            $parent = ParentInfo::create([
                'father_name' => $row['father_name'],
                'father_phone' => $row['father_phone'],
                'father_whatsapp' => $row['father_whatsapp'] ?? null,
                'father_email' => $row['father_email'],
                'father_id_number' => $row['father_id_number'],
                'father_phone_country_code' => $row['father_phone_country_code'] ?? '+254',
                'mother_name' => $row['mother_name'],
                'mother_phone' => $row['mother_phone'],
                'mother_whatsapp' => $row['mother_whatsapp'] ?? null,
                'mother_email' => $row['mother_email'],
                'mother_id_number' => $row['mother_id_number'],
                'mother_phone_country_code' => $row['mother_phone_country_code'] ?? '+254',
                'guardian_name' => $row['guardian_name'],
                'guardian_phone' => $row['guardian_phone'],
                'guardian_whatsapp' => $row['guardian_whatsapp'] ?? null,
                'guardian_email' => $row['guardian_email'],
                'guardian_phone_country_code' => $row['guardian_phone_country_code'] ?? '+254',
                'guardian_relationship' => $row['guardian_relationship'] ?? null,
                'marital_status' => $row['marital_status'] ?? null,
            ]);

            // Normalize gender to lowercase
            $gender = isset($row['gender']) ? strtolower(trim($row['gender'])) : null;
            // Normalize DOB - empty string to null
            $dob = !empty($row['dob']) ? $row['dob'] : null;
            
            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'],
                'gender' => $gender,
                'dob' => $dob,
                'classroom_id' => $row['classroom_id'],
                'stream_id' => $row['stream_id'] ?? null,
                'category_id' => $row['category_id'] ?? null,
                'parent_id' => $parent->id,
                // Identifiers
                'nemis_number' => $row['nemis_number'] ?? null,
                'knec_assessment_number' => $row['knec_assessment_number'] ?? null,
                // Extended Demographics
                'religion' => $row['religion'] ?? null,
                'residential_area' => $row['residential_area'] ?? null,
                // Medical
                'has_allergies' => isset($row['has_allergies']) ? (bool)$row['has_allergies'] : false,
                'allergies_notes' => $row['allergies_notes'] ?? null,
                'is_fully_immunized' => isset($row['is_fully_immunized']) ? (bool)$row['is_fully_immunized'] : false,
                'allergies' => $row['allergies'] ?? null,
                'chronic_conditions' => $row['chronic_conditions'] ?? null,
                'emergency_contact_name' => $row['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $row['emergency_contact_phone'] ?? null,
                'preferred_hospital' => $row['preferred_hospital'] ?? null,
                // Special Needs
                'has_special_needs' => isset($row['has_special_needs']) ? (bool)$row['has_special_needs'] : false,
                'special_needs_description' => $row['special_needs_description'] ?? null,
                'learning_disabilities' => $row['learning_disabilities'] ?? null,
                // Previous Schools
                'previous_schools' => $row['previous_schools'] ?? null,
                'transfer_reason' => $row['transfer_reason'] ?? null,
                // Status
                'status' => $row['status'] ?? 'active',
                'admission_date' => $row['admission_date'] ?? now()->toDateString(),
            ]);

            if (!empty($row['transport_fee_amount'])) {
                try {
                    TransportFeeService::upsertFee([
                        'student_id' => $student->id,
                        'amount' => $row['transport_fee_amount'],
                        'source' => 'bulk_import',
                        'note' => 'Captured during bulk student import',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Transport fee capture failed during bulk import', [
                        'student_id' => $student->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($isNew) {
                $this->sendAdmissionCommunication($student, $parent);
                
                // Charge fees for newly admitted student
                try {
                    \App\Services\FeePostingService::chargeFeesForNewStudent($student);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to charge fees for new student: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                    ]);
                }
            }

            $imported++;
        }

        $message = "{$imported} students imported successfully.";
        if (!empty($duplicates)) {
            $message .= " Skipped duplicates: " . implode(', ', $duplicates);
            return redirect()->route('students.index')->with('warning', $message);
        }

        return redirect()->route('students.index')->with('success', $message);
    }

    /**
     * Search students (for dropdowns/autocomplete)
     */
    public function search(Request $request)
    {
        try {
            $q = trim((string) $request->input('q', ''));

            if ($q === '') {
                return response()->json([]);
            }

            // Check if we should include alumni and archived students (for manual assignment only)
            $includeAlumniArchived = $request->boolean('include_alumni_archived', false);

            // Normalize for case-insensitive name search and admission search without spaces/punctuation
            $searchTerm = '%' . addcslashes(mb_strtolower($q, 'UTF-8'), '%_\\') . '%';
            $normalizedAdmission = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $q), 'UTF-8');

            $students = Student::query()
                ->when(!$includeAlumniArchived, function($query) {
                    // Exclude alumni and archived students by default
                    $query->where('archive', 0)
                          ->where('is_alumni', false);
                })
                ->where(function ($s) use ($searchTerm, $normalizedAdmission) {
                    $s->whereRaw('LOWER(first_name) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(admission_number) LIKE ?', [$searchTerm]);

                    // Admission number search tolerant of prefixes/suffixes/spaces/dashes/slashes
                    if ($normalizedAdmission !== '') {
                        $s->orWhereRaw(
                            'LOWER(REPLACE(REPLACE(REPLACE(admission_number, " ", ""), "-", ""), "/", "")) LIKE ?',
                            ['%' . $normalizedAdmission . '%']
                        );
                    }
                })
                ->with(['classroom', 'stream'])
                ->select('id', 'first_name', 'middle_name', 'last_name', 'admission_number', 'classroom_id', 'stream_id', 'family_id', 'archive', 'is_alumni')
                ->orderBy('first_name')
                ->limit(25)
                ->get();

            return response()->json($students->map(function ($st) use ($includeAlumniArchived) {
                $full = trim(implode(' ', array_filter([$st->first_name, $st->middle_name, $st->last_name])));
                
                // Get siblings (only include non-archived, non-alumni siblings unless include_alumni_archived is true)
                $siblings = [];
                if ($st->family_id) {
                    $siblingsQuery = Student::where('family_id', $st->family_id)
                        ->where('id', '!=', $st->id);
                    
                    if (!$includeAlumniArchived) {
                        $siblingsQuery->where('archive', 0)
                                     ->where('is_alumni', false);
                    }
                    
                    $siblings = $siblingsQuery->select('id', 'first_name', 'middle_name', 'last_name', 'admission_number', 'classroom_id', 'stream_id')
                        ->with(['classroom', 'stream'])
                        ->get()
                        ->map(function ($sib) {
                            $fullName = trim(implode(' ', array_filter([
                                $sib->first_name,
                                $sib->middle_name,
                                $sib->last_name,
                            ])));
                            $classDisplay = $sib->classroom
                                ? ($sib->stream ? $sib->classroom->name . ' – ' . $sib->stream->name : $sib->classroom->name)
                                : null;
                            return [
                                'id' => $sib->id,
                                'first_name' => $sib->first_name,
                                'middle_name' => $sib->middle_name,
                                'last_name' => $sib->last_name,
                                'full_name' => $fullName,
                                'admission_number' => $sib->admission_number ?? '',
                                'classroom_name' => $sib->classroom ? $sib->classroom->name : null,
                                'stream_name' => $sib->stream ? $sib->stream->name : null,
                                'class_display' => $classDisplay,
                            ];
                        })
                        ->values()
                        ->toArray();
                }
                
                // Ensure classroom and stream are loaded
                if (!$st->relationLoaded('classroom') && $st->classroom_id) {
                    $st->load('classroom');
                }
                if (!$st->relationLoaded('stream') && $st->stream_id) {
                    $st->load('stream');
                }
                
                $classDisplay = $st->classroom
                    ? ($st->stream ? $st->classroom->name . ' – ' . $st->stream->name : $st->classroom->name)
                    : null;
                
                return [
                    'id' => $st->id,
                    'first_name' => $st->first_name,
                    'last_name' => $st->last_name,
                    'full_name' => $full,
                    'siblings' => $siblings,
                    'admission_number' => $st->admission_number ?? '',
                    'classroom_name' => $st->classroom ? $st->classroom->name : null,
                    'stream_name' => $st->stream ? $st->stream->name : null,
                    'class_display' => $classDisplay,
                    'label' => $classDisplay
                        ? "{$full} ({$st->admission_number}) – {$classDisplay}"
                        : "{$full} ({$st->admission_number})",
                    'family_id' => $st->family_id,
                    'is_alumni' => $st->is_alumni ?? false,
                    'is_archived' => $st->archive ?? false,
                ];
            }));
        } catch (\Exception $e) {
            \Log::error('Student search failed', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([])->setStatusCode(500);
        }
    }

    /**
     * Send admission SMS/Email
     */
    protected function sendAdmissionCommunication($student, $parent)
    {
        // Use templates from CommunicationTemplateSeeder
        // Template codes: admissions_welcome_sms, admissions_welcome_email, admissions_welcome_whatsapp
        $smsTemplate = CommunicationTemplate::where('code', 'admissions_welcome_sms')->first();
        $emailTemplate = CommunicationTemplate::where('code', 'admissions_welcome_email')->first();
        $whatsappTemplate = CommunicationTemplate::where('code', 'admissions_welcome_whatsapp')->first();
        
        // Fallback: create templates if seeder hasn't run yet
        if (!$smsTemplate) {
            $smsTemplate = CommunicationTemplate::firstOrCreate(
                ['code' => 'admissions_welcome_sms'],
                [
                    'title' => 'Welcome Student (SMS/WA)',
                    'type' => 'sms',
                    'subject' => null,
                    'content' => "Dear {{parent_name}},\n\nWelcome to {{school_name}}! 🎉\nWe are delighted to inform you that {{student_name}} has been successfully admitted.\n\nAdmission Number: {{admission_number}}\nClass: {{class_name}} {{stream_name}}\n\nWe look forward to partnering with you in nurturing your child's growth and success.\n\nWarm regards,\n{{school_name}}",
                ]
            );
        }
        
        if (!$emailTemplate) {
            $emailTemplate = CommunicationTemplate::firstOrCreate(
                ['code' => 'admissions_welcome_email'],
                [
                    'title' => 'Welcome Student (Email)',
                    'type' => 'email',
                    'subject' => 'Welcome to {{school_name}} – Admission Confirmation',
                    'content' => "Dear {{parent_name}},\n\nWe are pleased to welcome you and your child, {{student_name}}, to the {{school_name}} family.\n\nStudent Name: {{student_name}}\nAdmission Number: {{admission_number}}\nClass & Stream: {{class_name}} {{stream_name}}\n\nYou may update your profile or access student information using the link below:\n{{profile_update_link}}\n\nFor any assistance, contact us at {{school_phone}} or {{school_email}}.\n\nWarm regards,\n{{school_name}} Administration",
                ]
            );
        }
        
        // Get school settings
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
        $schoolPhone = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_phone')->value('value') ?? '';
        $schoolEmail = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_email')->value('value') ?? '';
        
        // Prepare template variables
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent';
        $className = optional($student->classroom)->name ?? '';
        $streamName = optional($student->stream)->name ?? '';
        $fullName = $student->full_name ?? $student->first_name . ' ' . $student->last_name;
        
        $variables = [
            'parent_name' => $parentName,
            'student_name' => $fullName,
            'admission_number' => $student->admission_number ?? '',
            'class_name' => $className,
            'stream_name' => $streamName,
            'school_name' => $schoolName,
            'school_phone' => $schoolPhone,
            'school_email' => $schoolEmail,
            'profile_update_link' => url('/parent/profile'),
        ];
        
        // Replace placeholders
        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };
        
        // Send SMS
        if ($smsTemplate) {
            $smsMessage = $replacePlaceholders($smsTemplate->content, $variables);
            // Never send to guardian when selecting parents/students; guardians are reached via manual number entry only
            foreach ([$parent->primary_contact_phone ?? $parent->father_phone, $parent->mother_phone] as $phone) {
                if ($phone) {
                    try {
                        $this->smsService->sendSMS($phone, $smsMessage);
                    } catch (\Throwable $e) {
                        Log::error("Admission SMS sending failed to $phone: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Send Email
        if ($emailTemplate) {
            $subject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
            $body = $replacePlaceholders($emailTemplate->content, $variables);
            
            // Never send to guardian when selecting parents/students; guardians are reached via manual number entry only
            foreach ([$parent->primary_contact_email ?? $parent->father_email, $parent->mother_email] as $email) {
                if ($email) {
                    try {
                        Mail::to($email)->send(new GenericMail($subject, $body));
                    } catch (\Throwable $e) {
                        Log::error("Admission email sending failed to $email: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function show($id)
    {
        $student = Student::withArchived()
            ->with(['parent.documents','classroom','stream','category','family','documents'])
            ->findOrFail($id);

        // Families must have 2+ children; do not auto-create a family for a single student.

        if ($student->family && !$student->family->updateLink) {
            FamilyUpdateLink::create([
                'family_id' => $student->family->id,
                'token' => FamilyUpdateLink::generateToken(),
                'is_active' => true,
            ]);
            $student->family->load('updateLink');
        }

        if ($student->family_id) {
            ensure_family_payment_link($student->family_id);
        }

        return view('students.show', compact('student'));
    }

    public function getStreams(Request $request)
    {
        $request->validate(['classroom_id'=>'required|exists:classrooms,id']);
        $classroom = Classroom::find($request->classroom_id);
        // Return all streams for this classroom (primary + via pivot)
        $streams = $classroom->primaryStreams->merge($classroom->streams)->unique('id')->sortBy('name')->values()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);
        return response()->json($streams);
    }

    /**
     * Export students to CSV
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        // Apply same filters as index() but get all matching records
        $perPage = 1000000; // Large number to get all records
        $query = $request->has('showArchived')
            ? Student::withArchived()->with(['parent','classroom','stream','category'])
            : Student::with(['parent','classroom','stream','category'])->where('archive', 0);

        if ($request->filled('name')) {
            $name = $request->name;
            $searchTerm = '%' . addcslashes($name, '%_\\') . '%';
            $query->where(fn($q) => $q->where('first_name','like', $searchTerm)
                ->orWhere('middle_name','like', $searchTerm)
                ->orWhere('last_name','like', $searchTerm));
        }

        if ($request->filled('admission_number')) {
            $searchTerm = '%' . addcslashes($request->admission_number, '%_\\') . '%';
            $query->where('admission_number','like', $searchTerm);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }

        $students = $query->orderBy('first_name')->get();

        $rows = [];
        $rows[] = ['Admission','First','Middle','Last','Gender','DOB','Class','Stream','Category','Parent Phone'];
        foreach ($students as $s) {
            $rows[] = [
                $s->admission_number,
                $s->first_name,
                $s->middle_name,
                $s->last_name,
                $s->gender,
                $s->dob,
                optional($s->classroom)->name,
                optional($s->stream)->name,
                optional($s->category)->name,
                optional($s->parent)->father_phone ?? optional($s->parent)->mother_phone ?? optional($s->parent)->guardian_phone,
            ];
        }

        $fn = 'students_export_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp','r+');
        foreach ($rows as $r) fputcsv($handle, $r);
        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return response($contents, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fn\"",
        ]);
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        Student::withArchived()->whereIn('id', $request->student_ids)->update(array_filter([
            'classroom_id' => $request->classroom_id,
            'stream_id'    => $request->stream_id,
        ]));

        return back()->with('success','Selected students updated.');
    }

    /**
     * Show bulk stream assignment page
     */
    public function bulkAssignStreams(Request $request)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::with('classroom')->orderBy('name')->get();
        
        $selectedClassroom = null;
        $students = collect();
        
        if ($request->filled('classroom_id')) {
            $selectedClassroom = Classroom::findOrFail($request->classroom_id);
            $students = Student::where('classroom_id', $selectedClassroom->id)
                ->where('archive', 0)
                ->with(['stream', 'parent'])
                ->orderBy('first_name')
                ->get();
        }
        
        return view('students.bulk_assign_streams', compact('classrooms', 'streams', 'selectedClassroom', 'students'));
    }

    /**
     * Show bulk category assignment page
     */
    public function bulkAssignCategories(Request $request)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $categories = StudentCategory::orderBy('name')->get();

        $selectedClassroom = null;
        $students = collect();

        if ($request->filled('classroom_id')) {
            $selectedClassroom = Classroom::findOrFail($request->classroom_id);
            $students = Student::where('classroom_id', $selectedClassroom->id)
                ->where('archive', 0)
                ->with('category')
                ->orderBy('first_name')
                ->get();
        }

        return view('students.bulk_assign_categories', compact('classrooms', 'categories', 'selectedClassroom', 'students'));
    }

    /**
     * Process bulk category assignment
     */
    public function processBulkCategoryAssignment(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'assignments' => 'required|array',
            'assignments.*' => 'required|exists:student_categories,id',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $studentIds = array_keys($request->assignments);

        $students = Student::whereIn('id', $studentIds)
            ->where('classroom_id', $classroom->id)
            ->get();

        foreach ($students as $student) {
            $student->update([
                'category_id' => $request->assignments[$student->id],
            ]);
        }

        return redirect()->route('students.bulk.assign-categories', ['classroom_id' => $classroom->id])
            ->with('success', 'Categories updated for selected students.');
    }

    /**
     * Process bulk stream assignment
     */
    public function processBulkStreamAssignment(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'stream_id' => 'required|exists:streams,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        // Verify stream belongs to the classroom
        $stream = Stream::findOrFail($request->stream_id);
        $classroom = Classroom::findOrFail($request->classroom_id);
        
        // Check if stream is assigned to this classroom (primary or via pivot)
        $isValidStream = $stream->classroom_id == $classroom->id || 
                        $stream->classrooms->contains('id', $classroom->id);
        
        if (!$isValidStream) {
            return back()->withInput()->with('error', 'The selected stream is not assigned to this classroom.');
        }

        // Update students
        $updated = Student::whereIn('id', $request->student_ids)
            ->where('classroom_id', $classroom->id) // Ensure students are in the correct classroom
            ->update(['stream_id' => $request->stream_id]);

        if ($updated > 0) {
            return redirect()->route('students.bulk.assign-streams', ['classroom_id' => $classroom->id])
                ->with('success', "Successfully assigned {$updated} student(s) to {$stream->name} stream.");
        }

        return back()->withInput()->with('error', 'No students were updated. Please ensure selected students belong to the selected classroom.');
    }

    public function bulkArchive(Request $request)
    {
        $request->validate(['student_ids'=>'required|array']);
        
        $students = Student::withArchived()->whereIn('id', $request->student_ids)->get();
        $archivedCount = 0;
        
        foreach ($students as $student) {
            try {
                $result = $this->archiveService->archive(
                    $student,
                    'Bulk archive',
                    auth()->id(),
                    null
                );
                if (!$result['skipped']) {
                    $archivedCount++;
                }
            } catch (\Throwable $e) {
                Log::error('Bulk archive failed for student: '.$e->getMessage(), ['student_id' => $student->id]);
            }
        }
        
        return back()->with('success', "Successfully archived {$archivedCount} student(s).");
    }

    public function bulkRestore(Request $request)
    {
        $request->validate(['student_ids'=>'required|array']);
        $ids = $request->input('student_ids');
        Student::withArchived()->whereIn('id', $ids)->update(['archive' => 0]);
        // Restore families for any restored students that had archived_family_id set
        $familyArchiveService = app(\App\Services\FamilyArchiveService::class);
        foreach (Student::whereIn('id', $ids)->get() as $student) {
            $familyArchiveService->onStudentRestored($student);
        }
        return back()->with('success','Selected students restored.');
    }

    /**
     * Full international country codes list (dialing) without flags, Kenya default appears first.
     */
    private function getCountryCodes(): array
    {
        $codes = [
            ['code' => '+254', 'label' => 'Kenya (+254)'],
            ['code' => '+1', 'label' => 'United States / Canada (+1)'],
            ['code' => '+44', 'label' => 'United Kingdom (+44)'],
            ['code' => '+27', 'label' => 'South Africa (+27)'],
            ['code' => '+234', 'label' => 'Nigeria (+234)'],
            ['code' => '+256', 'label' => 'Uganda (+256)'],
            ['code' => '+255', 'label' => 'Tanzania (+255)'],
            ['code' => '+91', 'label' => 'India (+91)'],
            ['code' => '+971', 'label' => 'United Arab Emirates (+971)'],
            ['code' => '+61', 'label' => 'Australia (+61)'],
            ['code' => '+64', 'label' => 'New Zealand (+64)'],
            ['code' => '+81', 'label' => 'Japan (+81)'],
            ['code' => '+86', 'label' => 'China (+86)'],
            ['code' => '+49', 'label' => 'Germany (+49)'],
            ['code' => '+33', 'label' => 'France (+33)'],
            ['code' => '+39', 'label' => 'Italy (+39)'],
            ['code' => '+34', 'label' => 'Spain (+34)'],
            ['code' => '+46', 'label' => 'Sweden (+46)'],
            ['code' => '+47', 'label' => 'Norway (+47)'],
            ['code' => '+45', 'label' => 'Denmark (+45)'],
            ['code' => '+31', 'label' => 'Netherlands (+31)'],
            ['code' => '+32', 'label' => 'Belgium (+32)'],
            ['code' => '+41', 'label' => 'Switzerland (+41)'],
            ['code' => '+52', 'label' => 'Mexico (+52)'],
            ['code' => '+55', 'label' => 'Brazil (+55)'],
            ['code' => '+54', 'label' => 'Argentina (+54)'],
            ['code' => '+51', 'label' => 'Peru (+51)'],
            ['code' => '+20', 'label' => 'Egypt (+20)'],
            ['code' => '+212', 'label' => 'Morocco (+212)'],
            ['code' => '+974', 'label' => 'Qatar (+974)'],
            ['code' => '+966', 'label' => 'Saudi Arabia (+966)'],
            ['code' => '+962', 'label' => 'Jordan (+962)'],
            ['code' => '+961', 'label' => 'Lebanon (+961)'],
            ['code' => '+90', 'label' => 'Turkey (+90)'],
            ['code' => '+94', 'label' => 'Sri Lanka (+94)'],
            ['code' => '+880', 'label' => 'Bangladesh (+880)'],
            ['code' => '+92', 'label' => 'Pakistan (+92)'],
            ['code' => '+60', 'label' => 'Malaysia (+60)'],
            ['code' => '+65', 'label' => 'Singapore (+65)'],
            ['code' => '+63', 'label' => 'Philippines (+63)'],
            ['code' => '+62', 'label' => 'Indonesia (+62)'],
            ['code' => '+82', 'label' => 'South Korea (+82)'],
            ['code' => '+853', 'label' => 'Macau (+853)'],
            ['code' => '+852', 'label' => 'Hong Kong (+852)'],
            ['code' => '+7', 'label' => 'Russia (+7)'],
            ['code' => '+380', 'label' => 'Ukraine (+380)'],
            ['code' => '+48', 'label' => 'Poland (+48)'],
            ['code' => '+420', 'label' => 'Czech Republic (+420)'],
            ['code' => '+421', 'label' => 'Slovakia (+421)'],
            ['code' => '+36', 'label' => 'Hungary (+36)'],
            ['code' => '+40', 'label' => 'Romania (+40)'],
            ['code' => '+30', 'label' => 'Greece (+30)'],
            ['code' => '+386', 'label' => 'Slovenia (+386)'],
            ['code' => '+385', 'label' => 'Croatia (+385)'],
            ['code' => '+43', 'label' => 'Austria (+43)'],
            ['code' => '+372', 'label' => 'Estonia (+372)'],
            ['code' => '+371', 'label' => 'Latvia (+371)'],
            ['code' => '+370', 'label' => 'Lithuania (+370)'],
            ['code' => '+56', 'label' => 'Chile (+56)'],
            ['code' => '+57', 'label' => 'Colombia (+57)'],
            ['code' => '+58', 'label' => 'Venezuela (+58)'],
            ['code' => '+507', 'label' => 'Panama (+507)'],
            ['code' => '+506', 'label' => 'Costa Rica (+506)'],
            ['code' => '+66', 'label' => 'Thailand (+66)'],
            ['code' => '+84', 'label' => 'Vietnam (+84)'],
        ];

        // Separate Kenya from the rest, then sort the rest alphabetically
        $kenya = collect($codes)->firstWhere('code', '+254');
        $others = collect($codes)->reject(fn($item) => $item['code'] === '+254')
            ->sortBy('label')
            ->values()
            ->all();

        return $kenya ? array_merge([$kenya], $others) : $others;
    }

    /**
     * Normalize a phone number by combining country code and local digits.
     */
    protected function formatPhoneWithCode(?string $number, ?string $code = '+254'): ?string
    {
        return app(\App\Services\PhoneNumberService::class)
            ->formatWithCountryCode($number, $code);
    }

    /**
     * Normalize country code (e.g., +ke, ke -> +254)
     */
    protected function normalizeCountryCode(?string $code): string
    {
        return app(\App\Services\PhoneNumberService::class)
            ->normalizeCountryCode($code);
    }

    /**
     * Extract local phone number from stored full international format.
     * Removes the country code prefix to show only the local number.
     */
    protected function extractLocalPhone(?string $fullPhone, ?string $countryCode = '+254'): ?string
    {
        return app(\App\Services\PhoneNumberService::class)
            ->extractLocalNumber($fullPhone, $countryCode);
    }

    protected function logPhoneNormalization(
        string $modelType,
        ?int $modelId,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        ?string $countryCode,
        string $source,
        ?int $userId
    ): void {
        app(\App\Services\PhoneNumberNormalizationLogger::class)
            ->logIfChanged($modelType, $modelId, $field, $oldValue, $newValue, $countryCode, $source, $userId);
    }

    /**
     * Show form for updating existing students via import
     */
    public function updateImportForm()
    {
        return view('students.update_import');
    }

    /**
     * Download template for updating existing students
     */
    public function updateImportTemplate()
    {
        return Excel::download(new \App\Exports\StudentUpdateTemplateExport(), 'student_update_template.xlsx');
    }

    /**
     * Preview the import file before processing
     */
    public function updateImportPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $rows = Excel::toArray([], $request->file('file'))[0];
            if (empty($rows) || count($rows) < 2) {
                return back()->with('error', 'The file appears to be empty or has no data rows.');
            }

            $headers = array_map('trim', array_map('strtolower', $rows[0]));
            unset($rows[0]);

            // Check if admission_number column exists
            $admissionColIndex = array_search('admission_number', $headers);
            if ($admissionColIndex === false) {
                return back()->with('error', 'The file must contain an "admission_number" column to identify students.');
            }

            $preview = [];
            $errors = [];
            $successCount = 0;

            foreach ($rows as $rowIndex => $row) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = $row[$index] ?? null;
                }

                $admissionNumber = trim($rowData['admission_number'] ?? '');
                if (empty($admissionNumber)) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Missing admission number";
                    continue;
                }

                $student = Student::where('admission_number', $admissionNumber)->first();
                if (!$student) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Student with admission number '{$admissionNumber}' not found";
                    continue;
                }

                // Map the data
                $updateData = [
                    'admission_number' => $admissionNumber,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'current_classroom' => $student->classroom ? $student->classroom->name : '',
                    'row_data' => $rowData,
                    'student' => $student,
                ];

                $preview[] = $updateData;
                $successCount++;
            }

            return view('students.update_import_preview', compact('preview', 'errors', 'successCount'));
        } catch (\Exception $e) {
            \Log::error('Student update import preview error: ' . $e->getMessage());
            return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Process the import and update students
     */
    public function updateImportProcess(Request $request)
    {
        $request->validate([
            'students' => 'required|array',
        ]);

        $updated = 0;
        $errors = [];
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($request->input('students', []) as $encoded) {
                $rowData = json_decode(base64_decode($encoded), true);
                if (!$rowData) continue;

                $admissionNumber = trim($rowData['admission_number'] ?? '');
                if (empty($admissionNumber)) {
                    $skipped++;
                    continue;
                }

                $student = Student::where('admission_number', $admissionNumber)->first();
                if (!$student) {
                    $errors[] = "Student with admission number '{$admissionNumber}' not found";
                    $skipped++;
                    continue;
                }

                // Update student fields
                $studentUpdateData = [];
                $parentUpdateData = [];
                $parentPhoneMeta = [];

                // Student basic info
                if (!empty($rowData['first_name'])) $studentUpdateData['first_name'] = $rowData['first_name'];
                if (!empty($rowData['middle_name']) || isset($rowData['middle_name'])) $studentUpdateData['middle_name'] = $rowData['middle_name'] ?: null;
                if (!empty($rowData['last_name'])) $studentUpdateData['last_name'] = $rowData['last_name'];
                if (!empty($rowData['gender'])) $studentUpdateData['gender'] = strtolower($rowData['gender']);
                if (!empty($rowData['dob'])) {
                    try {
                        $studentUpdateData['dob'] = \Carbon\Carbon::parse($rowData['dob'])->toDateString();
                    } catch (\Exception $e) {
                        // Skip invalid date
                    }
                }

                // Academic info
                if (!empty($rowData['classroom'])) {
                    $classroom = Classroom::where('name', $rowData['classroom'])->first();
                    if ($classroom) $studentUpdateData['classroom_id'] = $classroom->id;
                }
                if (!empty($rowData['stream'])) {
                    $stream = Stream::where('name', $rowData['stream'])->first();
                    if ($stream) $studentUpdateData['stream_id'] = $stream->id;
                }
                if (!empty($rowData['category'])) {
                    $category = StudentCategory::where('name', $rowData['category'])->first();
                    if ($category) $studentUpdateData['category_id'] = $category->id;
                }

                // Identifiers
                if (!empty($rowData['nemis_number']) || isset($rowData['nemis_number'])) {
                    $studentUpdateData['nemis_number'] = $rowData['nemis_number'] ?: null;
                }
                if (!empty($rowData['knec_assessment_number']) || isset($rowData['knec_assessment_number'])) {
                    $studentUpdateData['knec_assessment_number'] = $rowData['knec_assessment_number'] ?: null;
                }

                // Extended demographics
                if (!empty($rowData['religion']) || isset($rowData['religion'])) {
                    $studentUpdateData['religion'] = $rowData['religion'] ?: null;
                }
                if (!empty($rowData['residential_area']) || isset($rowData['residential_area'])) {
                    $studentUpdateData['residential_area'] = $rowData['residential_area'] ?: null;
                }

                // Medical
                if (isset($rowData['has_allergies'])) {
                    $studentUpdateData['has_allergies'] = in_array(strtolower($rowData['has_allergies']), ['yes', '1', 'true', 'y']) ? 1 : 0;
                }
                if (!empty($rowData['allergies_notes']) || isset($rowData['allergies_notes'])) {
                    $studentUpdateData['allergies_notes'] = $rowData['allergies_notes'] ?: null;
                }
                if (isset($rowData['is_fully_immunized'])) {
                    $studentUpdateData['is_fully_immunized'] = in_array(strtolower($rowData['is_fully_immunized']), ['yes', '1', 'true', 'y']) ? 1 : 0;
                }
                if (!empty($rowData['preferred_hospital']) || isset($rowData['preferred_hospital'])) {
                    $studentUpdateData['preferred_hospital'] = $rowData['preferred_hospital'] ?: null;
                }
                if (!empty($rowData['emergency_contact_name']) || isset($rowData['emergency_contact_name'])) {
                    $studentUpdateData['emergency_contact_name'] = $rowData['emergency_contact_name'] ?: null;
                }
                if (!empty($rowData['emergency_contact_phone'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['emergency_phone_country_code'] ?? '+254');
                    $emergencyPhone = $this->formatPhoneWithCode($rowData['emergency_contact_phone'], $countryCode);
                    $studentUpdateData['emergency_contact_phone'] = $emergencyPhone;
                    $this->logPhoneNormalization(
                        Student::class,
                        $student->id,
                        'emergency_contact_phone',
                        $student->emergency_contact_phone,
                        $emergencyPhone,
                        $countryCode,
                        'student_import',
                        auth()->id()
                    );
                }

                // Update student
                if (!empty($studentUpdateData)) {
                    $student->update($studentUpdateData);
                }

                // Get or create parent
                $parent = $student->parent;
                if (!$parent) {
                    $parent = ParentInfo::create([]);
                    $student->parent_id = $parent->id;
                    $student->save();
                }

                // Parent/Guardian info
                if (!empty($rowData['father_name']) || isset($rowData['father_name'])) {
                    $parentUpdateData['father_name'] = $rowData['father_name'] ?: null;
                }
                if (!empty($rowData['father_phone'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['father_phone_country_code'] ?? '+254');
                    $fatherPhone = $this->formatPhoneWithCode($rowData['father_phone'], $countryCode);
                    $parentUpdateData['father_phone'] = $fatherPhone;
                    $parentUpdateData['father_phone_country_code'] = $countryCode;
                    $parentPhoneMeta['father_phone'] = ['new' => $fatherPhone, 'code' => $countryCode];
                }
                if (!empty($rowData['father_whatsapp'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['father_whatsapp_country_code'] ?? $rowData['father_phone_country_code'] ?? '+254');
                    $fatherWhatsapp = $this->formatPhoneWithCode($rowData['father_whatsapp'], $countryCode);
                    $parentUpdateData['father_whatsapp'] = $fatherWhatsapp;
                    $parentUpdateData['father_whatsapp_country_code'] = $countryCode;
                    $parentPhoneMeta['father_whatsapp'] = ['new' => $fatherWhatsapp, 'code' => $countryCode];
                }
                if (!empty($rowData['father_email']) || isset($rowData['father_email'])) {
                    $parentUpdateData['father_email'] = $rowData['father_email'] ?: null;
                }
                if (!empty($rowData['father_id_number']) || isset($rowData['father_id_number'])) {
                    $parentUpdateData['father_id_number'] = $rowData['father_id_number'] ?: null;
                }

                // Mother info
                if (!empty($rowData['mother_name']) || isset($rowData['mother_name'])) {
                    $parentUpdateData['mother_name'] = $rowData['mother_name'] ?: null;
                }
                if (!empty($rowData['mother_phone'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['mother_phone_country_code'] ?? '+254');
                    $motherPhone = $this->formatPhoneWithCode($rowData['mother_phone'], $countryCode);
                    $parentUpdateData['mother_phone'] = $motherPhone;
                    $parentUpdateData['mother_phone_country_code'] = $countryCode;
                    $parentPhoneMeta['mother_phone'] = ['new' => $motherPhone, 'code' => $countryCode];
                }
                if (!empty($rowData['mother_whatsapp'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['mother_whatsapp_country_code'] ?? $rowData['mother_phone_country_code'] ?? '+254');
                    $motherWhatsapp = $this->formatPhoneWithCode($rowData['mother_whatsapp'], $countryCode);
                    $parentUpdateData['mother_whatsapp'] = $motherWhatsapp;
                    $parentUpdateData['mother_whatsapp_country_code'] = $countryCode;
                    $parentPhoneMeta['mother_whatsapp'] = ['new' => $motherWhatsapp, 'code' => $countryCode];
                }
                if (!empty($rowData['mother_email']) || isset($rowData['mother_email'])) {
                    $parentUpdateData['mother_email'] = $rowData['mother_email'] ?: null;
                }
                if (!empty($rowData['mother_id_number']) || isset($rowData['mother_id_number'])) {
                    $parentUpdateData['mother_id_number'] = $rowData['mother_id_number'] ?: null;
                }

                // Guardian info
                if (!empty($rowData['guardian_name']) || isset($rowData['guardian_name'])) {
                    $parentUpdateData['guardian_name'] = $rowData['guardian_name'] ?: null;
                }
                if (!empty($rowData['guardian_phone'])) {
                    $countryCode = $this->normalizeCountryCode($rowData['guardian_phone_country_code'] ?? '+254');
                    $guardianPhone = $this->formatPhoneWithCode($rowData['guardian_phone'], $countryCode);
                    $parentUpdateData['guardian_phone'] = $guardianPhone;
                    $parentUpdateData['guardian_phone_country_code'] = $countryCode;
                    $parentPhoneMeta['guardian_phone'] = ['new' => $guardianPhone, 'code' => $countryCode];
                }
                if (!empty($rowData['guardian_relationship']) || isset($rowData['guardian_relationship'])) {
                    $parentUpdateData['guardian_relationship'] = $rowData['guardian_relationship'] ?: null;
                }
                if (!empty($rowData['guardian_email']) || isset($rowData['guardian_email'])) {
                    $parentUpdateData['guardian_email'] = $rowData['guardian_email'] ?: null;
                }

                // Marital status
                if (!empty($rowData['marital_status']) || isset($rowData['marital_status'])) {
                    $parentUpdateData['marital_status'] = $rowData['marital_status'] ?: null;
                }

                // Update parent
                if (!empty($parentUpdateData)) {
                    $parentBefore = $parent->only(array_keys($parentPhoneMeta));
                    $parent->update($parentUpdateData);
                    foreach ($parentPhoneMeta as $field => $meta) {
                        $this->logPhoneNormalization(
                            ParentInfo::class,
                            $parent->id,
                            $field,
                            $parentBefore[$field] ?? null,
                            $meta['new'] ?? null,
                            $meta['code'] ?? null,
                            'student_import',
                            auth()->id()
                        );
                    }
                }

                $updated++;
            }

            DB::commit();

            $message = "Successfully updated {$updated} student(s).";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped.";
            }
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(s) occurred.";
            }

            return redirect()->route('students.update-import')
                ->with('success', $message)
                ->with('errors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Student update import error: ' . $e->getMessage());
            return back()->with('error', 'Error processing import: ' . $e->getMessage());
        }
    }
}

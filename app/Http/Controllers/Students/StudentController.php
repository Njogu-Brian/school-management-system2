<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SMSService;
use App\Services\ArchiveStudentService;
use App\Services\RestoreStudentService;
use Illuminate\Support\Facades\Mail;
use App\Models\CommunicationTemplate;
use App\Mail\GenericMail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentTemplateExport;
use App\Models\Setting;

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
     * Archived students listing
     */
    public function archived(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $students = Student::withArchived()
            ->where('archive', 1)
            ->with(['parent','classroom','stream','category'])
            ->orderByDesc('archived_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('students.archived', compact('students'));
    }

    /**
     * Show form to create a new student
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $students   = Student::all(); // optional, not needed with modal search
        $categories = StudentCategory::all();
        $classrooms = Classroom::all();
        $streams    = Stream::all();
        $routes     = TransportRoute::orderBy('name')->get();

        return view('students.create', [
            'students' => $students,
            'categories' => $categories,
            'classrooms' => $classrooms,
            'streams' => $streams,
            'routes' => $routes,
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
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|string',
                'dob' => 'nullable|date',
                'classroom_id' => 'nullable|exists:classrooms,id',
                'stream_id' => 'nullable|exists:streams,id',
                'category_id' => 'nullable|exists:student_categories,id',
                'nemis_number' => 'nullable|string',
                'knec_assessment_number' => 'nullable|string',
                // Extended demographics
                'national_id_number' => 'nullable|string|max:255',
                'passport_number' => 'nullable|string|max:255',
                'religion' => 'nullable|string|max:255',
                'ethnicity' => 'nullable|string|max:255',
                'home_address' => 'nullable|string|max:255',
                'home_city' => 'nullable|string|max:255',
                'home_county' => 'nullable|string|max:255',
                'home_postal_code' => 'nullable|string|max:255',
                'language_preference' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:10',
                'allergies' => 'nullable|string',
                'chronic_conditions' => 'nullable|string',
                'medical_insurance_provider' => 'nullable|string|max:255',
                'medical_insurance_number' => 'nullable|string|max:255',
                'emergency_medical_contact_name' => 'nullable|string|max:255',
                'emergency_medical_contact_phone' => 'nullable|string|max:255',
                'previous_schools' => 'nullable|string',
                'transfer_reason' => 'nullable|string',
                'has_special_needs' => 'nullable|boolean',
                'special_needs_description' => 'nullable|string',
                'learning_disabilities' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,graduated,transferred,expelled,suspended',
                'admission_date' => 'nullable|date',
            ]);

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
            // Create ParentInfo
            $parent = ParentInfo::create($request->only([
                'father_name', 'father_phone', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email'
            ]));

            $admission_number = $this->generateNextAdmissionNumber();

            $student = Student::create(array_merge(
                $request->only([
                    'first_name', 'middle_name', 'last_name', 'gender', 'dob',
                    'classroom_id', 'stream_id', 'category_id',
                    'nemis_number', 'knec_assessment_number',
                    'national_id_number', 'passport_number', 'religion', 'ethnicity',
                    'home_address', 'home_city', 'home_county', 'home_postal_code',
                    'language_preference', 'blood_group', 'allergies', 'chronic_conditions',
                    'medical_insurance_provider', 'medical_insurance_number',
                    'emergency_medical_contact_name', 'emergency_medical_contact_phone',
                    'previous_schools', 'transfer_reason', 'has_special_needs',
                    'special_needs_description', 'learning_disabilities',
                    'status', 'admission_date'
                ]),
                ['admission_number' => $admission_number, 'parent_id' => $parent->id, 'family_id' => $familyId]
            ));

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
            
            // Charge fees for newly admitted student
            try {
                \App\Services\FeePostingService::chargeFeesForNewStudent($student);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to charge fees for new student: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                ]);
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
        $categories   = StudentCategory::all();
        $classrooms   = Classroom::all();
        $streams      = Stream::all();
        $routes       = TransportRoute::orderBy('name')->get();
        $familyMembers = $student->family_id
            ? Student::where('family_id',$student->family_id)->where('id','!=',$student->id)->get()
            : collect();

        return view('students.edit', compact('student','categories','classrooms','streams','routes','familyMembers'));
    }
    /**
     * Update student
     */
   public function update(Request $request, $id)
    {
        $student = Student::withArchived()->findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            'classroom_id' => 'nullable|exists:classrooms,id',
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
            'category_id' => 'nullable|exists:student_categories,id',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
            // Extended demographics
            'national_id_number' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'ethnicity' => 'nullable|string|max:255',
            'home_address' => 'nullable|string|max:255',
            'home_city' => 'nullable|string|max:255',
            'home_county' => 'nullable|string|max:255',
            'home_postal_code' => 'nullable|string|max:255',
            'language_preference' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'medical_insurance_provider' => 'nullable|string|max:255',
            'medical_insurance_number' => 'nullable|string|max:255',
            'emergency_medical_contact_name' => 'nullable|string|max:255',
            'emergency_medical_contact_phone' => 'nullable|string|max:255',
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

        $updateData = $request->only([
            'first_name', 'middle_name', 'last_name', 'gender', 'dob',
            'classroom_id', 'stream_id', 'category_id',
            'nemis_number', 'knec_assessment_number',
            'national_id_number', 'passport_number', 'religion', 'ethnicity',
            'home_address', 'home_city', 'home_county', 'home_postal_code',
            'language_preference', 'blood_group', 'allergies', 'chronic_conditions',
            'medical_insurance_provider', 'medical_insurance_number',
            'emergency_medical_contact_name', 'emergency_medical_contact_phone',
            'previous_schools', 'transfer_reason', 'has_special_needs',
            'special_needs_description', 'learning_disabilities',
            'status', 'admission_date', 'graduation_date', 'transfer_date',
            'transfer_to_school', 'status_change_reason', 'is_readmission'
        ]);
        
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
        
        $student->update($updateData);
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
                'phone'         => $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone,
                'email'         => $request->guardian_email ?? $request->father_email ?? $request->mother_email,
            ]);
            $familyId = $fam->id;
        }

        if ($familyId) {
            $student->update(['family_id' => $familyId]);
        }

        if ($student->parent) {
            $student->parent->update($request->only([
                'father_name', 'father_phone', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email'
            ]));
        }

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }
    /**
     * Archive student
     */
    public function archive($id, Request $request)
    {
        $student = Student::withArchived()->findOrFail($id);
        try {
            $result = $this->archiveService->archive($student, $request->input('reason'), auth()->id());
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
            'father_name' => 'Mr. Smith',
            'father_phone' => '+2547xxxxxxx',
            'father_email' => 'father@example.com',
            'father_id_number' => '12345678',
            'mother_name' => 'Mrs. Smith',
            'mother_phone' => '+2547xxxxxxx',
            'mother_email' => 'mother@example.com',
            'mother_id_number' => '87654321',
            'guardian_name' => '',
            'guardian_phone' => '',
            'guardian_email' => ''
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
            
            // Handle status field
            $rowData['status'] = $rowData['status'] ?? 'active';
            if (!in_array($rowData['status'], ['active', 'inactive', 'graduated', 'transferred', 'expelled', 'suspended'])) {
                $rowData['status'] = 'active';
            }

            // Validate required fields
            $rowData['valid'] =
                !empty($rowData['first_name']) &&
                !empty($rowData['last_name']) &&
                !empty($rowData['gender']) &&
                !empty($classroomId);

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
                'father_email' => $row['father_email'],
                'father_id_number' => $row['father_id_number'],
                'mother_name' => $row['mother_name'],
                'mother_phone' => $row['mother_phone'],
                'mother_email' => $row['mother_email'],
                'mother_id_number' => $row['mother_id_number'],
                'guardian_name' => $row['guardian_name'],
                'guardian_phone' => $row['guardian_phone'],
                'guardian_email' => $row['guardian_email'],
            ]);

            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'],
                'gender' => $row['gender'],
                'dob' => $row['dob'],
                'classroom_id' => $row['classroom_id'],
                'stream_id' => $row['stream_id'] ?? null,
                'category_id' => $row['category_id'] ?? null,
                'parent_id' => $parent->id,
                // Identifiers
                'nemis_number' => $row['nemis_number'] ?? null,
                'knec_assessment_number' => $row['knec_assessment_number'] ?? null,
                'national_id_number' => $row['national_id_number'] ?? null,
                'passport_number' => $row['passport_number'] ?? null,
                // Extended Demographics
                'religion' => $row['religion'] ?? null,
                'ethnicity' => $row['ethnicity'] ?? null,
                'language_preference' => $row['language_preference'] ?? null,
                'blood_group' => $row['blood_group'] ?? null,
                'home_address' => $row['home_address'] ?? null,
                'home_city' => $row['home_city'] ?? null,
                'home_county' => $row['home_county'] ?? null,
                'home_postal_code' => $row['home_postal_code'] ?? null,
                // Medical
                'allergies' => $row['allergies'] ?? null,
                'chronic_conditions' => $row['chronic_conditions'] ?? null,
                'medical_insurance_provider' => $row['medical_insurance_provider'] ?? null,
                'medical_insurance_number' => $row['medical_insurance_number'] ?? null,
                'emergency_medical_contact_name' => $row['emergency_medical_contact_name'] ?? null,
                'emergency_medical_contact_phone' => $row['emergency_medical_contact_phone'] ?? null,
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
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $searchTerm = '%' . addcslashes($q, '%_\\') . '%';
        $students = Student::query()
            ->with('classroom')
            ->where(function ($s) use ($searchTerm) {
                $s->where('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)
                  ->orWhere('last_name', 'like', $searchTerm)
                  ->orWhere('admission_number', 'like', $searchTerm);
            })
            ->select('id', 'first_name', 'middle_name', 'last_name', 'admission_number', 'classroom_id')
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return response()->json($students->map(function ($st) {
            $full = trim(implode(' ', array_filter([$st->first_name, $st->middle_name, $st->last_name])));
            return [
                'id' => $st->id,
                'full_name' => $full,
                'admission_number' => $st->admission_number,
                'classroom_name' => $st->classroom->name ?? null,
            ];
        }));
    }

    /**
     * Send admission SMS/Email
     */
    protected function sendAdmissionCommunication($student, $parent)
    {
        $className = optional($student->classrooms)->name ?? '';
        $fullName = $student->getFullNameAttribute();

        // SMS
        $smsTemplate = CommunicationTemplate::where('type', 'sms')
            ->where('code', 'student_admission')
            ->first();

        $smsMessage = $smsTemplate
            ? str_replace(['{name}', '{class}'], [$fullName, $className], $smsTemplate->content)
            : "Dear Parent, your child {$fullName} has been admitted to class {$className}.";

        foreach ([$parent->father_phone, $parent->mother_phone, $parent->guardian_phone] as $phone) {
            if ($phone) {
                $this->smsService->sendSMS($phone, $smsMessage);
            }
        }

        // Email
        $emailTemplate = CommunicationTemplate::where('code', 'student_admission')->first();
        if ($emailTemplate) {
            $subject = $emailTemplate->title;
            $body = str_replace(
                ['{name}', '{class}'],
                [$fullName, $className],
                $emailTemplate->message
            );

            foreach ([$parent->father_email, $parent->mother_email, $parent->guardian_email] as $email) {
                if ($email) {
                    try {
                        Mail::to($email)->send(new GenericMail($subject, $body));
                    } catch (\Throwable $e) {
                        Log::error("Email sending failed to $email: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function show($id)
    {
        $student = Student::withArchived()
            ->with(['parent','classroom','stream','category','family'])
            ->findOrFail($id);

        return view('students.show', compact('student'));
    }

    public function getStreams(Request $request)
    {
        $request->validate(['classroom_id'=>'required|exists:classrooms,id']);
        $streams = Stream::where('classroom_id', $request->classroom_id)->select('id','name')->get();
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
        Student::withArchived()->whereIn('id', $request->student_ids)->update(['archive'=>1]);
        return back()->with('success','Selected students archived.');
    }

    public function bulkRestore(Request $request)
    {
        $request->validate(['student_ids'=>'required|array']);
        Student::withArchived()->whereIn('id', $request->student_ids)->update(['archive'=>0]);
        return back()->with('success','Selected students restored.');
    }
}

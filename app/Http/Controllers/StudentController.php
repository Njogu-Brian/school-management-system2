<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SMSService;
use Illuminate\Support\Facades\Mail;
use App\Models\CommunicationTemplate;
use App\Mail\GenericMail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentTemplateExport;
use App\Models\SystemSetting;

class StudentController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * List students
     */
   public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = $request->has('showArchived')
            ? Student::withArchived()->with(['parent','classroom','stream','category'])
            : Student::with(['parent','classroom','stream','category'])->where('archive', 0);

        if ($request->filled('name')) {
            $name = $request->name;
            $query->where(fn($q)=>$q->where('first_name','like',"%$name%")
                ->orWhere('middle_name','like',"%$name%")
                ->orWhere('last_name','like',"%$name%"));
        }

        if ($request->filled('admission_number')) {
            $query->where('admission_number','like','%'.$request->admission_number.'%');
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

        // birthdays this week (for badge)
        $thisWeekBirthdays = Student::whereNotNull('dob')->get()->filter(function($s){
            $dob = \Carbon\Carbon::parse($s->dob);
            $thisYear = $dob->copy()->year(now()->year);
            return $thisYear->isCurrentWeek();
        })->pluck('id')->toArray();

        return view('students.index', compact('students','classrooms','streams','thisWeekBirthdays'));
    }

    /**
     * Show form to create student
     */
    public function create()
    {
        $students   = Student::all(); // optional, not needed with modal search
        $categories = StudentCategory::all();
        $classrooms = Classroom::all();
        $streams    = Stream::all();
        $routes     = \App\Models\TransportRoute::orderBy('name')->get(); // if you have this model

        return view('students.create', [
            'students' => $students,
            'categories' => $categories,
            'classrooms' => $classrooms,
            'streams' => $streams,
            'routes' => $routes,
        ]);
    }
    /**
     * Store student
     */
    public function store(Request $request)
    {
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
                // Create new family for THIS student using provided parent info
                $fam = \App\Models\Family::create([
                    'guardian_name' => $request->guardian_name ?? $request->father_name ?? $request->mother_name ?? 'New Family',
                    'phone'         => $request->guardian_phone ?? $request->father_phone ?? $request->mother_phone,
                    'email'         => $request->guardian_email ?? $request->father_email ?? $request->mother_email,
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
                    'nemis_number', 'knec_assessment_number'
                ]),
                ['admission_number' => $admission_number, 'parent_id' => $parent->id]
            ));

            $this->sendAdmissionCommunication($student, $parent);

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
        $routes       = \App\Models\TransportRoute::orderBy('name')->get(); // if exists
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
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'nullable|exists:student_categories,id',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
        ]);

        $student->update($request->only([
            'first_name', 'middle_name', 'last_name', 'gender', 'dob',
            'classroom_id', 'stream_id', 'category_id',
            'nemis_number', 'knec_assessment_number'
        ]));
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
   public function archive($id)
    {
        $student = Student::withArchived()->findOrFail($id);
        $student->archive = 1;
        $student->save();

        return redirect()->route('students.index')->with('success', 'Student archived successfully.');
    }

    /**
     * Restore student
     */
    public function restore($id)
    {
        $student = Student::withArchived()->findOrFail($id);
        $student->archive = 0;
        $student->save();

        return redirect()->route('students.index')->with('success', 'Student restored successfully.');
    }
    /**
     * Generate admission number
     */
    private function generateNextAdmissionNumber()
    {
        $prefix = SystemSetting::getValue('student_id_prefix', 'ADM');
        $counter = SystemSetting::incrementValue('student_id_counter', SystemSetting::getValue('student_id_start', 1000));
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
                'middle_name' => $row['middle_name'],
                'last_name' => $row['last_name'],
                'gender' => $row['gender'],
                'dob' => $row['dob'],
                'classroom_id' => $row['classroom_id'],
                'stream_id' => $row['stream_id'],
                'category_id' => $row['category_id'],
                'parent_id' => $parent->id,
            ]);

            if ($isNew) {
                $this->sendAdmissionCommunication($student, $parent);
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

        $students = Student::query()
            ->where(function ($s) use ($q) {
                $s->where('first_name', 'like', "%{$q}%")
                  ->orWhere('middle_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('admission_number', 'like', "%{$q}%");
            })
            ->select('id', 'first_name', 'middle_name', 'last_name', 'admission_number')
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return response()->json($students->map(function ($st) {
            $full = trim(implode(' ', array_filter([$st->first_name, $st->middle_name, $st->last_name])));
            return [
                'id' => $st->id,
                'full_name' => $full,
                'admission_number' => $st->admission_number,
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
            ->with(['parent','classroom','stream','category'])
            ->findOrFail($id);

        return view('students.show', compact('student'));
    }

    public function getStreams(Request $request)
    {
        $request->validate(['classroom_id'=>'required|exists:classrooms,id']);
        $streams = Stream::where('classroom_id', $request->classroom_id)->select('id','name')->get();
        return response()->json($streams);
    }

    public function export(Request $request)
    {
        // Reuse same filters as index()
        $request->merge(['per_page' => 1000000]); // get "all" that match
        $students = $this->index(new Request($request->all()))->getData()['students']; // hack-free: duplicate filter logic if you prefer

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

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Classroom;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SMSService;
use Illuminate\Support\Facades\Mail;
use App\Models\SMSTemplate;
use App\Models\EmailTemplate;
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

    public function index(Request $request)
    {
        abort_unless(can_access("students", "manage_students", "view"), 403);

        $query = Student::with(['parent', 'classroom', 'stream', 'category']);

        if (!$request->has('showArchived')) {
            $query->where('archive', false);
        }

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name . '%')
                ->orWhere('last_name', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('admission_number')) {
            $query->where('admission_number', 'like', '%' . $request->admission_number . '%');
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $students = $query->get();
        $classes = Classroom::all();

        return view('students.index', compact('students', 'classes'));
    }

    public function create()
    {
        abort_unless(can_access("students", "manage_students", "add"), 403);

        $students = Student::where('archive', false)->get(); // For sibling dropdown
        $categories = StudentCategory::all();
        $classrooms = Classroom::all();
        $streams = Stream::all();

        return view('students.create', compact('students', 'categories', 'classrooms', 'streams'));
    }

    public function edit($id)
    {
        abort_unless(can_access("students", "manage_students", "edit"), 403);

        $student = Student::with(['parent', 'classroom', 'stream', 'category'])->findOrFail($id);
        $categories = StudentCategory::all();
        $classrooms = Classroom::all();
        $streams = Stream::all();

        return view('students.edit', compact('student', 'categories', 'classrooms', 'streams'));
    }
    public function update(Request $request, $id)
    {
        abort_unless(can_access("students", "manage_students", "edit"), 403);

        $student = Student::findOrFail($id);

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

        $student->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'classroom_id' => $request->classroom_id,
            'stream_id' => $request->stream_id,
            'category_id' => $request->category_id,
            'nemis_number' => $request->nemis_number,
            'knec_assessment_number' => $request->knec_assessment_number,
        ]);

        // Update parent if present
        if ($student->parent) {
            $student->parent->update($request->only([
                'father_name', 'father_phone', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email'
            ]));
        }

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    public function archive($id)
    {
        abort_unless(can_access("students", "manage_students", "delete"), 403);

        $student = Student::findOrFail($id);
        $student->archive = true;
        $student->save();

        return redirect()->route('students.index')->with('success', 'Student archived successfully.');
    }

    public function restore($id)
    {
        abort_unless(can_access("students", "manage_students", "edit"), 403);

        $student = Student::findOrFail($id);
        $student->archive = false;
        $student->save();

        return redirect()->route('students.index')->with('success', 'Student restored successfully.');
    }


    private function generateNextAdmissionNumber()
    {
        $prefix = SystemSetting::getValue('student_id_prefix', 'ADM');
        $counter = SystemSetting::incrementValue('student_id_counter', SystemSetting::getValue('student_id_start', 1000));
        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

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

            $parent = ParentInfo::create($request->only([
                'father_name', 'father_phone', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email'
            ]));

            $admission_number = $this->generateNextAdmissionNumber();

            $student = Student::create([
                'admission_number' => $admission_number,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'parent_id' => $parent->id,
                'classroom_id' => $request->classroom_id,
                'stream_id' => $request->stream_id,
                'category_id' => $request->category_id,
                'nemis_number' => $request->nemis_number,
                'knec_assessment_number' => $request->knec_assessment_number,
            ]);

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

    protected function sendAdmissionCommunication($student, $parent)
    {
        $className = optional($student->classroom)->name ?? '';
        $fullName = $student->getFullNameAttribute();

        // SMS
        $smsTemplate = SMSTemplate::where('code', 'student_admission')->first();
        $smsMessage = $smsTemplate
            ? str_replace(['{name}', '{class}'], [$fullName, $className], $smsTemplate->message)
            : "Dear Parent, your child {$fullName} has been admitted to class {$className}.";

        foreach ([$parent->father_phone, $parent->mother_phone, $parent->guardian_phone] as $phone) {
            if ($phone) {
                $this->smsService->sendSMS($phone, $smsMessage);
            }
        }

        // EMAIL
        $emailTemplate = EmailTemplate::where('code', 'student_admission')->first();
        if ($emailTemplate) {
            $subject = $emailTemplate->title;
            $body = str_replace(
                ['{name}', '{class}'],
                [$student->getFullNameAttribute(), $className],
                $emailTemplate->message
            );

            foreach ([$parent->father_email, $parent->mother_email, $parent->guardian_email] as $email) {
                if ($email) {
                    try {
                        Mail::to($email)->send(new \App\Mail\GenericMail($subject, $body));
                    } catch (\Throwable $e) {
                        \Log::error("Email sending failed to $email: " . $e->getMessage());
                    }
                }
            }
        }

    }

    // ============= KEEP EXISTING METHODS AS IS =============

    public function bulkForm()
    {
        return view('students.bulk');
    }

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

            $classroomId = Classroom::where('name', $rowData['classroom'] ?? '')->value('id');
            $streamId = Stream::where('name', $rowData['stream'] ?? '')->value('id');
            $categoryId = StudentCategory::where('name', $rowData['category'] ?? '')->value('id');

            $rowData['classroom_id'] = $classroomId;
            $rowData['stream_id'] = $streamId;
            $rowData['category_id'] = $categoryId;

            $rowData['classroom_name'] = $rowData['classroom'] ?? '';
            $rowData['stream_name'] = $rowData['stream'] ?? '';
            $rowData['category_name'] = $rowData['category'] ?? '';

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
}

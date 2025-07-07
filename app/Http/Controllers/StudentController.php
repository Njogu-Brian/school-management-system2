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
        $students = Student::with(['parent', 'classroom', 'stream', 'category'])
            ->when($request->name, fn ($q, $name) => $q->where('name', 'like', "%$name%"))
            ->when($request->admission_number, fn ($q, $adNo) => $q->where('admission_number', $adNo))
            ->when($request->classroom_id, fn ($q, $id) => $q->where('classroom_id', $id))
            ->where('archive', false)
            ->get();

        $classes = Classroom::all();
        return view('students.index', compact('students', 'classes'));
    }

    public function create()
    {
        $parents = ParentInfo::all();
        $categories = StudentCategory::all();
        $classes = Classroom::all();
        $streams = Stream::all();
        $students = Student::with('parent')->get();

        return view('students.create', compact('parents', 'categories', 'classes', 'streams', 'students'))->with('classrooms', $classes);
    }

    public function getStreams(Request $request)
    {
        $classroomId = $request->classroom_id;
        $streams = Stream::whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))->get();
        return response()->json($streams);
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

            $admission_number = Student::max('admission_number') + 1;

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

            // ✅ Notify via SMS template
            $smsTemplate = SMSTemplate::where('code', 'student_admission')->first();
            $smsMessage = $smsTemplate ? str_replace(
                ['{name}', '{class}'],
                [$student->getFullNameAttribute(), $student->classroom->name ?? ''],
                $smsTemplate->message
            ) : "Dear Parent, your child {$student->getFullNameAttribute()} has been successfully admitted.";

            foreach ([$parent->father_phone, $parent->mother_phone, $parent->guardian_phone] as $phone) {
                if ($phone) {
                    $this->smsService->sendSMS($phone, $smsMessage);
                }
            }

            // ✅ Notify via Email template
            $emailTemplate = EmailTemplate::where('code', 'student_admission')->first();
            if ($emailTemplate) {
                $subject = $emailTemplate->title;
                $body = str_replace(
                    ['{name}', '{class}'],
                    [$student->getFullNameAttribute(), $student->classroom->name ?? ''],
                    $emailTemplate->message
                );

                foreach ([$parent->father_email, $parent->mother_email, $parent->guardian_email] as $email) {
                    if ($email) {
                        Mail::to($email)->send(new GenericMail($subject, $body));
                    }
                }
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            'parent_id' => 'nullable|exists:parent_info,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'nullable|exists:student_categories,id',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
        ]);

        $student = Student::findOrFail($id);

        $student->update($request->only([
            'first_name', 'middle_name', 'last_name', 'gender', 'dob',
            'classroom_id', 'stream_id', 'category_id',
            'nemis_number', 'knec_assessment_number'
        ]));

        $parent = ParentInfo::updateOrCreate(
            ['id' => $student->parent_id],
            $request->only([
                'father_name', 'father_phone', 'father_email', 'father_id_number',
                'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
                'guardian_name', 'guardian_phone', 'guardian_email'
            ])
        );

        if (!$student->parent_id) {
            $student->update(['parent_id' => $parent->id]);
        }

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    public function archive($id)
    {
        abort_unless(can_access("students", "manage_students", "delete"), 403);
        $student = Student::findOrFail($id);
        $student->update(['archive' => true]);
        return redirect()->route('students.index')->with('success', 'Student archived.');
    }

    public function restore($id)
    {
        abort_unless(can_access("students", "manage_students", "edit"), 403);
        $student = Student::findOrFail($id);
        $student->update(['archive' => false]);
        return redirect()->route('students.index')->with('success', 'Student restored.');
    }

    public function edit($id)
    {
        abort_unless(can_access("students", "manage_students", "edit"), 403);
        $student = Student::with('parent')->findOrFail($id);
        $parents = ParentInfo::all();
        $categories = StudentCategory::all();
        $classes = Classroom::all();
        $streams = Stream::whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $student->classroom_id))->get();

        return view('students.edit', compact('student', 'parents', 'categories', 'classes', 'streams'));
    }
}

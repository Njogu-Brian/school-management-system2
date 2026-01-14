<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StudentRequirement;
use App\Models\RequirementTemplate;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ItemReceipt;
use App\Models\ActivityLog;
use App\Models\CommunicationLog;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CommunicationService;
use App\Services\WhatsAppService;

class StudentRequirementController extends Controller
{
    protected $comm;
    protected $whatsappService;

    public function __construct(CommunicationService $comm, WhatsAppService $whatsappService)
    {
        $this->comm = $comm;
        $this->whatsappService = $whatsappService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StudentRequirement::with(['student.classroom', 'requirementTemplate.requirementType', 'collectedBy']);

        // Teachers and Senior Teachers see only their assigned classes
        if ($user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher')) {
            $assignedClassroomIds = $user->hasRole('Senior Teacher')
                ? array_unique(array_merge(
                    $user->getAssignedClassroomIds(),
                    $user->getSupervisedClassroomIds()
                ))
                : $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $query->whereHas('student', function($q) use ($assignedClassroomIds) {
                    $q->whereIn('classroom_id', $assignedClassroomIds);
                });
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        $requirements = $query->latest()->paginate(30);
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.student-requirements.index', compact(
            'requirements', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function collectForm(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        $selectedClassroomId = $request->filled('classroom_id') && in_array($request->classroom_id, $assignedClassroomIds) 
            ? $request->classroom_id 
            : null;

        $selectedStreamId = $request->filled('stream_id') ? $request->stream_id : null;

        // Get classrooms
        $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();

        // Get streams for selected classroom
        $streams = collect();
        if ($selectedClassroomId) {
            $classroom = Classroom::find($selectedClassroomId);
            if ($classroom) {
                $streams = $classroom->allStreams();
            }
        }

        // Get students based on classroom and stream selection
        $students = collect();
        if ($selectedClassroomId) {
            $query = Student::where('classroom_id', $selectedClassroomId)
                ->where('status', 'active') // Only active students
                ->with(['classroom', 'stream', 'parent']);

            if ($selectedStreamId) {
                $query->where('stream_id', $selectedStreamId);
            }

            $students = $query->orderBy('first_name')->get();
        }

        // Get requirement templates for selected class
        $templates = collect();
        if ($selectedClassroomId) {
            $templates = RequirementTemplate::where('classroom_id', $selectedClassroomId)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                ->where('is_active', true)
                ->with('requirementType')
                ->orderBy('requirement_type_id')
                ->get();
        }

        return view('inventory.student-requirements.collect', compact(
            'students', 
            'templates', 
            'classrooms', 
            'streams',
            'currentYear', 
            'currentTerm',
            'selectedClassroomId',
            'selectedStreamId'
        ));
    }

    /**
     * Load streams for a classroom
     */
    public function loadStreams(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $classroomId = $request->classroom_id;

        // Verify teacher has access to this classroom
        if (!in_array($classroomId, $assignedClassroomIds)) {
            return response()->json(['error' => 'You do not have access to this classroom.'], 403);
        }

        $classroom = Classroom::findOrFail($classroomId);
        $streams = $classroom->allStreams()->map(function($stream) {
            return [
                'id' => $stream->id,
                'name' => $stream->name,
            ];
        });

        return response()->json(['streams' => $streams]);
    }

    /**
     * Load students based on classroom and stream selection
     */
    public function loadStudents(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $classroomId = $request->classroom_id;
        $streamId = $request->stream_id;

        // Verify teacher has access to this classroom
        if (!in_array($classroomId, $assignedClassroomIds)) {
            return response()->json(['error' => 'You do not have access to this classroom.'], 403);
        }

        $query = Student::where('classroom_id', $classroomId)
            ->where('status', 'active') // Only active students
            ->with(['classroom', 'stream']);

        if ($streamId) {
            $query->where('stream_id', $streamId);
        }

        $students = $query->orderBy('first_name')->get()->map(function($student) {
            return [
                'id' => $student->id,
                'name' => $student->getNameAttribute(),
                'admission_number' => $student->admission_number,
                'classroom' => $student->classroom->name ?? '',
                'stream' => $student->stream->name ?? '',
            ];
        });

        return response()->json(['students' => $students]);
    }

    /**
     * Load existing requirement data for a student
     */
    public function loadStudentRequirements(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($student->classroom_id, $assignedClassroomIds)) {
                return response()->json(['error' => 'You do not have access to this student.'], 403);
            }
        }

        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        if (!$currentYear || !$currentTerm) {
            return response()->json(['error' => 'No active academic year or term found.'], 400);
        }

        // Get all requirements for this student
        $requirements = StudentRequirement::where('student_id', $student->id)
            ->where('academic_year_id', $currentYear->id)
            ->where('term_id', $currentTerm->id)
            ->with('requirementTemplate.requirementType')
            ->get()
            ->map(function($req) {
                return [
                    'template_id' => $req->requirement_template_id,
                    'quantity_collected' => $req->quantity_collected,
                    'quantity_required' => $req->quantity_required,
                    'notes' => $req->notes,
                    'status' => $req->status,
                ];
            })
            ->keyBy('template_id');

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->getNameAttribute(),
            ],
            'requirements' => $requirements,
        ]);
    }

    public function collect(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'requirements' => 'required|array',
            'requirements.*.template_id' => 'required|exists:requirement_templates,id',
            'requirements.*.quantity_collected' => 'required|numeric|min:0',
            'requirements.*.notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $student = Student::findOrFail($validated['student_id']);

        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($student->classroom_id, $assignedClassroomIds)) {
                return back()->with('error', 'You do not have access to collect requirements for this student.');
            }
        }

        DB::beginTransaction();
        try {
            $currentYear = AcademicYear::where('is_active', true)->first();
            $currentTerm = Term::where('is_current', true)->first();

            foreach ($validated['requirements'] as $reqData) {
                $template = RequirementTemplate::findOrFail($reqData['template_id']);
                
                // Find or create student requirement
                $studentRequirement = StudentRequirement::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'requirement_template_id' => $template->id,
                        'academic_year_id' => $currentYear->id,
                        'term_id' => $currentTerm->id,
                    ],
                    [
                        'quantity_required' => $template->quantity_per_student,
                        'expected_quantity' => $template->quantity_per_student,
                        'quantity_collected' => 0,
                        'quantity_missing' => $template->quantity_per_student,
                        'balance_pending' => $template->quantity_per_student,
                        'status' => 'pending',
                        'can_update_receipt' => true,
                    ]
                );

                // Check payment status before allowing collection
                $paymentCheck = $studentRequirement->canReceiveItems();
                if (!$paymentCheck['allowed']) {
                    DB::rollBack();
                    return back()->with('error', "Cannot collect items for {$student->first_name} {$student->last_name}: {$paymentCheck['reason']}");
                }

                // Get quantity to add (difference between submitted and already collected)
                $quantitySubmitted = (float)($reqData['quantity_collected'] ?? 0);
                $quantityToAdd = max(0, $quantitySubmitted - $studentRequirement->quantity_collected);

                // Determine receipt status
                $expected = $studentRequirement->expected_quantity ?? $studentRequirement->quantity_required;
                
                if ($quantitySubmitted == 0) {
                    $receiptStatus = 'not_received';
                } elseif ($quantitySubmitted >= $expected) {
                    $receiptStatus = 'fully_received';
                } else {
                    $receiptStatus = 'partially_received';
                }

                // Record receipt if there's a quantity to add
                if ($quantityToAdd > 0) {
                    $studentRequirement->recordReceipt(
                        $quantityToAdd,
                        $user->id,
                        $receiptStatus,
                        $reqData['notes'] ?? null
                    );
                } elseif ($quantitySubmitted == 0 && $studentRequirement->quantity_collected > 0) {
                    // If updating to 0, create a receipt record for tracking
                    ItemReceipt::create([
                        'student_requirement_id' => $studentRequirement->id,
                        'student_id' => $student->id,
                        'classroom_id' => $student->classroom_id,
                        'received_by' => $user->id,
                        'quantity_received' => 0,
                        'receipt_status' => 'not_received',
                        'notes' => $reqData['notes'] ?? 'Items updated to not received',
                        'received_at' => now(),
                    ]);
                }

                // Update notes if provided
                if (!empty($reqData['notes'])) {
                    $existingNotes = $studentRequirement->notes ?? '';
                    $newNote = date('Y-m-d H:i') . ': ' . $reqData['notes'];
                    $studentRequirement->notes = $existingNotes ? $existingNotes . "\n" . $newNote : $newNote;
                }

                // Update collected_by and collected_at if first collection
                if (!$studentRequirement->collected_by && $quantitySubmitted > 0) {
                    $studentRequirement->collected_by = $user->id;
                    $studentRequirement->collected_at = now();
                }

                $studentRequirement->save();
                $studentRequirement->updateStatus();
            }

            DB::commit();

            // Send notification to parent
            $this->notifyParent($student);

            ActivityLog::log('update', $student, "Collected requirements for {$student->first_name} {$student->last_name}");

            return back()->with('success', 'Requirements collected successfully. Parent has been notified.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error collecting requirements: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing receipt (for partial receipts)
     */
    public function updateReceipt(Request $request, StudentRequirement $requirement)
    {
        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0',
            'receipt_status' => 'required|in:fully_received,partially_received,not_received',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $student = $requirement->student;

        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($student->classroom_id, $assignedClassroomIds)) {
                return back()->with('error', 'You do not have access to update requirements for this student.');
            }
        }

        if (!$requirement->can_update_receipt) {
            return back()->with('error', 'This receipt cannot be updated.');
        }

        DB::beginTransaction();
        try {
            $quantity = $validated['quantity_received'];
            
            if ($quantity > 0) {
                $requirement->recordReceipt(
                    $quantity,
                    $user->id,
                    $validated['receipt_status'],
                    $validated['notes'] ?? null
                );
            }

            DB::commit();
            return back()->with('success', 'Receipt updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating receipt: ' . $e->getMessage());
        }
    }

    protected function notifyParent(Student $student)
    {
        if (!$student->parent) {
            return;
        }

        $parent = $student->parent;
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        $requirements = StudentRequirement::where('student_id', $student->id)
            ->where('academic_year_id', $currentYear->id)
            ->where('term_id', $currentTerm->id)
            ->with('requirementTemplate.requirementType')
            ->get();

        if ($requirements->isEmpty()) {
            return;
        }

        $studentName = "{$student->first_name} {$student->last_name}";
        $yearTerm = "{$currentYear->year} / {$currentTerm->name}";

        // Build detailed message for WhatsApp and Email
        $detailedMessage = "Dear Parent,\n\n";
        $detailedMessage .= "REQUIREMENTS COLLECTION UPDATE\n";
        $detailedMessage .= "Student: {$studentName}\n";
        $detailedMessage .= "Academic Period: {$yearTerm}\n\n";
        
        $collectedItems = [];
        $missingItems = [];
        
        foreach ($requirements as $req) {
            $template = $req->requirementTemplate;
            $typeName = $template->requirementType->name;
            $brand = $template->brand ?? 'Any brand';
            $collected = $req->quantity_collected ?? 0;
            $required = $req->quantity_required ?? 0;
            $unit = $template->unit ?? 'piece';
            
            if ($collected > 0) {
                $collectedItems[] = "✅ {$typeName} - {$collected} {$unit} ({$brand})";
            }
            
            if ($collected < $required) {
                $missing = $required - $collected;
                $missingItems[] = "⚠️ {$typeName} - Collected: {$collected}/{$required} {$unit} (Missing: {$missing} {$unit})";
            }
        }

        if (!empty($collectedItems)) {
            $detailedMessage .= "COLLECTED ITEMS:\n";
            $detailedMessage .= implode("\n", $collectedItems) . "\n\n";
        }

        if (!empty($missingItems)) {
            $detailedMessage .= "PENDING ITEMS:\n";
            $detailedMessage .= implode("\n", $missingItems) . "\n\n";
            $detailedMessage .= "Please provide the missing items.\n\n";
        }

        $detailedMessage .= "Thank you.";

        // Simple SMS message
        $smsMessage = "Dear Parent, we have received requirements for {$studentName} ({$yearTerm}). ";
        if (empty($missingItems)) {
            $smsMessage .= "All items collected. Thank you.";
        } else {
            $smsMessage .= "Some items pending. Check WhatsApp/Email for details. Thank you.";
        }

        // Send SMS - simple notification
        if ($parent->getPrimaryContactPhoneAttribute()) {
            try {
                $this->comm->sendSMS(
                    'parent',
                    $parent->id,
                    $parent->getPrimaryContactPhoneAttribute(),
                    $smsMessage,
                    'Requirements Received'
                );
            } catch (\Exception $e) {
                Log::error('Failed to send SMS for requirements', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send WhatsApp - detailed message
        $whatsappNumber = $parent->father_whatsapp 
            ?? $parent->mother_whatsapp 
            ?? $parent->guardian_whatsapp 
            ?? null;
            
        if ($whatsappNumber) {
            try {
                $result = $this->whatsappService->sendMessage($whatsappNumber, $detailedMessage);
                
                // Log WhatsApp communication
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id' => $parent->id,
                    'contact' => $whatsappNumber,
                    'channel' => 'whatsapp',
                    'title' => 'Requirements Collection Update',
                    'message' => $detailedMessage,
                    'type' => 'requirements',
                    'status' => ($result['status'] ?? 'error') === 'success' ? 'sent' : 'failed',
                    'response' => $result,
                    'scope' => 'requirements',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp for requirements', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send Email - detailed message
        if ($parent->getPrimaryContactEmailAttribute()) {
            try {
                $this->comm->sendEmail(
                    'parent',
                    $parent->id,
                    $parent->getPrimaryContactEmailAttribute(),
                    'Requirements Collection Update',
                    nl2br($detailedMessage)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send Email for requirements', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Mark as notified
        $requirements->each(function($req) {
            $req->update(['notified_parent' => true]);
        });
    }

    public function show(StudentRequirement $requirement)
    {
        $user = Auth::user();
        
        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($requirement->student->classroom_id, $assignedClassroomIds)) {
                abort(403, 'You do not have access to view this requirement.');
            }
        }

        $requirement->load([
            'student.classroom', 
            'requirementTemplate.requirementType', 
            'collectedBy',
            'receipts.receivedBy'
        ]);
        
        // Check payment status
        $paymentCheck = $requirement->canReceiveItems();
        
        return view('inventory.student-requirements.show', compact('requirement', 'paymentCheck'));
    }
}

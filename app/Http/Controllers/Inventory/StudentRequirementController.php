<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StudentRequirement;
use App\Models\RequirementTemplate;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\CommunicationService;

class StudentRequirementController extends Controller
{
    protected $comm;

    public function __construct(CommunicationService $comm)
    {
        $this->comm = $comm;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StudentRequirement::with(['student.classroom', 'requirementTemplate.requirementType', 'collectedBy']);

        // Teachers see only their assigned classes
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
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

        $query = Student::whereIn('classroom_id', $assignedClassroomIds)
            ->with(['classroom', 'parent']);

        if ($request->filled('classroom_id') && in_array($request->classroom_id, $assignedClassroomIds)) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $students = $query->orderBy('first_name')->get();

        // Get requirement templates for these classes
        $templates = RequirementTemplate::whereIn('classroom_id', $assignedClassroomIds)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
            ->where('is_active', true)
            ->with('requirementType')
            ->get();

        $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();

        return view('inventory.student-requirements.collect', compact(
            'students', 'templates', 'classrooms', 'currentYear', 'currentTerm'
        ));
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
                
                $studentRequirement = StudentRequirement::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'requirement_template_id' => $template->id,
                        'academic_year_id' => $currentYear->id,
                        'term_id' => $currentTerm->id,
                    ],
                    [
                        'quantity_required' => $template->quantity_per_student,
                        'quantity_collected' => 0,
                        'quantity_missing' => $template->quantity_per_student,
                        'status' => 'pending',
                    ]
                );

                $studentRequirement->quantity_collected += $reqData['quantity_collected'];
                $studentRequirement->collected_by = $user->id;
                $studentRequirement->collected_at = now();
                if ($reqData['notes']) {
                    $studentRequirement->notes = ($studentRequirement->notes ? $studentRequirement->notes . "\n" : '') . $reqData['notes'];
                }
                $studentRequirement->updateStatus();

                // If item should be left with teacher, add to inventory
                if ($template->leave_with_teacher && $reqData['quantity_collected'] > 0) {
                    $inventoryItem = InventoryItem::firstOrCreate(
                        [
                            'name' => $template->requirementType->name,
                            'brand' => $template->brand,
                        ],
                        [
                            'category' => $template->requirementType->category ?? 'stationery',
                            'unit' => $template->unit,
                            'quantity' => 0,
                            'min_stock_level' => 0,
                        ]
                    );

                    InventoryTransaction::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => $user->id,
                        'student_requirement_id' => $studentRequirement->id,
                        'type' => 'in',
                        'quantity' => $reqData['quantity_collected'],
                        'notes' => "Collected from {$student->first_name} {$student->last_name}",
                    ]);

                    $inventoryItem->refresh();
                }
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

    protected function notifyParent(Student $student)
    {
        if (!$student->parent) {
            return;
        }

        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        $requirements = StudentRequirement::where('student_id', $student->id)
            ->where('academic_year_id', $currentYear->id)
            ->where('term_id', $currentTerm->id)
            ->with('requirementTemplate.requirementType')
            ->get();

        $allCollected = $requirements->every(fn($r) => $r->status === 'complete');
        $missingItems = $requirements->filter(fn($r) => $r->status !== 'complete');

        $message = "Dear Parent,\n\n";
        $message .= "Requirements collection update for {$student->first_name} {$student->last_name}:\n\n";

        if ($allCollected) {
            $message .= "✅ All requirements have been collected successfully.\n\n";
        } else {
            $message .= "⚠️ Some requirements are missing:\n\n";
            foreach ($missingItems as $req) {
                $missing = $req->quantity_required - $req->quantity_collected;
                $message .= "- {$req->requirementTemplate->requirementType->name}: Missing {$missing} {$req->requirementTemplate->unit}\n";
            }
            $message .= "\nPlease provide the missing items.\n\n";
        }

        $message .= "Thank you.";

        // Send SMS
        if ($student->parent->phone) {
            $this->comm->sendSMS('parent', $student->parent->id, $student->parent->phone, $message);
        }

        // Send Email
        if ($student->parent->email) {
            $this->comm->sendEmail(
                'parent',
                $student->parent->id,
                $student->parent->email,
                'Requirements Collection Update',
                nl2br($message)
            );
        }

        // Mark as notified
        $requirements->each(function($req) {
            $req->update(['notified_parent' => true]);
        });
    }

    public function show(StudentRequirement $requirement)
    {
        $requirement->load(['student.classroom', 'requirementTemplate.requirementType', 'collectedBy']);
        return view('inventory.student-requirements.show', compact('requirement'));
    }
}

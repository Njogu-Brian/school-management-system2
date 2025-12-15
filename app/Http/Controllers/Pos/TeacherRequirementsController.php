<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\StudentRequirement;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherRequirementsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StudentRequirement::with([
            'student.classroom', 
            'requirementTemplate.requirementType', 
            'requirementTemplate.posProduct',
            'posOrder',
            'posOrderItem',
            'collectedBy'
        ]);

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

        if ($request->filled('purchase_source')) {
            if ($request->purchase_source === 'pos') {
                $query->where('purchased_through_pos', true);
            } else {
                $query->where(function($q) {
                    $q->where('purchased_through_pos', false)
                      ->orWhereNull('purchased_through_pos');
                });
            }
        }

        $requirements = $query->latest()->paginate(30);
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => StudentRequirement::whereHas('student', function($q) use ($user) {
                if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
                    $assignedClassroomIds = $user->getAssignedClassroomIds();
                    if (!empty($assignedClassroomIds)) {
                        $q->whereIn('classroom_id', $assignedClassroomIds);
                    }
                }
            })->count(),
            'complete' => StudentRequirement::whereHas('student', function($q) use ($user) {
                if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
                    $assignedClassroomIds = $user->getAssignedClassroomIds();
                    if (!empty($assignedClassroomIds)) {
                        $q->whereIn('classroom_id', $assignedClassroomIds);
                    }
                }
            })->where('status', 'complete')->count(),
            'pending' => StudentRequirement::whereHas('student', function($q) use ($user) {
                if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
                    $assignedClassroomIds = $user->getAssignedClassroomIds();
                    if (!empty($assignedClassroomIds)) {
                        $q->whereIn('classroom_id', $assignedClassroomIds);
                    }
                }
            })->where('status', 'pending')->count(),
            'pos_purchases' => StudentRequirement::whereHas('student', function($q) use ($user) {
                if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
                    $assignedClassroomIds = $user->getAssignedClassroomIds();
                    if (!empty($assignedClassroomIds)) {
                        $q->whereIn('classroom_id', $assignedClassroomIds);
                    }
                }
            })->where('purchased_through_pos', true)->count(),
        ];

        return view('pos.teacher-requirements.index', compact(
            'requirements', 'classrooms', 'academicYears', 'terms', 'stats'
        ));
    }

    public function markReceived(Request $request, StudentRequirement $requirement)
    {
        $user = Auth::user();
        
        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($requirement->student->classroom_id, $assignedClassroomIds)) {
                return back()->with('error', 'You do not have access to this requirement.');
            }
        }

        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0|max:' . ($requirement->quantity_required - $requirement->quantity_collected),
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($requirement, $validated, $user) {
            $requirement->quantity_collected += $validated['quantity_received'];
            $requirement->collected_by = $user->id;
            $requirement->collected_at = now();
            
            if ($validated['notes']) {
                $requirement->notes = ($requirement->notes ? $requirement->notes . "\n" : '') . 
                    date('Y-m-d H:i') . ': ' . $validated['notes'];
            }
            
            $requirement->updateStatus();
            $requirement->save();

            // If item should be left with teacher, add to inventory
            if ($requirement->requirementTemplate->leave_with_teacher && $validated['quantity_received'] > 0) {
                $inventoryItem = \App\Models\InventoryItem::firstOrCreate(
                    [
                        'name' => $requirement->requirementTemplate->requirementType->name,
                        'brand' => $requirement->requirementTemplate->brand,
                    ],
                    [
                        'category' => $requirement->requirementTemplate->requirementType->category ?? 'stationery',
                        'unit' => $requirement->requirementTemplate->unit,
                        'quantity' => 0,
                        'min_stock_level' => 0,
                    ]
                );

                \App\Models\InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'user_id' => $user->id,
                    'student_requirement_id' => $requirement->id,
                    'type' => 'in',
                    'quantity' => $validated['quantity_received'],
                    'notes' => "Collected from {$requirement->student->first_name} {$requirement->student->last_name}",
                ]);
            }
        });

        \App\Models\ActivityLog::log('update', $requirement, "Marked requirement as received for {$requirement->student->first_name} {$requirement->student->last_name}");

        return back()->with('success', 'Requirement marked as received successfully.');
    }

    public function show(StudentRequirement $requirement)
    {
        $user = Auth::user();
        
        // Verify teacher has access
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($requirement->student->classroom_id, $assignedClassroomIds)) {
                abort(403, 'You do not have access to this requirement.');
            }
        }

        $requirement->load([
            'student.classroom', 
            'requirementTemplate.requirementType',
            'requirementTemplate.posProduct',
            'posOrder',
            'posOrderItem',
            'collectedBy'
        ]);

        return view('pos.teacher-requirements.show', compact('requirement'));
    }
}




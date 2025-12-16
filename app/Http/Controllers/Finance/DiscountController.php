<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeConcession;
use App\Models\DiscountTemplate;
use App\Models\Student;
use App\Models\Votehead;
use App\Models\Invoice;
use App\Models\AcademicYear;
use App\Services\DiscountService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function index(Request $request)
    {
        $query = FeeConcession::with(['student', 'votehead', 'invoice', 'family'])
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('discount_type'), fn($q) => $q->where('discount_type', $request->discount_type))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active));

        $discounts = $query->latest()->paginate(20)->withQueryString();
        return view('finance.discounts.index', compact('discounts'));
    }

    public function create()
    {
        $voteheads = Votehead::orderBy('name')->get();
        $students = collect(); // Empty collection - students are selected during allocation, not template creation
        $invoices = collect(); // Empty collection - invoices are selected during allocation, not template creation
        return view('finance.discounts.create', compact('voteheads', 'students', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed_amount',
            'discount_type' => 'required|in:sibling,referral,early_repayment,transport,manual,other',
            'frequency' => 'required|in:termly,yearly,once,manual',
            'scope' => 'required|in:votehead,invoice,student,family',
            'value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'end_date' => 'nullable|date',
            'requires_approval' => 'nullable|boolean',
            'sibling_rules' => 'nullable|array',
            'sibling_rules.*' => 'numeric|min:0|max:100',
            'votehead_ids' => 'nullable|array',
            'votehead_ids.*' => 'exists:voteheads,id',
        ]);

        try {
            // Process sibling_rules - convert to JSON format {2: 5, 3: 10, etc.}
            $siblingRules = null;
            if (!empty($validated['sibling_rules'])) {
                $siblingRules = [];
                foreach ($validated['sibling_rules'] as $position => $value) {
                    if ($value !== null && $value !== '') {
                        $siblingRules[(int)$position] = (float)$value;
                    }
                }
                $siblingRules = !empty($siblingRules) ? $siblingRules : null;
            }

            // Process votehead_ids
            $voteheadIds = !empty($validated['votehead_ids']) ? array_map('intval', $validated['votehead_ids']) : null;

            $template = DiscountTemplate::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'discount_type' => $validated['discount_type'],
                'frequency' => $validated['frequency'],
                'scope' => $validated['scope'],
                'value' => $validated['value'],
                'sibling_rules' => $siblingRules,
                'votehead_ids' => $voteheadIds,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'requires_approval' => $validated['requires_approval'] ?? true,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
            
            return redirect()
                ->route('finance.discounts.templates.index')
                ->with('success', 'Discount template created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(FeeConcession $discount)
    {
        $discount->load(['student', 'votehead', 'invoice', 'family', 'approver', 'creator']);
        return view('finance.discounts.show', compact('discount'));
    }

    public function applySiblingDiscount(Student $student)
    {
        try {
            $discount = $this->discountService->applySiblingDiscount($student);
            if ($discount) {
                return back()->with('success', 'Sibling discount applied successfully.');
            }
            return back()->with('info', 'No sibling discount applicable.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Discount Templates Management
    public function templatesIndex()
    {
        $templates = DiscountTemplate::with('creator')
            ->latest()
            ->paginate(20);
        return view('finance.discounts.templates.index', compact('templates'));
    }

    // Discount Allocation
    public function allocate()
    {
        $templates = DiscountTemplate::where('is_active', true)->orderBy('name')->get();
        $students = Student::orderBy('first_name')->get();
        $voteheads = Votehead::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        return view('finance.discounts.allocate', compact('templates', 'students', 'voteheads', 'academicYears', 'currentYear'));
    }

    public function storeAllocation(Request $request)
    {
        $validated = $request->validate([
            'discount_template_id' => 'required|exists:discount_templates,id',
            'student_id' => 'required|exists:students,id',
            'term' => 'required|in:1,2,3',
            'year' => 'required|integer',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'votehead_ids' => 'nullable|array',
            'votehead_ids.*' => 'exists:voteheads,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $template = DiscountTemplate::findOrFail($validated['discount_template_id']);
            
            // Get academic year if not provided
            if (empty($validated['academic_year_id'])) {
                $academicYear = AcademicYear::where('year', $validated['year'])->first();
                if ($academicYear) {
                    $validated['academic_year_id'] = $academicYear->id;
                }
            }

            // If votehead scope, require votehead_ids
            if ($template->scope === 'votehead' && empty($validated['votehead_ids'])) {
                return back()->withInput()->with('error', 'Please select at least one votehead for votehead-specific discounts.');
            }

            // Create allocations
            $allocations = [];
            if ($template->scope === 'votehead' && !empty($validated['votehead_ids'])) {
                // Create one allocation per votehead
                foreach ($validated['votehead_ids'] as $voteheadId) {
                    $allocations[] = FeeConcession::create([
                        'discount_template_id' => $template->id,
                        'student_id' => $validated['student_id'],
                        'votehead_id' => $voteheadId,
                        'term' => $validated['term'],
                        'year' => $validated['year'],
                        'academic_year_id' => $validated['academic_year_id'] ?? null,
                        'type' => $template->type,
                        'discount_type' => $template->discount_type,
                        'frequency' => $template->frequency,
                        'scope' => $template->scope,
                        'value' => $template->value,
                        'reason' => $template->reason,
                        'description' => $template->description,
                        'start_date' => $validated['start_date'] ?? now(),
                        'end_date' => $validated['end_date'] ?? $template->end_date,
                        'is_active' => true,
                        'approval_status' => $template->requires_approval ? 'pending' : 'approved',
                        'approved_by' => $template->requires_approval ? null : auth()->id(),
                        'created_by' => auth()->id(),
                    ]);
                }
            } else {
                // Single allocation for other scopes
                $allocations[] = FeeConcession::create([
                    'discount_template_id' => $template->id,
                    'student_id' => $validated['student_id'],
                    'term' => $validated['term'],
                    'year' => $validated['year'],
                    'academic_year_id' => $validated['academic_year_id'] ?? null,
                    'type' => $template->type,
                    'discount_type' => $template->discount_type,
                    'frequency' => $template->frequency,
                    'scope' => $template->scope,
                    'value' => $template->value,
                    'reason' => $template->reason,
                    'description' => $template->description,
                    'start_date' => $validated['start_date'] ?? now(),
                    'end_date' => $validated['end_date'] ?? $template->end_date,
                    'is_active' => true,
                    'approval_status' => $template->requires_approval ? 'pending' : 'approved',
                    'approved_by' => $template->requires_approval ? null : auth()->id(),
                    'created_by' => auth()->id(),
                ]);
            }

            $count = count($allocations);
            return redirect()
                ->route('finance.discounts.allocations.index')
                ->with('success', "Discount allocated successfully. {$count} allocation(s) created.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // View allocated discounts
    public function allocationsIndex(Request $request)
    {
        $query = FeeConcession::with(['student', 'discountTemplate', 'votehead', 'academicYear', 'creator'])
            ->whereNotNull('discount_template_id')
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('term'), fn($q) => $q->where('term', $request->term))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->year))
            ->when($request->filled('approval_status'), fn($q) => $q->where('approval_status', $request->approval_status));

        $allocations = $query->latest()->paginate(20)->withQueryString();
        $students = Student::orderBy('first_name')->get();
        
        return view('finance.discounts.allocations.index', compact('allocations', 'students'));
    }

    // Discount Approvals
    public function approvalsIndex(Request $request)
    {
        $query = FeeConcession::with(['student', 'discountTemplate', 'votehead', 'creator'])
            ->where('approval_status', 'pending')
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id));

        $pendingApprovals = $query->latest()->paginate(20)->withQueryString();
        $students = Student::orderBy('first_name')->get();
        
        return view('finance.discounts.approvals.index', compact('pendingApprovals', 'students'));
    }

    public function approve(FeeConcession $discount)
    {
        $discount->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Discount approved successfully.');
    }

    public function reject(Request $request, FeeConcession $discount)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $discount->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Discount rejected.');
    }

    // Bulk Sibling Discount Allocation
    public function bulkAllocateSibling(Request $request)
    {
        $validated = $request->validate([
            'discount_template_id' => 'required|exists:discount_templates,id',
            'term' => 'required|in:1,2,3',
            'year' => 'required|integer',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        try {
            $template = DiscountTemplate::findOrFail($validated['discount_template_id']);
            
            if ($template->discount_type !== 'sibling') {
                return back()->with('error', 'Selected template is not a sibling discount.');
            }

            // Get academic year if not provided
            if (empty($validated['academic_year_id'])) {
                $academicYear = AcademicYear::where('year', $validated['year'])->first();
                if ($academicYear) {
                    $validated['academic_year_id'] = $academicYear->id;
                }
            }

            // Get all families with 2+ active students
            $families = \App\Models\Family::with(['students' => function($q) {
                $q->where('status', 'active')
                  ->whereNotNull('dob')
                  ->orderBy('dob', 'desc'); // Youngest first (most recent DOB)
            }])
            ->has('students', '>=', 2)
            ->get();

            $allocationsCreated = 0;
            $errors = [];

            foreach ($families as $family) {
                $students = $family->students->where('status', 'active')->whereNotNull('dob');
                
                if ($students->count() < 2) {
                    continue;
                }

                // Sort by DOB (youngest first)
                $sortedStudents = $students->sortByDesc('dob')->values();
                $siblingCount = $sortedStudents->count();

                // Apply discounts based on child number from oldest
                // 2 children: youngest (2nd child) gets 5%
                // 3 children: second youngest (2nd child) gets 5%, youngest (3rd child) gets 10%
                // 4 children: 2nd gets 5%, 3rd gets 10%, 4th gets 15%
                // Pattern: Child number from oldest determines discount (2nd = 5%, 3rd = 10%, 4th = 15%, etc.)
                
                foreach ($sortedStudents as $index => $student) {
                    // Index 0 = youngest, Index 1 = second youngest, etc.
                    // Child number from oldest: 1 = oldest, 2 = second oldest, etc.
                    $childNumberFromOldest = $siblingCount - $index;
                    
                    // Only apply to children after the first (oldest doesn't get discount)
                    if ($childNumberFromOldest >= 2) {
                        // Get discount value from template based on child position
                        $discountValue = $template->getDiscountForChildPosition($childNumberFromOldest);
                        
                        // Check if allocation already exists for this student/term/year
                        $existing = FeeConcession::where('student_id', $student->id)
                            ->where('discount_template_id', $template->id)
                            ->where('term', $validated['term'])
                            ->where('year', $validated['year'])
                            ->first();
                        
                        if ($existing) {
                            continue; // Skip if already allocated
                        }

                        try {
                            // Handle votehead-specific discounts
                            if ($template->scope === 'votehead' && !empty($template->votehead_ids)) {
                                // Create one allocation per votehead
                                foreach ($template->votehead_ids as $voteheadId) {
                                    // Check if allocation already exists
                                    $existing = FeeConcession::where('student_id', $student->id)
                                        ->where('discount_template_id', $template->id)
                                        ->where('votehead_id', $voteheadId)
                                        ->where('term', $validated['term'])
                                        ->where('year', $validated['year'])
                                        ->first();
                                    
                                    if ($existing) {
                                        continue; // Skip if already allocated
                                    }

                                    FeeConcession::create([
                                        'discount_template_id' => $template->id,
                                        'student_id' => $student->id,
                                        'family_id' => $family->id,
                                        'votehead_id' => $voteheadId,
                                        'term' => $validated['term'],
                                        'year' => $validated['year'],
                                        'academic_year_id' => $validated['academic_year_id'] ?? null,
                                        'type' => $template->type,
                                        'discount_type' => 'sibling',
                                        'frequency' => $template->frequency,
                                        'scope' => $template->scope,
                                        'value' => $discountValue,
                                        'reason' => "Sibling discount - {$childNumberFromOldest} of {$siblingCount} children",
                                        'description' => $template->description ?? "Automatic sibling discount allocation. Family has {$siblingCount} children.",
                                        'start_date' => now(),
                                        'end_date' => $template->end_date,
                                        'is_active' => true,
                                        'approval_status' => $template->requires_approval ? 'pending' : 'approved',
                                        'approved_by' => $template->requires_approval ? null : auth()->id(),
                                        'created_by' => auth()->id(),
                                    ]);
                                    $allocationsCreated++;
                                }
                            } else {
                                // Single allocation for other scopes
                                // Check if allocation already exists
                                $existing = FeeConcession::where('student_id', $student->id)
                                    ->where('discount_template_id', $template->id)
                                    ->where('term', $validated['term'])
                                    ->where('year', $validated['year'])
                                    ->first();
                                
                                if ($existing) {
                                    continue; // Skip if already allocated
                                }

                                FeeConcession::create([
                                    'discount_template_id' => $template->id,
                                    'student_id' => $student->id,
                                    'family_id' => $family->id,
                                    'term' => $validated['term'],
                                    'year' => $validated['year'],
                                    'academic_year_id' => $validated['academic_year_id'] ?? null,
                                    'type' => $template->type,
                                    'discount_type' => 'sibling',
                                    'frequency' => $template->frequency,
                                    'scope' => $template->scope,
                                    'value' => $discountValue,
                                    'reason' => "Sibling discount - {$childNumberFromOldest} of {$siblingCount} children",
                                    'description' => $template->description ?? "Automatic sibling discount allocation. Family has {$siblingCount} children.",
                                    'start_date' => now(),
                                    'end_date' => $template->end_date,
                                    'is_active' => true,
                                    'approval_status' => $template->requires_approval ? 'pending' : 'approved',
                                    'approved_by' => $template->requires_approval ? null : auth()->id(),
                                    'created_by' => auth()->id(),
                                ]);
                                $allocationsCreated++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Failed to allocate for {$student->first_name} {$student->last_name}: " . $e->getMessage();
                        }
                    }
                }
            }

            $message = "Bulk allocation completed. {$allocationsCreated} discount(s) allocated.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(s) occurred.";
            }

            return redirect()
                ->route('finance.discounts.allocations.index')
                ->with('success', $message)
                ->with('errors', $errors);

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function bulkAllocateSiblingForm()
    {
        $templates = DiscountTemplate::where('is_active', true)
            ->where('discount_type', 'sibling')
            ->orderBy('name')
            ->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        return view('finance.discounts.bulk-allocate-sibling', compact('templates', 'academicYears', 'currentYear'));
    }
}

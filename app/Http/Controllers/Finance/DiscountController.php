<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeConcession;
use App\Models\Student;
use App\Models\Votehead;
use App\Models\Invoice;
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
        $students = Student::orderBy('first_name')->get();
        $voteheads = Votehead::orderBy('name')->get();
        $invoices = Invoice::with('student')->orderBy('invoice_number', 'desc')->limit(100)->get();
        
        return view('finance.discounts.create', compact('students', 'voteheads', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'family_id' => 'nullable|exists:families,id',
            'votehead_id' => 'nullable|exists:voteheads,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'type' => 'required|in:percentage,fixed_amount',
            'discount_type' => 'required|in:sibling,referral,early_repayment,transport,manual,other',
            'frequency' => 'required|in:termly,yearly,once,manual',
            'scope' => 'required|in:votehead,invoice,student,family',
            'value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'auto_approve' => 'nullable|boolean',
        ]);

        try {
            $discount = $this->discountService->createDiscount($validated);
            return redirect()
                ->route('finance.discounts.show', $discount)
                ->with('success', 'Discount created successfully.');
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
}


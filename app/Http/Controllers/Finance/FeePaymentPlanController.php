<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeePaymentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = FeePaymentPlan::with(['student', 'invoice']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->latest()->paginate(20)->withQueryString();
        return view('finance.fee_payment_plans.index', compact('plans'));
    }

    public function create()
    {
        $students = Student::with('classroom')->orderBy('first_name')->get();
        return view('finance.fee_payment_plans.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'total_amount' => 'required|numeric|min:0',
            'installment_count' => 'required|integer|min:2|max:12',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $installmentAmount = $validated['total_amount'] / $validated['installment_count'];
            $daysBetween = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date']));
            $daysPerInstallment = floor($daysBetween / $validated['installment_count']);

            $plan = FeePaymentPlan::create([
                'student_id' => $validated['student_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'installment_count' => $validated['installment_count'],
                'installment_amount' => $installmentAmount,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'active',
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
            ]);

            // Create installments
            $currentDate = Carbon::parse($validated['start_date']);
            for ($i = 1; $i <= $validated['installment_count']; $i++) {
                FeePaymentPlanInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'amount' => $i === $validated['installment_count'] 
                        ? $validated['total_amount'] - ($installmentAmount * ($validated['installment_count'] - 1))
                        : $installmentAmount,
                    'due_date' => $currentDate->copy(),
                    'status' => 'pending',
                ]);
                $currentDate->addDays($daysPerInstallment);
            }

            DB::commit();
            return redirect()->route('finance.fee-payment-plans.show', $plan)
                ->with('success', 'Payment plan created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating payment plan: ' . $e->getMessage());
        }
    }

    public function show(FeePaymentPlan $feePaymentPlan)
    {
        $feePaymentPlan->load(['student', 'invoice', 'installments', 'creator']);
        return view('finance.fee_payment_plans.show', compact('feePaymentPlan'));
    }

    public function updateStatus(Request $request, FeePaymentPlan $feePaymentPlan)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,cancelled',
        ]);

        $feePaymentPlan->update(['status' => $validated['status']]);
        return back()->with('success', 'Payment plan status updated.');
    }
}

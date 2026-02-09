<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Student;
use App\Models\Invoice;
use App\Services\PaymentPlanNotificationService;
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
        $students = Student::with('classroom')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('first_name')
            ->get();
        return view('finance.fee_payment_plans.create', compact('students'));
    }

    /**
     * API: Get invoices and siblings for the given student (for payment plan form).
     */
    public function getStudentInvoicesAndSiblings(Student $student)
    {
        try {
            $invoices = Invoice::where('student_id', $student->id)
                ->orderBy('due_date', 'desc')
                ->get()
                ->map(function ($inv) {
                    $total = (float) ($inv->total ?? 0);
                    $paid = (float) ($inv->paid_amount ?? 0);
                    $balance = isset($inv->balance) ? (float) $inv->balance : ($total - $paid);
                    return [
                        'id' => $inv->id,
                        'invoice_number' => $inv->invoice_number ?? 'Inv',
                        'total' => $total,
                        'balance' => max(0, $balance),
                        'due_date' => $inv->due_date ? (\Carbon\Carbon::parse($inv->due_date)->format('Y-m-d')) : null,
                        'term' => $inv->term?->name ?? null,
                        'academic_year' => $inv->academicYear?->year ?? $inv->year ?? null,
                    ];
                })
                ->values()
                ->all();

            $siblings = [];
            if ($student->family_id) {
                $siblings = Student::where('family_id', $student->family_id)
                    ->where('id', '!=', $student->id)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->orderBy('first_name')
                    ->get()
                    ->map(function ($s) {
                        $totalBalance = Invoice::where('student_id', $s->id)->get()->sum(fn ($i) => max(0, (float) ($i->balance ?? (float)($i->total ?? 0) - (float)($i->paid_amount ?? 0))));
                        return [
                            'id' => $s->id,
                            'name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                            'admission_number' => $s->admission_number ?? '',
                            'total_outstanding' => round($totalBalance, 2),
                        ];
                    })
                    ->values()
                    ->all();
            }

            $combinedTotal = collect($invoices)->sum('balance');
            foreach ($siblings as $s) {
                $combinedTotal += (float) ($s['total_outstanding'] ?? 0);
            }

            return response()->json([
                'invoices' => $invoices,
                'siblings' => $siblings,
                'combined_total' => round($combinedTotal, 2),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Fee payment plan student invoices failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
            return response()->json(['invoices' => [], 'siblings' => []], 200);
        }
    }

public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'total_amount' => 'required|numeric|min:0',
            'schedule_type' => 'required|in:one_time,weekly,monthly,custom',
            'installment_count' => 'required_unless:schedule_type,custom|nullable|integer|min:1|max:24',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'installments' => 'required_if:schedule_type,custom|nullable|array',
            'installments.*.due_date' => 'required_with:installments|date',
            'installments.*.amount' => 'required_with:installments|numeric|min:0',
        ]);

        $primaryStudent = Student::findOrFail($validated['student_id']);
        $totalAmount = (float) $validated['total_amount'];

        $scheduleType = $validated['schedule_type'];
        $installmentCount = $scheduleType === 'one_time' ? 1 : (int) ($validated['installment_count'] ?? 1);
        if ($installmentCount < 1) {
            $installmentCount = 1;
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;
        if (!$endDate && $installmentCount >= 1) {
            if ($scheduleType === 'weekly') {
                $endDate = $startDate->copy()->addWeeks($installmentCount - 1);
            } elseif ($scheduleType === 'monthly') {
                $endDate = $startDate->copy()->addMonths($installmentCount - 1);
            } else {
                $endDate = $startDate->copy();
            }
        }
        $endDate = $endDate ? $endDate->format('Y-m-d') : $startDate->format('Y-m-d');

        $installmentAmount = $installmentCount > 0 ? round($totalAmount / $installmentCount, 2) : $totalAmount;

        DB::beginTransaction();
        try {
            $plan = FeePaymentPlan::create([
                'student_id' => $primaryStudent->id,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'total_amount' => $totalAmount,
                'installment_count' => $installmentCount,
                'installment_amount' => $installmentAmount,
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'status' => 'active',
                'notes' => ($validated['notes'] ?? '') . ($primaryStudent->family_id ? ' (Combined family plan)' : ''),
                'created_by' => auth()->id(),
            ]);

            if ($scheduleType === 'custom' && !empty($validated['installments'])) {
                $customInstallments = collect($validated['installments'])->sortBy('due_date')->values();
                $num = 0;
                foreach ($customInstallments as $row) {
                    $num++;
                    FeePaymentPlanInstallment::create([
                        'payment_plan_id' => $plan->id,
                        'installment_number' => $num,
                        'amount' => (float) ($row['amount'] ?? 0),
                        'due_date' => $row['due_date'],
                        'status' => 'pending',
                    ]);
                }
                $plan->update(['installment_count' => $num]);
            } else {
                $currentDate = $startDate->copy();
                for ($i = 1; $i <= $installmentCount; $i++) {
                    $instAmount = $i === $installmentCount
                        ? $totalAmount - round($installmentAmount * ($installmentCount - 1), 2)
                        : $installmentAmount;
                    FeePaymentPlanInstallment::create([
                        'payment_plan_id' => $plan->id,
                        'installment_number' => $i,
                        'amount' => round($instAmount, 2),
                        'due_date' => $currentDate->copy(),
                        'status' => 'pending',
                    ]);
                    if ($scheduleType === 'weekly') {
                        $currentDate->addWeek();
                    } elseif ($scheduleType === 'monthly') {
                        $currentDate->addMonth();
                    } else {
                        $daysBetween = $installmentCount > 1 ? (int) floor($startDate->diffInDays(Carbon::parse($endDate)) / ($installmentCount - 1)) : 0;
                        $currentDate->addDays(max(0, $daysBetween));
                    }
                }
            }

            DB::commit();

            try {
                app(PaymentPlanNotificationService::class)->notifyParentOnPlanCreated($plan);
            } catch (\Throwable $e) {
                \Log::warning('Payment plan notification failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
            }

            $msg = $primaryStudent->family_id
                ? 'One combined payment plan created for the family. Parent has been notified where available.'
                : 'Payment plan created successfully. Parent has been notified via SMS, WhatsApp and Email where available.';
            return redirect()->route('finance.fee-payment-plans.show', $plan)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating payment plan: ' . $e->getMessage());
        }
    }

    protected function getStudentTotalOutstanding(Student $student): float
    {
        return (float) Invoice::where('student_id', $student->id)->get()->sum(fn ($i) => max(0, (float) ($i->balance ?? $i->total - $i->paid_amount)));
    }

    protected function getFirstInvoiceIdForStudent(Student $student): ?int
    {
        $inv = Invoice::where('student_id', $student->id)->orderBy('due_date', 'desc')->first();
        return $inv ? (int) $inv->id : null;
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

    /**
     * Public view of payment plan using hashed ID (no authentication required)
     * This route only accepts hashed_id (10 chars), not numeric IDs
     */
    public function publicView(string $hash)
    {
        // Explicitly find by hashed_id to prevent numeric ID access
        $plan = FeePaymentPlan::where('hashed_id', $hash)
            ->whereRaw('LENGTH(hashed_id) = 10') // Ensure it's exactly 10 chars
            ->firstOrFail();
        
        $plan->load(['student.parent', 'invoice', 'installments', 'creator']);
        
        // Ensure invoice has hashed_id for public pay link
        if ($plan->invoice && !$plan->invoice->hashed_id) {
            $plan->invoice->hashed_id = \App\Models\Invoice::generateHashedId();
            $plan->invoice->save();
        }
        
        // Get school settings (via ReceiptService for consistent branding)
        $schoolSettings = app(\App\Services\ReceiptService::class)->getSchoolSettings();
        
        return view('finance.fee_payment_plans.public', compact('plan', 'schoolSettings'));
    }
}

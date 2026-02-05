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
        $invoices = Invoice::where('student_id', $student->id)
            ->with(['academicYear', 'term'])
            ->orderBy('due_date', 'desc')
            ->get()
            ->map(function ($inv) {
                $balance = (float) ($inv->balance ?? $inv->total - $inv->paid_amount);
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'total' => (float) $inv->total,
                    'balance' => max(0, $balance),
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                    'term' => $inv->term?->name,
                    'academic_year' => $inv->academicYear?->year ?? $inv->year,
                ];
            });

        $siblings = [];
        if ($student->family_id) {
            $siblings = Student::where('family_id', $student->family_id)
                ->where('id', '!=', $student->id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->orderBy('first_name')
                ->get()
                ->map(function ($s) {
                    $totalBalance = Invoice::where('student_id', $s->id)->get()->sum(fn ($i) => max(0, (float) ($i->balance ?? $i->total - $i->paid_amount)));
                    return [
                        'id' => $s->id,
                        'name' => $s->full_name ?? trim($s->first_name . ' ' . $s->last_name),
                        'admission_number' => $s->admission_number,
                        'total_outstanding' => round($totalBalance, 2),
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json([
            'invoices' => $invoices,
            'siblings' => $siblings,
        ]);
    }

public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'total_amount' => 'required|numeric|min:0',
            'installment_count' => 'required|integer|min:2|max:12',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'apply_to_siblings' => 'nullable|boolean',
        ]);

        if (empty($validated['end_date'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $validated['end_date'] = $startDate->copy()->addMonths($validated['installment_count'] - 1)->format('Y-m-d');
        }

        $primaryStudent = Student::findOrFail($validated['student_id']);
        $siblingStudents = [];
        if (! empty($validated['apply_to_siblings']) && $primaryStudent->family_id) {
            $siblingStudents = Student::where('family_id', $primaryStudent->family_id)
                ->where('id', '!=', $primaryStudent->id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get();
        }

        $studentsToPlan = collect([$primaryStudent])->merge($siblingStudents);
        $createdPlans = [];

        DB::beginTransaction();
        try {
            foreach ($studentsToPlan as $index => $student) {
                $totalAmount = $index === 0
                    ? $validated['total_amount']
                    : $this->getStudentTotalOutstanding($student);
                if ($totalAmount <= 0 && $index > 0) {
                    continue;
                }
                $invoiceId = $index === 0 ? ($validated['invoice_id'] ?? null) : $this->getFirstInvoiceIdForStudent($student);
                $installmentAmount = $totalAmount / $validated['installment_count'];
                $daysBetween = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date']));
                $daysPerInstallment = $validated['installment_count'] > 1 ? (int) floor($daysBetween / $validated['installment_count']) : 0;

                $plan = FeePaymentPlan::create([
                    'student_id' => $student->id,
                    'invoice_id' => $invoiceId,
                    'total_amount' => $totalAmount,
                    'installment_count' => $validated['installment_count'],
                    'installment_amount' => $installmentAmount,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'active',
                    'notes' => $validated['notes'] . ($index > 0 ? ' (Same schedule – family)' : ''),
                    'created_by' => auth()->id(),
                ]);

                $currentDate = Carbon::parse($validated['start_date']);
                for ($i = 1; $i <= $validated['installment_count']; $i++) {
                    $instAmount = $i === $validated['installment_count']
                        ? $totalAmount - ($installmentAmount * ($validated['installment_count'] - 1))
                        : $installmentAmount;
                    FeePaymentPlanInstallment::create([
                        'payment_plan_id' => $plan->id,
                        'installment_number' => $i,
                        'amount' => $instAmount,
                        'due_date' => $currentDate->copy(),
                        'status' => 'pending',
                    ]);
                    $currentDate->addDays($daysPerInstallment);
                }
                $createdPlans[] = $plan;
            }

            DB::commit();

            $notificationService = app(PaymentPlanNotificationService::class);
            foreach ($createdPlans as $plan) {
                try {
                    $notificationService->notifyParentOnPlanCreated($plan);
                } catch (\Throwable $e) {
                    \Log::warning('Payment plan notification failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
                }
            }

            $firstPlan = $createdPlans[0];
            $msg = count($createdPlans) > 1
                ? count($createdPlans) . ' payment plans created (one per sibling). Parents notified via SMS, WhatsApp and Email where available.'
                : 'Payment plan created successfully. Parent has been notified via SMS, WhatsApp and Email where available.';
            return redirect()->route('finance.fee-payment-plans.show', $firstPlan)
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
        
        // Get school settings
        $schoolSettings = \App\Services\ReceiptService::getSchoolSettings();
        
        return view('finance.fee_payment_plans.public', compact('plan', 'schoolSettings'));
    }
}

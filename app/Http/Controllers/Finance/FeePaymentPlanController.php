<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Student;
use App\Models\Invoice;
use App\Services\PaymentPlanNotificationService;
use App\Services\PaymentPlanSyncService;
use App\Services\ReceiptService;
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
            $familyId = $student->family_id;

            $invoiceQuery = Invoice::query()
                ->whereNull('reversed_at')
                // Do not trust legacy status/balance fields alone; compute live totals from items/allocations.
                ->with([
                    'student',
                    'items.allocations.payment',
                ])
                ->orderBy('due_date', 'desc');

            if ($familyId) {
                $invoiceQuery->where('family_id', $familyId);
            } else {
                $invoiceQuery->where('student_id', $student->id);
            }

            $invoiceModels = $invoiceQuery->get();

            $invoices = $invoiceModels
                ->map(function (Invoice $inv) {
                    // Live totals from loaded relations (safe for stale legacy invoices).
                    $inv->fillTotalsFromLoadedRelations();

                    $total = (float) ($inv->total ?? 0);
                    $paid = (float) ($inv->paid_amount ?? 0);
                    $balance = (float) ($inv->balance ?? max(0, $total - $paid));

                    $stu = $inv->student;
                    $studentName = $stu?->full_name ?? trim((string) (($stu->first_name ?? '') . ' ' . ($stu->last_name ?? '')));

                    return [
                        'id' => $inv->id,
                        'invoice_number' => $inv->invoice_number ?? 'Inv',
                        'total' => $total,
                        'balance' => max(0, $balance),
                        'due_date' => $inv->due_date ? (\Carbon\Carbon::parse($inv->due_date)->format('Y-m-d')) : null,
                        'term' => $inv->term?->name ?? null,
                        'academic_year' => $inv->academicYear?->year ?? $inv->year ?? null,
                        'student_id' => $inv->student_id,
                        'student_name' => $studentName,
                        'admission_number' => $stu?->admission_number ?? '',
                    ];
                })
                // only keep invoices that still have outstanding balance
                ->filter(fn ($row) => (float) ($row['balance'] ?? 0) > 0.009)
                ->values()
                ->all();

            // Build family roster (selected student + siblings) so UI always shows all students,
            // even if one has 0 outstanding invoices.
            $familyStudents = collect();
            if ($student->family_id) {
                $familyStudents = Student::where('family_id', $student->family_id)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->orderBy('first_name')
                    ->get();
            } else {
                $familyStudents = collect([$student]);
            }

            $siblings = $familyStudents
                ->filter(fn ($s) => $s->id !== $student->id)
                ->map(function ($s) use ($invoices) {
                    $studentInvoices = collect($invoices)->where('student_id', $s->id)->values();
                    $totalBalance = (float) $studentInvoices->sum('balance');
                    return [
                        'id' => $s->id,
                        'name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                        'admission_number' => $s->admission_number ?? '',
                        'total_outstanding' => round($totalBalance, 2),
                    ];
                })
                ->values()
                ->all();

            // Group invoices by student for UI rendering (selected + siblings).
            $byStudent = $familyStudents->map(function ($s) use ($invoices) {
                $rows = collect($invoices)->where('student_id', $s->id)->values();
                $total = (float) $rows->sum('total');
                $balance = (float) $rows->sum('balance');
                return [
                    'student_id' => (int) $s->id,
                    'student_name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    'admission_number' => $s->admission_number ?? '',
                    'total_invoice_amount' => round($total, 2),
                    'total_outstanding' => round($balance, 2),
                    'invoices' => $rows->all(),
                ];
            })->values()->all();

            $combinedTotal = collect($byStudent)->sum('total_outstanding');

            return response()->json([
                'invoices' => $invoices,
                'siblings' => $siblings,
                'family_students' => $byStudent,
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
        $syncService = app(PaymentPlanSyncService::class);

        // Always compute total from outstanding invoices (student-only or family-wide) to avoid mismatch and duplicate plans.
        $outstandingInvoiceIds = $syncService->collectOutstandingInvoiceIdsForStudentOrFamily($primaryStudent)->all();
        $outstandingInvoices = !empty($outstandingInvoiceIds)
            ? Invoice::whereIn('id', $outstandingInvoiceIds)->get()
            : collect();
        $computedTotal = (float) $outstandingInvoices->sum(fn ($i) => max(0, (float) ($i->balance ?? 0)));
        $totalAmount = round($computedTotal, 2);

        if ($totalAmount <= 0.009) {
            \Log::warning('Payment plan create blocked: no outstanding invoices', [
                'student_id' => $primaryStudent->id,
                'family_id' => $primaryStudent->family_id,
                'outstanding_invoice_ids' => $outstandingInvoiceIds,
                'computed_total' => $totalAmount,
                'schedule_type' => $validated['schedule_type'] ?? null,
            ]);
            return back()
                ->withInput()
                ->withErrors([
                    'total_amount' => 'No outstanding active invoices were found for this student/family. Payment plan cannot be created.',
                ]);
        }

        // If there is already an active family plan, adjust it instead of creating a new one.
        $existingPlan = null;
        if ($primaryStudent->family_id) {
            $existingPlan = FeePaymentPlan::query()
                ->whereIn('status', ['active', 'compliant', 'overdue', 'broken'])
                ->whereHas('student', fn ($q) => $q->where('family_id', $primaryStudent->family_id))
                ->latest()
                ->first();
        }

        $scheduleType = $validated['schedule_type'];
        $installmentCount = $scheduleType === 'one_time' ? 1 : (int) ($validated['installment_count'] ?? 1);
        if ($installmentCount < 1) {
            $installmentCount = 1;
        }

        // For custom schedules, enforce that installment sum exactly covers the computed total.
        if ($scheduleType === 'custom') {
            $rows = collect($validated['installments'] ?? [])
                ->filter(fn ($r) => is_array($r))
                ->values();

            if ($rows->isEmpty()) {
                \Log::warning('Payment plan custom schedule blocked: empty installments', [
                    'student_id' => $primaryStudent->id,
                    'family_id' => $primaryStudent->family_id,
                    'computed_total' => $totalAmount,
                ]);
                return back()
                    ->withInput()
                    ->with('error', 'Custom plan not created: please add at least one installment (date + amount).')
                    ->withErrors(['installments' => 'Please add at least one custom installment (date and amount).']);
            }

            $sum = (float) $rows->sum(fn ($r) => (float) ($r['amount'] ?? 0));
            if (abs(round($sum, 2) - round($totalAmount, 2)) > 0.009) {
                \Log::warning('Payment plan custom schedule blocked: sum mismatch', [
                    'student_id' => $primaryStudent->id,
                    'family_id' => $primaryStudent->family_id,
                    'computed_total' => $totalAmount,
                    'client_total_amount' => (float) ($validated['total_amount'] ?? 0),
                    'installment_sum' => round($sum, 2),
                    'outstanding_invoice_ids' => $outstandingInvoiceIds,
                ]);
                return back()
                    ->withInput()
                    ->with('error', 'Custom plan not created: installment amounts must match the outstanding total.')
                    ->withErrors([
                        'installments' => 'Custom installment amounts must add up to the full total (KES ' . number_format($totalAmount, 2) . '). Current sum: KES ' . number_format($sum, 2) . '.',
                    ]);
            }
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
            $invoice = !empty($validated['invoice_id']) ? Invoice::find($validated['invoice_id']) : null;

            if ($existingPlan) {
                // Sync invoices + totals, and reuse the existing plan (no duplicates for the same family).
                $existingPlan->loadMissing('student');
                $existingPlan->invoices()->sync($outstandingInvoiceIds);
                if (!$existingPlan->invoice_id) {
                    $existingPlan->invoice_id = $outstandingInvoiceIds[0] ?? null;
                }
                $existingPlan->notes = trim((string) ($validated['notes'] ?? $existingPlan->notes ?? ''));
                $existingPlan->updated_by = auth()->id();
                $existingPlan->save();

                $syncService->syncPlanFromInvoices($existingPlan);

                DB::commit();

                return redirect()->route('finance.fee-payment-plans.show', $existingPlan)
                    ->with('success', 'Existing family payment plan updated to include all active invoices (no duplicate plan created).');
            }

            $plan = FeePaymentPlan::create([
                'student_id' => $primaryStudent->id,
                'invoice_id' => $outstandingInvoiceIds[0] ?? ($validated['invoice_id'] ?? null),
                'term_id' => $invoice?->term_id,
                'academic_year_id' => $invoice?->academic_year_id,
                'total_amount' => $totalAmount,
                'installment_count' => $installmentCount,
                'installment_amount' => $installmentAmount,
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'status' => 'active',
                'notes' => ($validated['notes'] ?? '') . ($primaryStudent->family_id ? ' (Combined family plan)' : ''),
                'created_by' => auth()->id(),
            ]);

            if (!empty($outstandingInvoiceIds)) {
                $plan->invoices()->sync($outstandingInvoiceIds);
            } elseif (!empty($validated['invoice_id'])) {
                // Back-compat: if no outstanding invoices detected but user linked one, attach it.
                $plan->invoices()->sync([(int) $validated['invoice_id']]);
            }

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

            // Ensure plan is immediately synced to invoice balances.
            try {
                $syncService->syncPlanFromInvoices($plan);
            } catch (\Throwable $e) {
                \Log::warning('Payment plan sync after create failed', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
            }

            $msg = $primaryStudent->family_id
                ? 'One combined payment plan created for the family. Parent has been notified where available.'
                : 'Payment plan created successfully. Parent has been notified via SMS, WhatsApp and Email where available.';
            return redirect()->route('finance.fee-payment-plans.show', $plan)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment plan create failed', [
                'student_id' => $primaryStudent->id,
                'family_id' => $primaryStudent->family_id,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'schedule_type' => $validated['schedule_type'] ?? null,
                'outstanding_invoice_ids' => $outstandingInvoiceIds ?? [],
                'computed_total' => $totalAmount ?? null,
                'error' => $e->getMessage(),
            ]);
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
        $feePaymentPlan->load(['student', 'invoice', 'installments', 'creator', 'invoices.student']);
        return view('finance.fee_payment_plans.show', compact('feePaymentPlan'));
    }

    /**
     * Printable HTML (browser print / save as PDF) with letterhead and signature blocks.
     */
    public function printAgreement(FeePaymentPlan $feePaymentPlan)
    {
        $data = app(ReceiptService::class)->buildPaymentPlanAgreementData($feePaymentPlan, auth()->user());
        $data['showPrintChrome'] = true;

        return view('finance.fee_payment_plans.pdf.agreement', $data);
    }

    /**
     * Download agreement as PDF (DomPDF).
     */
    public function downloadAgreementPdf(FeePaymentPlan $feePaymentPlan)
    {
        $pdf = app(ReceiptService::class)->generatePaymentPlanAgreementPdf($feePaymentPlan, auth()->user());
        $student = $feePaymentPlan->student;
        $studentSlug = \Illuminate\Support\Str::slug($student->full_name ?? 'student');
        $filename = 'Payment_plan_agreement_' . $studentSlug . '_' . $feePaymentPlan->id . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
        
        // Unified payment link: same /pay/{id} format as receipt pay-now. Family link allows paying all children.
        $payNowUrl = null;
        if ($plan->student) {
            if ($plan->student->family_id) {
                $link = \App\Models\PaymentLink::getOrCreateFamilyLink($plan->student->family_id, null, 'payment_plan');
                $payNowUrl = route('payment.link.show', $link->hashed_id);
            } else {
                $existing = \App\Models\PaymentLink::where('student_id', $plan->student->id)
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->whereRaw('use_count < max_uses')
                    ->first();
                if ($existing) {
                    $payNowUrl = route('payment.link.show', $existing->hashed_id);
                } else {
                    $link = \App\Models\PaymentLink::create([
                        'student_id' => $plan->student->id,
                        'family_id' => null,
                        'amount' => (float) ($plan->invoice->balance ?? $plan->total_amount ?? 0),
                        'currency' => 'KES',
                        'description' => 'Payment plan - ' . $plan->student->full_name,
                        'status' => 'active',
                        'expires_at' => now()->addDays(90),
                        'max_uses' => 999,
                        'metadata' => ['source' => 'payment_plan'],
                    ]);
                    $payNowUrl = route('payment.link.show', $link->hashed_id);
                }
            }
        }
        
        // Get school settings (via ReceiptService for consistent branding)
        $schoolSettings = app(\App\Services\ReceiptService::class)->getSchoolSettings();
        
        return view('finance.fee_payment_plans.public', compact('plan', 'schoolSettings', 'payNowUrl'));
    }
}

<?php

namespace App\Services;

use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Invoice;
use App\Models\PaymentThreshold;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Automatically create payment plans after fee collection when the parent has met the
 * minimum threshold (e.g. 50%) but there is still an outstanding balance.
 *
 * This keeps "fees collection" and "payment plans" aligned: once a threshold is met,
 * the remaining balance can be structured into installments and will appear in Payment Plans
 * + participate in installment reminders.
 */
class AutoPaymentPlanService
{
    public function __construct(
        protected PaymentPlanComplianceService $compliance,
        protected PaymentPlanSyncService $syncService,
        protected PaymentPlanNotificationService $notificationService
    ) {}

    public function maybeCreateAfterPayment(Student $student, ?Invoice $touchedInvoice = null): ?FeePaymentPlan
    {
        if ($student->archive || $student->is_alumni) {
            return null;
        }

        $term = null;
        if ($touchedInvoice && $touchedInvoice->term_id) {
            $term = Term::find($touchedInvoice->term_id);
        }
        if (!$term) {
            $term = Term::where('is_current', true)->first();
        }
        if (!$term) {
            return null;
        }

        // Only apply to students with parents set (otherwise there’s no meaningful "plan communication").
        if (!$student->parent_id) {
            return null;
        }

        // Determine minimum percentage for this term + category.
        $threshold = PaymentThreshold::where('term_id', $term->id)
            ->where('student_category_id', $student->category_id)
            ->where('is_active', true)
            ->first();
        if (!$threshold) {
            return null;
        }

        $percentPaid = $this->compliance->getPaymentPercentage($student, $term);
        $min = (float) ($threshold->minimum_percentage ?? 0);
        if ($percentPaid + 0.0001 < $min) {
            return null; // threshold not met
        }
        if ($percentPaid >= 99.999) {
            return null; // already fully paid
        }

        // Compute outstanding across student/family invoices (active/unpaid/partial).
        $outstandingInvoiceIds = $this->syncService->collectOutstandingInvoiceIdsForStudentOrFamily($student)->all();
        if (empty($outstandingInvoiceIds)) {
            return null;
        }
        $totalOutstanding = (float) Invoice::whereIn('id', $outstandingInvoiceIds)->sum('balance');
        if ($totalOutstanding <= 0.009) {
            return null;
        }

        // Avoid duplicates: if an active plan exists, just sync it.
        $existing = FeePaymentPlan::query()
            ->whereIn('status', ['active', 'compliant', 'overdue', 'broken'])
            ->where('student_id', $student->id)
            ->latest()
            ->first();
        if ($existing) {
            try {
                $existing->invoices()->sync($outstandingInvoiceIds);
            } catch (\Throwable) {
                // ignore if pivot not available in older installs
            }
            $this->syncService->syncPlanFromInvoices($existing);
            return $existing;
        }

        $start = Carbon::today();
        $endBase = $term->closing_date ? Carbon::parse($term->closing_date) : $start->copy()->addMonths(2);
        if ($endBase->lt($start)) {
            $endBase = $start->copy()->addMonths(2);
        }

        // Default: 3 monthly installments (or fewer if term ends sooner).
        $months = max(1, min(6, $start->diffInMonths($endBase) + 1));
        $installmentCount = min(3, $months);

        $installmentAmount = round(((float) $totalOutstanding) / $installmentCount, 2);

        return DB::transaction(function () use (
            $student,
            $outstandingInvoiceIds,
            $totalOutstanding,
            $installmentCount,
            $installmentAmount,
            $start,
            $endBase,
            $term
        ) {
            $plan = FeePaymentPlan::create([
                'student_id' => $student->id,
                'invoice_id' => $outstandingInvoiceIds[0] ?? null,
                'term_id' => $term->id,
                'academic_year_id' => $term->academic_year_id,
                'total_amount' => round((float) $totalOutstanding, 2),
                'installment_count' => $installmentCount,
                'installment_amount' => $installmentAmount,
                'start_date' => $start->toDateString(),
                'end_date' => $endBase->toDateString(),
                'status' => 'active',
                'notes' => 'Auto-created after fee payment (threshold met).',
                'created_by' => auth()->id(),
            ]);

            // Attach invoices (family-wide) when pivot exists.
            try {
                $plan->invoices()->sync($outstandingInvoiceIds);
            } catch (\Throwable) {
            }

            $date = $start->copy();
            for ($i = 1; $i <= $installmentCount; $i++) {
                $amount = $i === $installmentCount
                    ? round(((float) $totalOutstanding) - round($installmentAmount * ($installmentCount - 1), 2), 2)
                    : $installmentAmount;
                FeePaymentPlanInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $i,
                    'amount' => max(0, $amount),
                    'due_date' => $date->copy()->toDateString(),
                    'status' => 'pending',
                ]);
                $date->addMonth();
            }

            // Keep aligned with invoice balances (and adjust installment amounts if needed).
            $this->syncService->syncPlanFromInvoices($plan);

            // Notify parent (best effort).
            try {
                $this->notificationService->notifyParentOnPlanCreated($plan);
            } catch (\Throwable) {
            }

            return $plan;
        });
    }
}


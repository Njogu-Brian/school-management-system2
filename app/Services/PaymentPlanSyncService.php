<?php

namespace App\Services;

use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keeps payment plans aligned with underlying invoice balances.
 *
 * Primary use cases:
 * - Family plans: one plan covers multiple invoices across siblings.
 * - When any invoice is updated (items, discounts, allocations), the plan total and pending installments should adjust.
 */
class PaymentPlanSyncService
{
    /**
     * Sync all active plans impacted by an invoice change.
     */
    public function syncPlansForInvoice(Invoice $invoice): void
    {
        // Avoid recursion if plan updates trigger invoice recalcs elsewhere.
        if (app()->bound('syncing_payment_plans') && app('syncing_payment_plans')) {
            return;
        }

        app()->instance('syncing_payment_plans', true);
        try {
            $plans = FeePaymentPlan::query()
                ->whereIn('status', ['active', 'compliant', 'overdue', 'broken'])
                ->where(function ($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id)
                        ->orWhereHas('invoices', fn ($q2) => $q2->where('invoices.id', $invoice->id));
                })
                ->with(['student', 'installments', 'invoices'])
                ->get();

            foreach ($plans as $plan) {
                $this->syncPlanFromInvoices($plan);
            }
        } finally {
            app()->instance('syncing_payment_plans', false);
        }
    }

    /**
     * Sync a plan total and pending installments from its covered invoices (or, if none attached yet, from student/family).
     */
    public function syncPlanFromInvoices(FeePaymentPlan $plan): void
    {
        $plan->loadMissing(['student', 'installments', 'invoices']);

        $student = $plan->student;
        if (!$student) {
            return;
        }

        $invoiceIds = $plan->invoices->pluck('id')->all();
        if (empty($invoiceIds)) {
            // Fallback: legacy plans linked to a single invoice_id
            if ($plan->invoice_id) {
                $invoiceIds = [$plan->invoice_id];
            } else {
                $invoiceIds = $this->collectOutstandingInvoiceIdsForStudentOrFamily($student)->all();
            }
        }

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->whereNull('reversed_at')
            ->whereIn('status', ['unpaid', 'partial', 'paid']) // allow paid so plan can gracefully go to 0
            ->get();

        $totalOutstanding = (float) $invoices->sum(fn ($i) => max(0, (float) ($i->balance ?? 0)));

        DB::transaction(function () use ($plan, $student, $invoices, $totalOutstanding) {
            // Keep pivot in sync with current outstanding invoices for this student/family when family-scoped.
            $targetInvoiceIds = $this->collectOutstandingInvoiceIdsForStudentOrFamily($student)->all();
            if (!empty($targetInvoiceIds)) {
                $plan->invoices()->sync($targetInvoiceIds);
                if (!$plan->invoice_id) {
                    $plan->invoice_id = $targetInvoiceIds[0] ?? null;
                }
            }

            $plan->total_amount = round($totalOutstanding, 2);

            $this->recomputePendingInstallments($plan);

            $plan->updated_by = auth()->id();
            $plan->save();
        });
    }

    /**
     * Collect all outstanding invoice IDs for the student (or family if present).
     */
    public function collectOutstandingInvoiceIdsForStudentOrFamily(Student $student): Collection
    {
        $invoiceQuery = Invoice::query()
            ->whereNull('reversed_at')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where(function ($q) use ($student) {
                if ($student->family_id) {
                    $q->where('family_id', $student->family_id);
                } else {
                    $q->where('student_id', $student->id);
                }
            })
            ->orderByDesc('due_date')
            ->orderByDesc('id');

        return $invoiceQuery->get()
            ->filter(fn ($inv) => (float) ($inv->balance ?? 0) > 0.009)
            ->pluck('id')
            ->values();
    }

    /**
     * Recompute pending installment amounts so total scheduled (paid + pending) equals plan total_amount.
     *
     * Strategy:
     * - Keep installments that already have paid_amount > 0 untouched.
     * - Distribute the remaining plan balance across remaining installments proportionally (equal split).
     * - Never set negative amounts.
     */
    protected function recomputePendingInstallments(FeePaymentPlan $plan): void
    {
        $plan->loadMissing('installments');

        $installments = $plan->installments->sortBy('installment_number')->values();
        if ($installments->isEmpty()) {
            return;
        }

        $paidTotal = (float) $installments->sum(fn ($i) => (float) ($i->paid_amount ?? 0));
        $remaining = max(0, (float) $plan->total_amount - $paidTotal);

        /** @var \Illuminate\Support\Collection<int, FeePaymentPlanInstallment> $mutable */
        $mutable = $installments->filter(function ($i) {
            return (float) ($i->paid_amount ?? 0) <= 0.009 && in_array($i->status, ['pending', 'partial', 'overdue'], true);
        })->values();

        $count = $mutable->count();
        if ($count <= 0) {
            // Nothing left to adjust.
            $plan->installment_amount = $installments->count() > 0
                ? round(((float) $plan->total_amount) / max(1, (int) $plan->installment_count), 2)
                : 0;
            return;
        }

        $per = $count > 0 ? round($remaining / $count, 2) : $remaining;
        $running = 0.0;

        foreach ($mutable as $idx => $inst) {
            $amount = $idx === ($count - 1)
                ? max(0, round($remaining - $running, 2))
                : max(0, $per);
            $running += $amount;

            $inst->amount = $amount;
            // If plan is now fully covered, mark as completed-like
            if ($amount <= 0.009) {
                $inst->status = 'completed';
            }
            $inst->save();
        }

        // Set indicative installment_amount on plan (first adjustable installment or equal split)
        $plan->installment_amount = $per;
    }
}


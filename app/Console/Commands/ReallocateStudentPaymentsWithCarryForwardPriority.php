<?php

namespace App\Console\Commands;

use App\Models\{Payment, Invoice, Student, InvoiceItem, Votehead};
use App\Services\{PaymentAllocationService, InvoiceService};
use App\Services\StudentBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-allocate a student's payments with "previous term balances first" priority.
 *
 * This command fixes older allocations where payments were applied to current-term items
 * while "Balance brought forward" / "Balance from prior term(s)" lines remained unpaid.
 *
 * Safe to run: it deletes allocations then re-auto-allocates using current allocation order.
 */
class ReallocateStudentPaymentsWithCarryForwardPriority extends Command
{
    protected $signature = 'finance:reallocate-student-carryforward
                            {student? : Student ID, admission number (e.g. RKS463), or "all" for all students with prior-term carry-forward/BBF}
                            {--dry-run : Show what would be done without making changes}
                            {--from-date= : Only consider payments on/after this date (YYYY-MM-DD)}';

    protected $description = 'Re-allocate student payments with prior-term carry-forward/BBF cleared first. Use "all" to fix all students who have a carried-forward balance line.';

    public function handle(): int
    {
        $identifier = $this->argument('student') ?? 'all';
        $dryRun = $this->option('dry-run');

        if (strtolower($identifier) === 'all') {
            return $this->reallocateAllStudentsWithCarryForward($dryRun);
        }

        $student = is_numeric($identifier)
            ? Student::find($identifier)
            : Student::where('admission_number', $identifier)->first();

        if (!$student) {
            $this->error("Student not found: {$identifier}");
            return 1;
        }

        return $this->reallocateStudent($student, $dryRun);
    }

    private function reallocateAllStudentsWithCarryForward(bool $dryRun): int
    {
        // IMPORTANT: Only include students who CURRENTLY still owe carry-forward/BBF.
        // Exclude students whose prior-term/BBF lines exist but are already fully paid/cleared.

        $priorVoteheadId = Votehead::where('code', 'PRIOR_TERM_ARREARS')->value('id');
        $bbfVoteheadId = Votehead::where('code', 'BAL_BF')->value('id');

        $studentIds = collect();

        // Students with unpaid prior-term carry-forward line
        $studentIds = $studentIds->merge(
            DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('payment_allocations', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
                ->whereNull('invoice_items.deleted_at')
                ->where('invoice_items.status', 'active')
                ->where(function ($q) use ($priorVoteheadId) {
                    $q->where('invoice_items.source', 'prior_term_carryforward');
                    if ($priorVoteheadId) {
                        $q->orWhere('invoice_items.votehead_id', $priorVoteheadId);
                    }
                })
                ->where('invoices.status', '!=', 'reversed')
                ->groupBy('invoices.student_id', 'invoice_items.id', 'invoice_items.amount', 'invoice_items.discount_amount')
                ->havingRaw('(COALESCE(invoice_items.amount,0) - COALESCE(invoice_items.discount_amount,0) - COALESCE(SUM(payment_allocations.amount),0)) > 0.01')
                ->pluck('invoices.student_id')
        );

        // Students with unpaid BBF line
        if ($bbfVoteheadId) {
            $studentIds = $studentIds->merge(
                DB::table('invoice_items')
                    ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->leftJoin('payment_allocations', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
                    ->whereNull('invoice_items.deleted_at')
                    ->where('invoice_items.status', 'active')
                    ->where('invoice_items.source', 'balance_brought_forward')
                    ->where('invoice_items.votehead_id', $bbfVoteheadId)
                    ->where('invoices.status', '!=', 'reversed')
                    ->groupBy('invoices.student_id', 'invoice_items.id', 'invoice_items.amount', 'invoice_items.discount_amount')
                    ->havingRaw('(COALESCE(invoice_items.amount,0) - COALESCE(invoice_items.discount_amount,0) - COALESCE(SUM(payment_allocations.amount),0)) > 0.01')
                    ->pluck('invoices.student_id')
            );
        }

        $students = Student::whereIn('id', $studentIds->filter()->unique()->values())->get();

        $this->info("Found {$students->count()} student(s) with carry-forward/BBF lines.");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        $reallocated = 0;
        foreach ($students as $student) {
            $result = $this->reallocateStudent($student, $dryRun);
            if ($result === 0) {
                $reallocated++;
            }
        }

        if (!$dryRun) {
            $this->info("Re-allocation complete for {$reallocated} student(s).");
        }

        return 0;
    }

    private function reallocateStudent(Student $student, bool $dryRun): int
    {
        $fromDate = $this->option('from-date');
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->where('reversed', false)
            ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
            ->when($fromDate, function ($q) use ($fromDate) {
                // payment_date is usually a date/datetime; treat from-date as inclusive
                $q->whereDate('payment_date', '>=', $fromDate);
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ;
        $payments = $paymentsQuery->get();

        if ($payments->isEmpty()) {
            return 1;
        }

        $hasAllocations = $payments->contains(fn ($p) => $p->allocations()->exists());
        if (!$hasAllocations) {
            return 1;
        }

        $allocationService = app(PaymentAllocationService::class);

        DB::transaction(function () use ($payments, $student, $allocationService, $dryRun) {
            $unpaidPriorTerm = (float) StudentBalanceService::getOutstandingPriorTermArrears($student);
            $unpaidBbf = (float) StudentBalanceService::getOutstandingBalanceBroughtForward($student);
            $hasUnpaidPriority = ($unpaidPriorTerm + $unpaidBbf) > 0.01;

            foreach ($payments as $payment) {
                $allocations = $payment->allocations()->get();
                if ($allocations->isEmpty()) {
                    continue;
                }

                // Only touch payments that are currently allocated to non-priority items
                // while the student still has an unpaid carried-forward/BBF balance.
                // This avoids rewriting receipts/payments that are already correctly prioritized.
                $hasNonPriorityAlloc = DB::table('payment_allocations')
                    ->join('invoice_items', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
                    ->leftJoin('voteheads', 'invoice_items.votehead_id', '=', 'voteheads.id')
                    ->where('payment_allocations.payment_id', $payment->id)
                    ->where(function ($q) {
                        $q->whereNotIn('invoice_items.source', ['prior_term_carryforward', 'balance_brought_forward'])
                            ->orWhereNull('invoice_items.source');
                    })
                    ->where(function ($q) {
                        $q->whereNotIn('voteheads.code', ['PRIOR_TERM_ARREARS', 'BAL_BF'])
                            ->orWhereNull('voteheads.code');
                    })
                    ->exists();

                if (!$hasUnpaidPriority || !$hasNonPriorityAlloc) {
                    continue;
                }

                if ($dryRun) {
                    $label = $student->admission_number ?: (string) $student->id;
                    $this->line("  {$label}: Would deallocate {$allocations->count()} from payment #{$payment->id} ({$payment->receipt_number})");
                    continue;
                }

                $payment->allocations()->delete();
                $payment->updateAllocationTotals();
            }

            if (!$dryRun) {
                Invoice::where('student_id', $student->id)
                    ->where('status', '!=', 'reversed')
                    ->get()
                    ->each(fn ($inv) => InvoiceService::recalc($inv));

                foreach ($payments as $payment) {
                    $allocationService->autoAllocate($payment, $student->id);
                }
            }
        });

        if (!$dryRun) {
            $this->line("  Re-allocated: {$student->admission_number} {$student->full_name}");
        }

        return 0;
    }
}


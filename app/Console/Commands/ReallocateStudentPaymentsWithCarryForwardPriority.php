<?php

namespace App\Console\Commands;

use App\Models\{Payment, Invoice, Student, InvoiceItem, Votehead};
use App\Services\{PaymentAllocationService, InvoiceService};
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
                            {--dry-run : Show what would be done without making changes}';

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
        $priorVotehead = Votehead::where('code', 'PRIOR_TERM_ARREARS')->first();
        $bbfVotehead = Votehead::where('code', 'BAL_BF')->first();

        $studentIds = collect();

        // Students with prior-term carry-forward line
        $studentIds = $studentIds->merge(
            InvoiceItem::where('source', 'prior_term_carryforward')
                ->where('status', 'active')
                ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'reversed'))
                ->with('invoice')
                ->get()
                ->pluck('invoice.student_id')
        );

        // Students with BBF line (optional)
        if ($bbfVotehead) {
            $studentIds = $studentIds->merge(
                InvoiceItem::where('votehead_id', $bbfVotehead->id)
                    ->where('source', 'balance_brought_forward')
                    ->where('status', 'active')
                    ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'reversed'))
                    ->with('invoice')
                    ->get()
                    ->pluck('invoice.student_id')
            );
        }

        // Students with prior-term votehead (fallback)
        if ($priorVotehead) {
            $studentIds = $studentIds->merge(
                InvoiceItem::where('votehead_id', $priorVotehead->id)
                    ->where('status', 'active')
                    ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'reversed'))
                    ->with('invoice')
                    ->get()
                    ->pluck('invoice.student_id')
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
        $payments = Payment::where('student_id', $student->id)
            ->where('reversed', false)
            ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return 1;
        }

        $hasAllocations = $payments->contains(fn ($p) => $p->allocations()->exists());
        if (!$hasAllocations) {
            return 1;
        }

        $allocationService = app(PaymentAllocationService::class);

        DB::transaction(function () use ($payments, $student, $allocationService, $dryRun) {
            foreach ($payments as $payment) {
                $allocations = $payment->allocations()->get();
                if ($allocations->isEmpty()) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  {$student->admission_number}: Would deallocate {$allocations->count()} from payment #{$payment->id} ({$payment->receipt_number})");
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


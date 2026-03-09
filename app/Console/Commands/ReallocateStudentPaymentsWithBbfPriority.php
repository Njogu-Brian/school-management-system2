<?php

namespace App\Console\Commands;

use App\Models\{Payment, PaymentAllocation, Invoice, Student};
use App\Services\{PaymentAllocationService, InvoiceService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-allocate a student's payments with BBF (Balance Brought Forward) priority.
 * Use when payments were allocated before BBF-first logic, leaving BBF unpaid.
 */
class ReallocateStudentPaymentsWithBbfPriority extends Command
{
    protected $signature = 'finance:reallocate-student-bbf
                            {student : Student ID or admission number (e.g. RKS463)}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Re-allocate student payments with BBF cleared first (fixes misallocations)';

    public function handle(): int
    {
        $identifier = $this->argument('student');
        $dryRun = $this->option('dry-run');

        $student = is_numeric($identifier)
            ? Student::find($identifier)
            : Student::where('admission_number', $identifier)->first();

        if (!$student) {
            $this->error("Student not found: {$identifier}");
            return 1;
        }

        $payments = Payment::where('student_id', $student->id)
            ->where('reversed', false)
            ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            $this->warn("No payments found for {$student->full_name} ({$student->admission_number}).");
            return 0;
        }

        $this->info("Found {$payments->count()} payment(s) for {$student->full_name} ({$student->admission_number}).");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        $allocationService = app(PaymentAllocationService::class);

        DB::transaction(function () use ($payments, $student, $allocationService, $dryRun) {
            foreach ($payments as $payment) {
                $allocations = $payment->allocations()->get();
                if ($allocations->isEmpty()) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  Would deallocate {$allocations->count()} allocation(s) from payment #{$payment->id} ({$payment->receipt_number})");
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
            $this->info('Re-allocation complete. BBF items were allocated first.');
        }

        return 0;
    }
}

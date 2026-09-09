<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Services\PaymentAllocationService;
use App\Services\StudentFeeLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove payment allocations that still point at reversed invoices, then
 * re-auto-allocate those payments onto active invoices.
 *
 * Fixes cases like RKS577 where Term 2 was reversed but KES 300 remained
 * allocated to a Term 2 line item while the ledger correctly showed it on Term 3.
 */
class RepairReversedInvoiceAllocations extends Command
{
    protected $signature = 'finance:repair-reversed-invoice-allocations
                            {student? : Student ID or admission number (omit to scan all)}
                            {--dry-run : Show affected allocations without changing anything}';

    protected $description = 'Move orphaned allocations off reversed invoices onto active invoices';

    public function handle(PaymentAllocationService $allocator, StudentFeeLedgerService $ledger): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $identifier = $this->argument('student');

        $studentId = null;
        if ($identifier) {
            $student = is_numeric($identifier)
                ? Student::withArchived()->find($identifier)
                : Student::withArchived()->where('admission_number', $identifier)->first();
            if (! $student) {
                $this->error("Student not found: {$identifier}");

                return 1;
            }
            $studentId = (int) $student->id;
            $this->info("Scanning {$student->admission_number} (id {$studentId})...");
        } else {
            $this->info('Scanning all students for allocations on reversed invoices...');
        }

        $query = PaymentAllocation::query()
            ->whereHas('invoiceItem.invoice', function ($q) use ($studentId) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('reversed_at')
                        ->orWhere('status', 'reversed');
                });
                if ($studentId) {
                    $q->where('student_id', $studentId);
                }
            })
            ->with(['payment', 'invoiceItem.invoice']);

        $orphans = $query->get();
        if ($orphans->isEmpty()) {
            $this->info('No orphaned allocations found.');

            return 0;
        }

        $this->table(
            ['Alloc#', 'Payment#', 'Amount', 'Invoice', 'Year', 'Term', 'Student'],
            $orphans->map(function (PaymentAllocation $a) {
                $inv = $a->invoiceItem?->invoice;

                return [
                    $a->id,
                    $a->payment_id,
                    number_format((float) $a->amount, 2),
                    $inv?->invoice_number ?? $inv?->id,
                    $inv?->year,
                    $inv?->term,
                    $inv?->student_id,
                ];
            })->all()
        );

        $paymentIds = $orphans->pluck('payment_id')->unique()->filter()->values();
        $this->warn("Found {$orphans->count()} orphan allocation(s) across {$paymentIds->count()} payment(s).");

        if ($dryRun) {
            $this->comment('Dry run — nothing changed. Re-run without --dry-run to repair.');

            return 0;
        }

        DB::transaction(function () use ($orphans, $paymentIds, $allocator, $ledger) {
            foreach ($orphans as $allocation) {
                $allocation->delete();
            }

            $touchedStudents = [];
            foreach ($paymentIds as $paymentId) {
                $payment = Payment::find($paymentId);
                if (! $payment || $payment->reversed || $payment->deleted_at) {
                    continue;
                }
                $payment->updateAllocationTotals();
                $allocator->autoAllocate($payment);
                if ($payment->student_id) {
                    $touchedStudents[(int) $payment->student_id] = true;
                }
            }

            foreach (array_keys($touchedStudents) as $sid) {
                $ledger->syncStudent($sid);
            }
        });

        $this->info('Repair complete. Ledger re-synced for affected students.');

        return 0;
    }
}

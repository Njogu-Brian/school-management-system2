<?php

namespace App\Console\Commands;

use App\Models\{Payment, PaymentAllocation, Student};
use Illuminate\Console\Command;

/**
 * Diagnose payments for a student - useful for debugging allocation issues.
 * Usage: php artisan finance:diagnose-student-payments RKS568
 */
class DiagnoseStudentPayments extends Command
{
    protected $signature = 'finance:diagnose-student-payments
                            {student_ref : Student admission number (e.g. RKS568) or student ID}
                            {--payments= : Comma-separated payment IDs to focus on}';

    protected $description = 'Diagnose payment and allocation details for a student';

    public function handle(): int
    {
        $ref = $this->argument('student_ref');
        $paymentIds = $this->option('payments');

        $student = is_numeric($ref)
            ? Student::find($ref)
            : Student::where('admission_number', $ref)->first();

        if (!$student) {
            $this->error("Student not found: {$ref}");
            return 1;
        }

        $this->info("Student: {$student->first_name} {$student->last_name} ({$student->admission_number})");
        $this->newLine();

        $query = Payment::where('student_id', $student->id)
            ->where('reversed', false)
            ->with(['allocations.invoiceItem.votehead', 'allocations.invoiceItem.invoice', 'paymentTransaction'])
            ->orderBy('payment_date')
            ->orderBy('id');

        if ($paymentIds) {
            $ids = array_map('trim', explode(',', $paymentIds));
            $query->whereIn('id', $ids);
        }

        $payments = $query->get();

        foreach ($payments as $p) {
            $this->line(str_repeat('-', 80));
            $this->line("Payment #{$p->id} | Receipt: {$p->receipt_number} | Amount: {$p->amount} | Allocated: " . ($p->allocated_amount ?? 0) . " | Unallocated: " . ($p->unallocated_amount ?? 0));
            $this->line("  Date: {$p->payment_date} | Method: {$p->payment_method} | Txn Code: {$p->transaction_code}");
            $this->line("  Source: " . ($p->payment_transaction_id ? "STK Push (transaction_id={$p->payment_transaction_id})" : ($p->mpesa_receipt_number ? "C2B/M-PESA" : "Manual/Other")));
            $this->line("  Created: {$p->created_at}");

            $allocs = $p->allocations;
            if ($allocs->isEmpty()) {
                $this->line("  Allocations: none");
            } else {
                $this->line("  Allocations:");
                foreach ($allocs as $a) {
                    $item = $a->invoiceItem;
                    $inv = $item?->invoice;
                    $votehead = $item?->votehead;
                    $name = $votehead?->name ?? 'Unknown';
                    $invNo = $inv?->invoice_number ?? '?';
                    $this->line("    - {$a->amount} -> INV {$invNo} / {$name}");
                }
            }
            $this->newLine();
        }

        $this->line(str_repeat('=', 80));
        $total = $payments->sum('amount');
        $allocated = $payments->sum(fn ($p) => (float) ($p->allocated_amount ?? 0));
        $unallocated = $total - $allocated;
        $this->info("Totals: Amount={$total} | Allocated={$allocated} | Unallocated={$unallocated}");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Console\Command;

/**
 * One-off fix for production:
 * 1. Recompute allocated_amount/unallocated_amount for all payments that have allocations (fixes "unallocated" when they are allocated, e.g. Esther Sharon).
 * 2. Set payment.invoice_id from first allocation when null (so invoice payment list can show them if needed).
 * 3. Link C2B transactions to payments where payment exists (trans_id matches transaction_code) but payment_id was never set (moves Confirmed/Auto Assigned to Collected).
 */
class SyncPaymentAllocationAndC2B extends Command
{
    protected $signature = 'finance:sync-payment-allocation-c2b
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Sync payment allocation totals, invoice_id, and C2B payment_id links';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        $paymentIds = PaymentAllocation::query()->pluck('payment_id')->unique()->filter()->values();
        $payments = Payment::whereIn('id', $paymentIds)->get();
        $updated = 0;
        $invoiceIdSet = 0;

        foreach ($payments as $payment) {
            $payment->refresh();
            $allocated = $payment->calculateAllocatedAmount();
            $unallocated = max(0, $payment->amount - $allocated);
            $changed = false;
            if ((float) $payment->allocated_amount !== (float) $allocated || (float) $payment->unallocated_amount !== (float) $unallocated) {
                if (!$dryRun) {
                    $payment->allocated_amount = $allocated;
                    $payment->unallocated_amount = $unallocated;
                    $payment->save();
                }
                $updated++;
                $changed = true;
            }
            if (!$payment->invoice_id) {
                $first = PaymentAllocation::where('payment_id', $payment->id)
                    ->with('invoiceItem')
                    ->first();
                if ($first && $first->invoiceItem && $first->invoiceItem->invoice_id) {
                    if (!$dryRun) {
                        $payment->update(['invoice_id' => $first->invoiceItem->invoice_id]);
                    }
                    $invoiceIdSet++;
                }
            }
        }

        $this->info("Payments with allocations: {$payments->count()}. Allocation totals updated: {$updated}. invoice_id set: {$invoiceIdSet}.");

        // C2B: set payment_id where payment exists (transaction_code = trans_id or like trans_id-%) but C2B.payment_id is null
        $c2bUpdated = 0;
        $c2bList = MpesaC2BTransaction::whereNull('payment_id')
            ->where('status', 'processed')
            ->where('is_duplicate', false)
            ->get();

        foreach ($c2bList as $c2b) {
            $ref = $c2b->trans_id;
            $payment = Payment::where('reversed', false)
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                        ->orWhere('transaction_code', 'like', $ref . '-%');
                })
                ->first();
            if ($payment) {
                if (!$dryRun) {
                    $c2b->update([
                        'payment_id' => $payment->id,
                        'allocated_amount' => $c2b->trans_amount,
                        'unallocated_amount' => 0,
                    ]);
                }
                $c2bUpdated++;
                $this->line("  C2B id={$c2b->id} trans_id={$ref} -> payment_id={$payment->id} ({$payment->receipt_number})");
            }
        }

        $this->info("C2B transactions linked to existing payment: {$c2bUpdated}.");

        if ($dryRun && ($updated + $invoiceIdSet + $c2bUpdated) > 0) {
            $this->comment('Run without --dry-run to apply changes.');
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnallocateSwimmingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swimming:unallocate-payments {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unallocate all swimming payments from invoices and reverse any collected receipts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Find all swimming payments
        $swimmingPayments = Payment::where('receipt_number', 'like', 'SWIM-%')
            ->where('reversed', false)
            ->with(['allocations.invoiceItem.invoice'])
            ->get();
        
        if ($swimmingPayments->isEmpty()) {
            $this->info('No swimming payments found.');
            return 0;
        }
        
        $this->info("Found {$swimmingPayments->count()} swimming payment(s)");
        
        $totalAllocations = 0;
        $affectedInvoices = collect();
        $reversedPayments = 0;
        
        foreach ($swimmingPayments as $payment) {
            $allocations = $payment->allocations;
            
            if ($allocations->isEmpty()) {
                $this->line("  Payment {$payment->receipt_number}: No allocations (already correct)");
                continue;
            }
            
            $this->warn("  Payment {$payment->receipt_number} ({$payment->amount}): {$allocations->count()} allocation(s) found");
            
            // Collect invoice IDs
            foreach ($allocations as $allocation) {
                if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                    $affectedInvoices->push($allocation->invoiceItem->invoice_id);
                }
                $totalAllocations++;
                
                if ($dryRun) {
                    $invoiceItem = $allocation->invoiceItem;
                    $this->line("    Would remove allocation: {$allocation->amount} from invoice item #{$invoiceItem->id} (Invoice #{$invoiceItem->invoice_id})");
                }
            }
            
            if (!$dryRun) {
                DB::transaction(function () use ($payment, &$reversedPayments, $affectedInvoices) {
                    // Delete all allocations
                    PaymentAllocation::where('payment_id', $payment->id)->delete();
                    
                    // Update payment allocation totals
                    $payment->updateAllocationTotals();
                    
                    // Mark payment as reversed since it should not be allocated to invoices
                    $payment->update([
                        'reversed' => true,
                        'reversed_by' => 1, // System user
                        'reversed_at' => now(),
                        'narration' => ($payment->narration ?? '') . ' (Reversed - Swimming payment should not allocate to invoices)',
                    ]);
                    
                    $reversedPayments++;
                });
            }
        }
        
        $uniqueInvoiceIds = $affectedInvoices->unique();
        
        if ($uniqueInvoiceIds->isNotEmpty()) {
            $this->info("\nAffected invoices: {$uniqueInvoiceIds->count()}");
            
            if (!$dryRun) {
                $this->info('Recalculating affected invoices...');
                foreach ($uniqueInvoiceIds as $invoiceId) {
                    $invoice = Invoice::find($invoiceId);
                    if ($invoice) {
                        InvoiceService::recalc($invoice);
                        $this->line("  Recalculated invoice #{$invoiceId}");
                    }
                }
            } else {
                foreach ($uniqueInvoiceIds as $invoiceId) {
                    $this->line("  Would recalculate invoice #{$invoiceId}");
                }
            }
        }
        
        if ($dryRun) {
            $this->warn("\nDRY RUN SUMMARY:");
            $this->line("  - Would remove {$totalAllocations} allocation(s)");
            $this->line("  - Would reverse {$swimmingPayments->count()} payment(s)");
            $this->line("  - Would recalculate {$uniqueInvoiceIds->count()} invoice(s)");
            $this->warn("\nRun without --dry-run to apply changes.");
        } else {
            $this->info("\nSUMMARY:");
            $this->line("  ✓ Removed {$totalAllocations} allocation(s)");
            $this->line("  ✓ Reversed {$reversedPayments} payment(s)");
            $this->line("  ✓ Recalculated {$uniqueInvoiceIds->count()} invoice(s)");
            
            Log::info('Swimming payments unallocated from invoices', [
                'allocations_removed' => $totalAllocations,
                'payments_reversed' => $reversedPayments,
                'invoices_recalculated' => $uniqueInvoiceIds->count(),
            ]);
        }
        
        return 0;
    }
}

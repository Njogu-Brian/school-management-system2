<?php

namespace App\Console\Commands;

use App\Models\{Payment, PaymentAllocation, InvoiceItem};
use App\Services\{PaymentAllocationService, InvoiceService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix allocation order for Eliana (RKS568) - Payment 1268 (2,800) came in first (08:14)
 * but 1267 (600) was processed first, leaving 1268 with 600 unallocated.
 * This swaps so 1268 gets fully allocated and 1267 holds the 600 as advance.
 */
class FixElianaPaymentAllocationOrder extends Command
{
    protected $signature = 'finance:fix-eliana-allocation-order
                            {--dry-run : Show what would be done}';

    protected $description = 'Fix Eliana payment allocation order (2,800 processed first, 600 as advance)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        $p1267 = Payment::find(1267); // 600, currently fully allocated
        $p1268 = Payment::find(1268); // 2,800, 2,200 allocated, 600 unallocated

        if (!$p1267 || !$p1268) {
            $this->error('Payments 1267 or 1268 not found.');
            return 1;
        }

        $alloc600 = $p1267->allocations()->where('amount', 600)->first();
        if (!$alloc600) {
            $this->error('Could not find 600 allocation on payment 1267.');
            return 1;
        }

        $invoiceItemId = $alloc600->invoice_item_id;
        $invoiceItem = InvoiceItem::find($invoiceItemId);
        if (!$invoiceItem) {
            $this->error('Invoice item not found.');
            return 1;
        }

        $this->info('Current: P1267 has 600 allocated to tuition; P1268 has 2,200 allocated, 600 unallocated.');
        $this->info('Fix: Move 600 allocation from P1267 to P1268, so P1268 is fully allocated and P1267 holds 600 as advance.');

        if (!$dryRun) {
            DB::transaction(function () use ($p1267, $p1268, $alloc600, $invoiceItemId) {
                // 1. Delete allocation from 1267
                $alloc600->delete();

                // 2. Create allocation from 1268 to same invoice item
                PaymentAllocation::create([
                    'payment_id' => 1268,
                    'invoice_item_id' => $invoiceItemId,
                    'amount' => 600,
                    'allocated_by' => 1, // system
                    'allocated_at' => now(),
                ]);

                // 3. Update totals and recalc invoice
                $p1267->updateAllocationTotals();
                $p1268->updateAllocationTotals();
                $invoice = $p1268->allocations()->first()?->invoiceItem?->invoice;
                if ($invoice) {
                    InvoiceService::recalc($invoice);
                }
            });
            $this->info('Done. P1268 should now show 2,800 allocated; P1267 should show 600 unallocated (advance).');
        }

        return 0;
    }
}

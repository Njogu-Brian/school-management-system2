<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentAllocation;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\PostingDiff;
use App\Models\OptionalFee;
use App\Models\FeePostingRun;

class CleanupAllInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:cleanup-all 
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all invoices (pending and active), invoice items, payment allocations, credit/debit notes, posting diffs, posting runs, and optional fee assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL invoices, invoice items, payment allocations, credit/debit notes, posting diffs, posting runs, and optional fee assignments. Are you absolutely sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            if (!$this->confirm('This action cannot be undone. Type "yes" to confirm:')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting cleanup...');
        
        try {
            DB::beginTransaction();
            
            // Count records before deletion
            $invoiceCount = Invoice::count();
            $itemCount = InvoiceItem::count();
            $allocationCount = PaymentAllocation::count();
            $creditNoteCount = CreditNote::count();
            $debitNoteCount = DebitNote::count();
            $postingDiffCount = PostingDiff::count();
            $postingRunCount = FeePostingRun::count();
            $optionalFeeCount = OptionalFee::count();
            
            $this->info("Found:");
            $this->line("  - Invoices: {$invoiceCount}");
            $this->line("  - Invoice Items: {$itemCount}");
            $this->line("  - Payment Allocations: {$allocationCount}");
            $this->line("  - Credit Notes: {$creditNoteCount}");
            $this->line("  - Debit Notes: {$debitNoteCount}");
            $this->line("  - Posting Diffs: {$postingDiffCount}");
            $this->line("  - Posting Runs: {$postingRunCount}");
            $this->line("  - Optional Fees: {$optionalFeeCount}");
            
            // Delete in order to respect foreign key constraints
            
            // 1. Delete payment allocations (references invoice_items)
            $this->info('Deleting payment allocations...');
            PaymentAllocation::query()->delete();
            $this->info('✓ Payment allocations deleted');
            
            // 2. Delete credit notes (references invoice_id and invoice_item_id)
            $this->info('Deleting credit notes...');
            CreditNote::query()->delete();
            $this->info('✓ Credit notes deleted');
            
            // 3. Delete debit notes (references invoice_id and invoice_item_id)
            $this->info('Deleting debit notes...');
            DebitNote::query()->delete();
            $this->info('✓ Debit notes deleted');
            
            // 4. Delete invoice items (references invoice_id)
            $this->info('Deleting invoice items...');
            InvoiceItem::query()->delete();
            $this->info('✓ Invoice items deleted');
            
            // 5. Delete posting diffs (references posting_run_id, but related to invoices)
            $this->info('Deleting posting diffs...');
            PostingDiff::query()->delete();
            $this->info('✓ Posting diffs deleted');
            
            // 6. Delete fee posting runs (references invoices via posting_run_id)
            $this->info('Deleting fee posting runs...');
            FeePostingRun::query()->delete();
            $this->info('✓ Fee posting runs deleted');
            
            // 7. Delete optional fees
            $this->info('Deleting optional fee assignments...');
            OptionalFee::query()->delete();
            $this->info('✓ Optional fees deleted');
            
            // 8. Delete invoices (should be last as other tables reference it)
            $this->info('Deleting invoices...');
            Invoice::query()->delete();
            $this->info('✓ Invoices deleted');
            
            DB::commit();
            
            $this->newLine();
            $this->info('✓ Cleanup completed successfully!');
            $this->info("Deleted {$invoiceCount} invoices, {$itemCount} invoice items, {$allocationCount} payment allocations, {$creditNoteCount} credit notes, {$debitNoteCount} debit notes, {$postingDiffCount} posting diffs, {$postingRunCount} posting runs, and {$optionalFeeCount} optional fee assignments.");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during cleanup: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

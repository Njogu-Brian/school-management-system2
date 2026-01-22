<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;

class CleanupSwimmingAttendanceInvoiceItems extends Command
{
    protected $signature = 'cleanup:swimming-attendance-invoice-items {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Remove invoice items with source=swimming_attendance (daily attendance charges should not be in invoices)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Finding invoice items with source=swimming_attendance...');
        $this->newLine();
        $this->info('⚠️  IMPORTANT: This command ONLY deletes invoice items.');
        $this->info('   Swimming wallet balances and ledger entries are NOT affected.');
        $this->info('   Wallet credits/debits remain intact.');
        $this->newLine();
        
        $items = InvoiceItem::where('source', 'swimming_attendance')
            ->with(['invoice.student', 'votehead'])
            ->get();
        
        $this->info("Found {$items->count()} invoice items to remove");
        
        if ($items->isEmpty()) {
            $this->info('No items to clean up.');
            return 0;
        }
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No items will be deleted');
            $this->newLine();
            
            foreach ($items->take(10) as $item) {
                $student = $item->invoice->student;
                $votehead = $item->votehead;
                $this->line("Would delete: {$student->first_name} {$student->last_name} - {$votehead->name} - {$item->amount} (Invoice #{$item->invoice_id})");
            }
            
            if ($items->count() > 10) {
                $this->line("... and " . ($items->count() - 10) . " more items");
            }
            
            return 0;
        }
        
        $this->warn('This will permanently delete these invoice items. Are you sure?');
        if (!$this->confirm('Continue?')) {
            $this->info('Cancelled.');
            return 0;
        }
        
        $deleted = 0;
        $invoiceIds = collect();
        
        foreach ($items as $item) {
            $invoiceIds->push($item->invoice_id);
            $item->delete();
            $deleted++;
        }
        
        // Recalculate affected invoices
        $uniqueInvoiceIds = $invoiceIds->unique();
        foreach ($uniqueInvoiceIds as $invoiceId) {
            $invoice = \App\Models\Invoice::find($invoiceId);
            if ($invoice) {
                InvoiceService::recalc($invoice);
            }
        }
        
        $this->info("Deleted {$deleted} invoice items");
        $this->info("Recalculated {$uniqueInvoiceIds->count()} invoices");
        
        return 0;
    }
}

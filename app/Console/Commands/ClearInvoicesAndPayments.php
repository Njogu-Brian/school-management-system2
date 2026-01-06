<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\BankStatementTransaction;

class ClearInvoicesAndPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:clear-invoices-payments 
                            {--force : Force the operation without confirmation}
                            {--reset-ids : Reset auto-increment IDs after deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all invoices, payments, bank statements, and related records. Preserves legacy import records and balance brought forward.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This will DELETE ALL invoices, payments, payment allocations, credit notes, debit notes, invoice items, and bank statement transactions.');
            $this->warn('⚠️  Legacy import records and balance brought forward will be PRESERVED.');
            $this->warn('⚠️  This action CANNOT be undone!');
            
            if (!$this->confirm('Are you absolutely sure you want to proceed?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            if (!$this->confirm('Type "yes" to confirm deletion:', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting cleanup...');
        
        try {
            DB::beginTransaction();
            
            // Count records before deletion
            $paymentCount = Payment::count();
            $allocationCount = PaymentAllocation::count();
            $creditNoteCount = CreditNote::count();
            $debitNoteCount = DebitNote::count();
            $invoiceItemCount = InvoiceItem::count();
            $invoiceCount = Invoice::count();
            $bankStatementCount = BankStatementTransaction::count();
            
            $this->info('Found records:');
            $this->line("  - Payments: {$paymentCount}");
            $this->line("  - Payment Allocations: {$allocationCount}");
            $this->line("  - Credit Notes: {$creditNoteCount}");
            $this->line("  - Debit Notes: {$debitNoteCount}");
            $this->line("  - Invoice Items: {$invoiceItemCount}");
            $this->line("  - Invoices: {$invoiceCount}");
            $this->line("  - Bank Statement Transactions: {$bankStatementCount}");
            
            $this->info('');
            $this->info('Note: Legacy import records and balance brought forward will be preserved.');
            
            if ($paymentCount === 0 && $invoiceCount === 0 && $bankStatementCount === 0) {
                $this->info('No records to delete.');
                DB::rollBack();
                return 0;
            }
            
            // Delete in order to respect foreign key constraints
            
            // 1. Delete bank statement transactions first (they reference payments)
            // This must be done before deleting payments to avoid foreign key issues
            $this->info('Deleting bank statement transactions...');
            
            // Get unique statement file paths before deletion for file cleanup
            $statementFilePaths = BankStatementTransaction::whereNotNull('statement_file_path')
                ->distinct()
                ->pluck('statement_file_path')
                ->filter()
                ->unique()
                ->toArray();
            
            $deletedBankStatements = BankStatementTransaction::query()->delete();
            $this->info("✓ Deleted {$deletedBankStatements} bank statement transactions");
            
            // Delete bank statement PDF files from storage
            if (!empty($statementFilePaths)) {
                $this->info('Deleting bank statement PDF files...');
                $deletedFiles = 0;
                foreach ($statementFilePaths as $filePath) {
                    try {
                        if (Storage::disk('private')->exists($filePath)) {
                            Storage::disk('private')->delete($filePath);
                            $deletedFiles++;
                        }
                    } catch (\Exception $e) {
                        $this->warn("  ⚠ Could not delete file {$filePath}: " . $e->getMessage());
                    }
                }
                $this->info("✓ Deleted {$deletedFiles} bank statement PDF files");
            }
            
            // 2. Delete payment allocations (references invoice_items and payments)
            $this->info('Deleting payment allocations...');
            $deletedAllocations = PaymentAllocation::query()->delete();
            $this->info("✓ Deleted {$deletedAllocations} payment allocations");
            
            // 3. Delete payments (references invoices)
            $this->info('Deleting payments...');
            $deletedPayments = Payment::query()->delete();
            $this->info("✓ Deleted {$deletedPayments} payments");
            
            // 4. Delete credit notes (references invoice_id and invoice_item_id)
            $this->info('Deleting credit notes...');
            $deletedCredits = CreditNote::query()->delete();
            $this->info("✓ Deleted {$deletedCredits} credit notes");
            
            // 5. Delete debit notes (references invoice_id and invoice_item_id)
            $this->info('Deleting debit notes...');
            $deletedDebits = DebitNote::query()->delete();
            $this->info("✓ Deleted {$deletedDebits} debit notes");
            
            // 6. Delete invoice items (references invoice_id)
            $this->info('Deleting invoice items...');
            $deletedItems = InvoiceItem::query()->delete();
            $this->info("✓ Deleted {$deletedItems} invoice items");
            
            // 7. Delete invoices (should be last as other tables reference it)
            $this->info('Deleting invoices...');
            $deletedInvoices = Invoice::query()->delete();
            $this->info("✓ Deleted {$deletedInvoices} invoices");
            
            DB::commit();
            
            $this->newLine();
            $this->info('✓ Cleanup completed successfully!');
            
            // Reset auto-increment IDs if requested
            if ($this->option('reset-ids')) {
                $this->info('Resetting auto-increment IDs...');
                
                $tablesToReset = [
                    'bank_statement_transactions',
                    'payment_allocations',
                    'payments',
                    'credit_notes',
                    'debit_notes',
                    'invoice_items',
                    'invoices',
                ];
                
                foreach ($tablesToReset as $table) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                        $this->line("  ✓ Reset auto-increment for {$table}");
                    } catch (\Exception $e) {
                        $this->warn("  ⚠ Could not reset auto-increment for {$table}: " . $e->getMessage());
                    }
                }
                
                $this->info('✓ Auto-increment IDs reset');
            }
            
            $this->newLine();
            $this->info('Summary:');
            $this->line("  - Deleted {$deletedBankStatements} bank statement transactions");
            $this->line("  - Deleted {$deletedPayments} payments");
            $this->line("  - Deleted {$deletedAllocations} payment allocations");
            $this->line("  - Deleted {$deletedCredits} credit notes");
            $this->line("  - Deleted {$deletedDebits} debit notes");
            $this->line("  - Deleted {$deletedItems} invoice items");
            $this->line("  - Deleted {$deletedInvoices} invoices");
            $this->newLine();
            $this->info('✓ Legacy import records and balance brought forward have been preserved.');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('Error during cleanup: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            return 1;
        }
    }
}


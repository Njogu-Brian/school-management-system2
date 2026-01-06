<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:reset 
                            {--force : Force the operation without confirmation}
                            {--keep-dropoff : Keep drop-off points (only delete assignments)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DANGER: Delete all invoices, payments, transport fees, optional fees, credit notes, and fee postings. Resets auto-increment IDs. Preserves bank statements, student assignments, legacy imports, and balance brought forward.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This will PERMANENTLY DELETE all financial data!');
            $this->warn('This includes:');
            $this->line('  - All invoices and invoice items');
            $this->line('  - All payments and payment allocations');
            $this->line('  - All transport fees and imports');
            $this->line('  - All optional fees and imports');
            $this->line('  - All credit/debit notes');
            $this->line('  - All fee posting runs and diffs');
            $this->line('  - Auto-increment IDs will be reset');
            $this->newLine();
            $this->info('Note: The following will be RETAINED:');
            $this->line('  ✓ Bank statement transactions and PDF files');
            $this->line('  ✓ Student transport assignments');
            $this->line('  ✓ Legacy imports and balance brought forward data');
            $this->newLine();

            if (!$this->confirm('Are you ABSOLUTELY SURE you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            if (!$this->confirm('This cannot be undone. Type YES to confirm:', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $confirmation = $this->ask('Type "DELETE ALL FINANCIAL DATA" to proceed:');
            if ($confirmation !== 'DELETE ALL FINANCIAL DATA') {
                $this->error('Confirmation text does not match. Operation cancelled.');
                return 1;
            }
        }

        $this->info('Starting financial data reset...');
        $this->newLine();

        try {
            // Disable foreign key checks temporarily
            // Note: TRUNCATE auto-commits, so we don't use transactions
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Keep bank statement transactions - NOT DELETED
            $this->info('Keeping bank statement transactions...');
            $count = Schema::hasTable('bank_statement_transactions') 
                ? DB::table('bank_statement_transactions')->count() 
                : 0;
            $this->line("  ✓ Retained {$count} bank statement transactions");

            // 2. Delete payment allocations (they reference payments and invoice items)
            $this->info('Deleting payment allocations...');
            $count = DB::table('payment_allocations')->count();
            DB::table('payment_allocations')->truncate();
            $this->line("  ✓ Deleted {$count} payment allocations");

            // 3. Delete payments
            $this->info('Deleting payments...');
            $count = DB::table('payments')->count();
            DB::table('payments')->truncate();
            $this->line("  ✓ Deleted {$count} payments");

            // 5. Delete credit notes
            $this->info('Deleting credit notes...');
            $count = DB::table('credit_notes')->count();
            DB::table('credit_notes')->truncate();
            $this->line("  ✓ Deleted {$count} credit notes");

            // 6. Delete debit notes
            $this->info('Deleting debit notes...');
            $count = DB::table('debit_notes')->count();
            DB::table('debit_notes')->truncate();
            $this->line("  ✓ Deleted {$count} debit notes");

            // 7. Delete fee concessions
            $this->info('Deleting fee concessions...');
            $count = DB::table('fee_concessions')->count();
            DB::table('fee_concessions')->truncate();
            $this->line("  ✓ Deleted {$count} fee concessions");

            // 8. Delete invoice items
            $this->info('Deleting invoice items...');
            $count = DB::table('invoice_items')->count();
            DB::table('invoice_items')->truncate();
            $this->line("  ✓ Deleted {$count} invoice items");

            // 9. Delete invoices
            $this->info('Deleting invoices...');
            $count = DB::table('invoices')->count();
            DB::table('invoices')->truncate();
            $this->line("  ✓ Deleted {$count} invoices");

            // 10. Delete optional fee imports
            if (Schema::hasTable('optional_fee_imports')) {
                $this->info('Deleting optional fee imports...');
                $count = DB::table('optional_fee_imports')->count();
                DB::table('optional_fee_imports')->truncate();
                $this->line("  ✓ Deleted {$count} optional fee imports");
            }

            // 11. Delete optional fees
            $this->info('Deleting optional fees...');
            $count = DB::table('optional_fees')->count();
            DB::table('optional_fees')->truncate();
            $this->line("  ✓ Deleted {$count} optional fees");

            // 12. Keep legacy import data (lines, terms, batches) and balance brought forward - NOT DELETED
            $this->info('Keeping legacy imports and balance brought forward data...');
            $this->line("  ✓ Legacy statement lines retained");
            $this->line("  ✓ Legacy statement terms retained");
            $this->line("  ✓ Legacy import batches retained");
            $this->line("  ✓ Balance brought forward data retained");

            // 13. Delete transport fee revisions
            $this->info('Deleting transport fee revisions...');
            $count = DB::table('transport_fee_revisions')->count();
            DB::table('transport_fee_revisions')->truncate();
            $this->line("  ✓ Deleted {$count} transport fee revisions");

            // 14. Delete transport fees
            $this->info('Deleting transport fees...');
            $count = DB::table('transport_fees')->count();
            DB::table('transport_fees')->truncate();
            $this->line("  ✓ Deleted {$count} transport fees");

            // 15. Delete transport fee imports
            if (Schema::hasTable('transport_fee_imports')) {
                $this->info('Deleting transport fee imports...');
                $count = DB::table('transport_fee_imports')->count();
                DB::table('transport_fee_imports')->truncate();
                $this->line("  ✓ Deleted {$count} transport fee imports");
            }

            // 16. Keep student assignments - NOT DELETED
            $this->info('Keeping student transport assignments...');
            $count = Schema::hasTable('student_assignments') 
                ? DB::table('student_assignments')->count() 
                : 0;
            $this->line("  ✓ Retained {$count} student assignments");

            // 17. Delete fee posting runs and diffs (pending postings)
            if (Schema::hasTable('posting_diffs')) {
                $this->info('Deleting posting diffs...');
                $count = DB::table('posting_diffs')->count();
                DB::table('posting_diffs')->truncate();
                $this->line("  ✓ Deleted {$count} posting diffs");
            }

            if (Schema::hasTable('fee_posting_runs')) {
                $this->info('Deleting fee posting runs...');
                $count = DB::table('fee_posting_runs')->count();
                DB::table('fee_posting_runs')->truncate();
                $this->line("  ✓ Deleted {$count} fee posting runs");
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->newLine();
            $this->info('✅ Financial data reset completed successfully!');
            $this->info('All tables have been truncated and auto-increment IDs have been reset.');

            return 0;
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('❌ Error during reset: ' . $e->getMessage());
            $this->error('Some data may have been deleted before the error occurred.');
            return 1;
        }
    }
}

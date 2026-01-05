<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    protected $description = 'DANGER: Delete all invoices, payments, imports, transport fees, and related data. Resets auto-increment IDs.';

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
            $this->line('  - All drop-off points and assignments');
            $this->line('  - All credit/debit notes');
            $this->line('  - Auto-increment IDs will be reset');
            $this->newLine();
            $this->info('Note: Legacy imports and balance brought forward data will be RETAINED');
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

        DB::beginTransaction();
        try {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Delete payment allocations first (they reference payments and invoice items)
            $this->info('Deleting payment allocations...');
            $count = DB::table('payment_allocations')->count();
            DB::table('payment_allocations')->truncate();
            $this->line("  ✓ Deleted {$count} payment allocations");

            // 2. Delete payments
            $this->info('Deleting payments...');
            $count = DB::table('payments')->count();
            DB::table('payments')->truncate();
            $this->line("  ✓ Deleted {$count} payments");

            // 3. Delete credit notes
            $this->info('Deleting credit notes...');
            $count = DB::table('credit_notes')->count();
            DB::table('credit_notes')->truncate();
            $this->line("  ✓ Deleted {$count} credit notes");

            // 4. Delete debit notes
            $this->info('Deleting debit notes...');
            $count = DB::table('debit_notes')->count();
            DB::table('debit_notes')->truncate();
            $this->line("  ✓ Deleted {$count} debit notes");

            // 5. Delete fee concessions
            $this->info('Deleting fee concessions...');
            $count = DB::table('fee_concessions')->count();
            DB::table('fee_concessions')->truncate();
            $this->line("  ✓ Deleted {$count} fee concessions");

            // 6. Delete invoice items
            $this->info('Deleting invoice items...');
            $count = DB::table('invoice_items')->count();
            DB::table('invoice_items')->truncate();
            $this->line("  ✓ Deleted {$count} invoice items");

            // 7. Delete invoices
            $this->info('Deleting invoices...');
            $count = DB::table('invoices')->count();
            DB::table('invoices')->truncate();
            $this->line("  ✓ Deleted {$count} invoices");

            // 8. Keep legacy import data (lines, terms, batches) and balance brought forward - NOT DELETED
            $this->info('Keeping legacy imports and balance brought forward data...');
            $this->line("  ✓ Legacy statement lines retained");
            $this->line("  ✓ Legacy statement terms retained");
            $this->line("  ✓ Legacy import batches retained");
            $this->line("  ✓ Balance brought forward data retained");

            // 9. Delete transport fee revisions
            $this->info('Deleting transport fee revisions...');
            $count = DB::table('transport_fee_revisions')->count();
            DB::table('transport_fee_revisions')->truncate();
            $this->line("  ✓ Deleted {$count} transport fee revisions");

            // 10. Delete transport fees
            $this->info('Deleting transport fees...');
            $count = DB::table('transport_fees')->count();
            DB::table('transport_fees')->truncate();
            $this->line("  ✓ Deleted {$count} transport fees");

            // 11. Delete transport fee imports
            if (Schema::hasTable('transport_fee_imports')) {
                $this->info('Deleting transport fee imports...');
                $count = DB::table('transport_fee_imports')->count();
                DB::table('transport_fee_imports')->truncate();
                $this->line("  ✓ Deleted {$count} transport fee imports");
            }

            // 12. Delete student assignments (they reference drop-off points)
            $this->info('Deleting student transport assignments...');
            $count = DB::table('student_assignments')->count();
            DB::table('student_assignments')->truncate();
            $this->line("  ✓ Deleted {$count} student assignments");

            // 13. Clear student drop-off point info
            $this->info('Clearing student drop-off point data...');
            $count = DB::table('students')
                ->whereNotNull('drop_off_point_id')
                ->orWhereNotNull('drop_off_point_other')
                ->update([
                    'drop_off_point_id' => null,
                    'drop_off_point_other' => null,
                ]);
            $this->line("  ✓ Cleared drop-off point data for {$count} students");

            // 14. Delete drop-off points (unless --keep-dropoff flag is used)
            if (!$this->option('keep-dropoff')) {
                $this->info('Deleting drop-off points...');
                $count = DB::table('drop_off_points')->count();
                DB::table('drop_off_points')->truncate();
                $this->line("  ✓ Deleted {$count} drop-off points");
            } else {
                $this->info('Keeping drop-off points (--keep-dropoff flag used)');
            }

            // 15. Delete fee posting runs and diffs
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

            DB::commit();

            $this->newLine();
            $this->info('✅ Financial data reset completed successfully!');
            $this->info('All tables have been truncated and auto-increment IDs have been reset.');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('❌ Error during reset: ' . $e->getMessage());
            $this->error('Transaction rolled back. No data was deleted.');
            return 1;
        }
    }
}

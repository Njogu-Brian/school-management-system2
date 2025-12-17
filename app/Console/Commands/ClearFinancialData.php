<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:clear-all 
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all financial records except voteheads and reset auto-increment IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will DELETE ALL financial data (invoices, payments, credit notes, debit notes, fee postings, optional fees, fee structures). Voteheads will be preserved. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting financial data cleanup...');
        
        try {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // List of tables to clear (in order to respect foreign keys)
            $tablesToClear = [
                'payment_allocations',
                'payments',
                'credit_notes',
                'debit_notes',
                'invoice_items',
                'invoices',
                'optional_fees',
                'fee_posting_runs',
                'posting_diffs',
                'fee_structures',
                'fee_charges',
                'fee_concessions',
                'fee_reminders',
                'fee_payment_plans',
                'payment_methods',
                'bank_accounts',
            ];
            
            $this->info('Deleting records from financial tables...');
            
            foreach ($tablesToClear as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line("  ✓ Cleared {$table} ({$count} records)");
                } else {
                    $this->warn("  ⚠ Table {$table} does not exist, skipping...");
                }
            }
            
            // Reset auto-increment IDs
            $this->info('Resetting auto-increment IDs...');
            
            $tablesToReset = [
                'payment_allocations',
                'payments',
                'credit_notes',
                'debit_notes',
                'invoice_items',
                'invoices',
                'optional_fees',
                'fee_posting_runs',
                'posting_diffs',
                'fee_structures',
                'fee_charges',
                'fee_concessions',
                'fee_reminders',
                'fee_payment_plans',
                'payment_methods',
                'bank_accounts',
            ];
            
            foreach ($tablesToReset as $table) {
                if (Schema::hasTable($table)) {
                    // Reset to 1
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                    $this->line("  ✓ Reset auto-increment for {$table}");
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->info('');
            $this->info('✓ Financial data cleared successfully!');
            $this->info('✓ Auto-increment IDs reset');
            $this->info('✓ Voteheads preserved');
            $this->info('');
            $this->info('You can now start fresh with financial records.');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->error('Error clearing financial data: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            return 1;
        }
    }
}

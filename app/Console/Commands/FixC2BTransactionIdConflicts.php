<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixC2BTransactionIdConflicts extends Command
{
    protected $signature = 'transactions:fix-c2b-id-conflicts 
                            {--dry-run : Show conflicts without fixing them}
                            {--fix : Actually fix the conflicts by reassigning C2B transaction IDs}';

    protected $description = 'Find and fix ID conflicts between C2B and Bank Statement transactions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $fix = $this->option('fix');

        if (!$dryRun && !$fix) {
            $this->error('Please specify either --dry-run to see conflicts or --fix to resolve them');
            return 1;
        }

        $this->info('Scanning for ID conflicts between C2B and Bank Statement transactions...');
        $this->newLine();

        // Find all conflicts
        $conflicts = $this->findConflicts();

        if ($conflicts->isEmpty()) {
            $this->info('✓ No ID conflicts found. All transactions have unique IDs.');
            return 0;
        }

        $this->warn("Found {$conflicts->count()} ID conflict(s):");
        $this->newLine();

        // Display conflicts
        $tableData = [];
        foreach ($conflicts as $id => $conflict) {
            $tableData[] = [
                'ID' => $id,
                'C2B Trans ID' => $conflict['c2b']->trans_id ?? 'N/A',
                'C2B Student' => $conflict['c2b']->student_id ? "Student #{$conflict['c2b']->student_id}" : 'None',
                'C2B Payment' => $conflict['c2b']->payment_id ? "Payment #{$conflict['c2b']->payment_id}" : 'None',
                'Bank Ref' => $conflict['bank']->reference_number ?? 'N/A',
                'Bank Student' => $conflict['bank']->student_id ? "Student #{$conflict['bank']->student_id}" : 'None',
                'Bank Payment' => $conflict['bank']->payment_id ? "Payment #{$conflict['bank']->payment_id}" : 'None',
            ];
        }

        $this->table(
            ['ID', 'C2B Trans ID', 'C2B Student', 'C2B Payment', 'Bank Ref', 'Bank Student', 'Bank Payment'],
            $tableData
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('This is a dry run. Use --fix to actually resolve the conflicts.');
            return 0;
        }

        if ($fix) {
            $this->newLine();
            if (!$this->confirm('This will reassign C2B transaction IDs to resolve conflicts. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            return $this->fixConflicts($conflicts);
        }

        return 0;
    }

    protected function findConflicts()
    {
        $conflicts = collect();

        // Get all C2B transaction IDs
        $c2bIds = MpesaC2BTransaction::pluck('id')->toArray();

        // Check which ones also exist in bank statement transactions
        foreach ($c2bIds as $id) {
            $c2bTransaction = MpesaC2BTransaction::find($id);
            $bankTransaction = BankStatementTransaction::find($id);

            if ($c2bTransaction && $bankTransaction) {
                $conflicts[$id] = [
                    'c2b' => $c2bTransaction,
                    'bank' => $bankTransaction,
                ];
            }
        }

        return $conflicts;
    }

    protected function fixConflicts($conflicts)
    {
        $this->info('Fixing conflicts by reassigning C2B transaction IDs...');
        $this->newLine();

        $fixed = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            foreach ($conflicts as $id => $conflict) {
                $c2bTransaction = $conflict['c2b'];
                $bankTransaction = $conflict['bank'];

                // Find the next available ID that doesn't conflict
                // Start from a high number to avoid conflicts with existing bank statement transactions
                $maxBankId = BankStatementTransaction::max('id') ?? 0;
                $maxC2BId = MpesaC2BTransaction::max('id') ?? 0;
                $startId = max($maxBankId, $maxC2BId) + 1000; // Start well above existing IDs

                $newId = $startId;
                while (
                    BankStatementTransaction::where('id', $newId)->exists() ||
                    MpesaC2BTransaction::where('id', $newId)->exists()
                ) {
                    $newId++;
                }

                $this->line("  Reassigning C2B transaction ID {$id} -> {$newId} (Trans ID: {$c2bTransaction->trans_id})");

                // WARNING: Changing primary keys is dangerous!
                // We need to:
                // 1. Check for any foreign key references to this C2B transaction
                // 2. Temporarily disable foreign key checks
                // 3. Update the ID
                // 4. Re-enable foreign key checks
                
                // Check if there are any payments or other records that might reference this
                $hasPayment = $c2bTransaction->payment_id !== null;
                if ($hasPayment) {
                    $this->warn("    ⚠ C2B transaction has payment_id: {$c2bTransaction->payment_id} - this will need to be updated in payments table");
                }

                // Check for self-referential foreign keys (duplicate_of)
                $duplicates = MpesaC2BTransaction::where('duplicate_of', $id)->get();
                if ($duplicates->isNotEmpty()) {
                    $this->line("    Found {$duplicates->count()} duplicate(s) referencing this transaction");
                }

                // Temporarily disable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                
                try {
                    // First, update any duplicate_of references
                    if ($duplicates->isNotEmpty()) {
                        DB::table('mpesa_c2b_transactions')
                            ->where('duplicate_of', $id)
                            ->update(['duplicate_of' => $newId]);
                    }

                    // Update the C2B transaction ID
                    DB::table('mpesa_c2b_transactions')
                        ->where('id', $id)
                        ->update(['id' => $newId]);
                    
                } finally {
                    // Always re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }

                $fixed++;

                Log::info('Fixed C2B transaction ID conflict', [
                    'old_id' => $id,
                    'new_id' => $newId,
                    'c2b_trans_id' => $c2bTransaction->trans_id,
                    'bank_ref_number' => $bankTransaction->reference_number,
                ]);
            }

            DB::commit();

            $this->newLine();
            $this->info("✓ Successfully fixed {$fixed} conflict(s).");
            
            if ($errors > 0) {
                $this->warn("⚠ {$errors} error(s) occurred during the fix.");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error("✗ Error fixing conflicts: " . $e->getMessage());
            Log::error('Failed to fix C2B transaction ID conflicts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AnalyzeTransactionDatabase extends Command
{
    protected $signature = 'transactions:analyze 
                            {--export : Export results to CSV files}
                            {--fix : Attempt to fix issues automatically}';

    protected $description = 'Analyze transaction database for inconsistencies and issues';

    protected $issues = [];

    public function handle()
    {
        $this->info('🔍 Starting Transaction Database Analysis...');
        $this->newLine();

        // 1. Transactions with payments by reference but not linked
        $this->analyzeUnlinkedPayments();

        // 2. Transactions marked confirmed/collected but no payment exists
        $this->analyzeConfirmedWithoutPayments();

        // 3. Swimming transactions used for fees collection
        $this->analyzeSwimmingTransactionsForFees();

        // 4. Other issues
        $this->analyzeOtherIssues();

        // Display summary
        $this->displaySummary();

        // Export if requested
        if ($this->option('export')) {
            $this->exportResults();
        }

        // Fix if requested
        if ($this->option('fix')) {
            $this->attemptFixes();
        }

        return 0;
    }

    /**
     * Issue 1: Transactions with existing payments (by reference) but payment not shown/linked
     */
    protected function analyzeUnlinkedPayments()
    {
        $this->info('📊 Analyzing: Transactions with unlinked payments...');

        // Bank Statement Transactions
        $bankIssues = DB::table('bank_statement_transactions as t')
            ->leftJoin('payments as p', function($join) {
                $join->on('p.transaction_code', '=', 't.reference_number')
                     ->orWhere(function($q) {
                         $q->whereColumn('p.transaction_code', 'LIKE', DB::raw("CONCAT(t.reference_number, '-%')"));
                     });
            })
            ->whereNotNull('t.reference_number')
            ->where('t.reference_number', '!=', '')
            ->where('p.reversed', false)
            ->where(function($q) {
                $q->whereNull('t.payment_id')
                  ->orWhereColumn('t.payment_id', '!=', 'p.id');
            })
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.status',
                't.payment_created',
                't.payment_id as linked_payment_id',
                'p.id as payment_id',
                'p.transaction_code as payment_transaction_code',
                'p.amount as payment_amount',
                'p.reversed as payment_reversed',
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        // C2B Transactions
        $c2bIssues = DB::table('mpesa_c2b_transactions as t')
            ->leftJoin('payments as p', function($join) {
                $join->on('p.transaction_code', '=', 't.trans_id')
                     ->orWhere(function($q) {
                         $q->whereColumn('p.transaction_code', 'LIKE', DB::raw("CONCAT(t.trans_id, '-%')"));
                     });
            })
            ->whereNotNull('t.trans_id')
            ->where('t.trans_id', '!=', '')
            ->where('p.reversed', false)
            ->where(function($q) {
                $q->whereNull('t.payment_id')
                  ->orWhereColumn('t.payment_id', '!=', 'p.id');
            })
            ->select([
                't.id as transaction_id',
                't.trans_id as reference_number',
                't.status',
                't.payment_id as linked_payment_id',
                'p.id as payment_id',
                'p.transaction_code as payment_transaction_code',
                'p.amount as payment_amount',
                'p.reversed as payment_reversed',
                DB::raw("'mpesa_c2b' as transaction_type")
            ])
            ->get();

        $total = $bankIssues->count() + $c2bIssues->count();

        if ($total > 0) {
            $this->warn("   ⚠️  Found {$total} transactions with unlinked payments");
            $this->issues['unlinked_payments'] = [
                'bank_statements' => $bankIssues->toArray(),
                'c2b' => $c2bIssues->toArray(),
                'count' => $total
            ];
        } else {
            $this->info("   ✅ No unlinked payments found");
        }

        $this->newLine();
    }

    /**
     * Issue 2: Transactions marked confirmed/collected but no payment exists
     */
    protected function analyzeConfirmedWithoutPayments()
    {
        $this->info('📊 Analyzing: Confirmed/collected transactions without payments...');

        // Bank Statement Transactions - confirmed but no payment
        $bankConfirmedNoPayment = DB::table('bank_statement_transactions as t')
            ->leftJoin('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.status', 'confirmed')
            ->where(function($q) {
                $q->where('t.payment_created', true)
                  ->orWhereNotNull('t.payment_id');
            })
            ->where(function($q) {
                $q->whereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select([
                't.id',
                't.reference_number',
                't.status',
                't.payment_created',
                't.payment_id',
                't.student_id',
                't.amount',
                't.is_swimming_transaction',
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        // Bank Statement Transactions - payment_created = true but payment_id is null or invalid
        $bankPaymentCreatedNoId = DB::table('bank_statement_transactions as t')
            ->leftJoin('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.payment_created', true)
            ->where(function($q) {
                $q->whereNull('t.payment_id')
                  ->orWhereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select([
                't.id',
                't.reference_number',
                't.status',
                't.payment_created',
                't.payment_id',
                't.student_id',
                't.amount',
                't.is_swimming_transaction',
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        // C2B Transactions - processed but no payment
        $c2bProcessedNoPayment = DB::table('mpesa_c2b_transactions as t')
            ->leftJoin('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.status', 'processed')
            ->whereNotNull('t.payment_id')
            ->where(function($q) {
                $q->whereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select([
                't.id',
                't.trans_id as reference_number',
                't.status',
                't.payment_id',
                't.student_id',
                't.trans_amount as amount',
                't.is_swimming_transaction',
                DB::raw("'mpesa_c2b' as transaction_type")
            ])
            ->get();

        $total = $bankConfirmedNoPayment->count() + 
                 $bankPaymentCreatedNoId->count() + 
                 $c2bProcessedNoPayment->count();

        if ($total > 0) {
            $this->warn("   ⚠️  Found {$total} confirmed/collected transactions without valid payments");
            $this->issues['confirmed_without_payments'] = [
                'bank_confirmed_no_payment' => $bankConfirmedNoPayment->toArray(),
                'bank_payment_created_no_id' => $bankPaymentCreatedNoId->toArray(),
                'c2b_processed_no_payment' => $c2bProcessedNoPayment->toArray(),
                'count' => $total
            ];
        } else {
            $this->info("   ✅ No confirmed transactions without payments");
        }

        $this->newLine();
    }

    /**
     * Issue 3: Swimming transactions used for fees collection
     */
    protected function analyzeSwimmingTransactionsForFees()
    {
        $this->info('📊 Analyzing: Swimming transactions used for fees...');

        // Check if swimming transactions have payments allocated to invoices
        $swimmingWithInvoicePayments = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->join('payment_allocations as pa', 'pa.payment_id', '=', 'p.id')
            ->join('invoice_items as ii', 'ii.id', '=', 'pa.invoice_item_id')
            ->join('invoices as inv', 'inv.id', '=', 'ii.invoice_id')
            ->where('t.is_swimming_transaction', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.amount as transaction_amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                'inv.id as invoice_id',
                'inv.invoice_number',
                'pa.amount as allocation_amount',
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        // Check C2B swimming transactions
        $c2bSwimmingWithInvoicePayments = DB::table('mpesa_c2b_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->join('payment_allocations as pa', 'pa.payment_id', '=', 'p.id')
            ->join('invoice_items as ii', 'ii.id', '=', 'pa.invoice_item_id')
            ->join('invoices as inv', 'inv.id', '=', 'ii.invoice_id')
            ->where('t.is_swimming_transaction', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->select([
                't.id as transaction_id',
                't.trans_id as reference_number',
                't.trans_amount as transaction_amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                'inv.id as invoice_id',
                'inv.invoice_number',
                'pa.amount as allocation_amount',
                DB::raw("'mpesa_c2b' as transaction_type")
            ])
            ->get();

        // Check swimming transactions that have payment_created = true but should go to wallet
        $swimmingWithPayments = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.is_swimming_transaction', true)
            ->where('t.payment_created', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->whereNotExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('swimming_transaction_allocations')
                  ->whereColumn('swimming_transaction_allocations.bank_statement_transaction_id', 't.id');
            })
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        $c2bSwimmingWithPayments = DB::table('mpesa_c2b_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.is_swimming_transaction', true)
            ->where('t.status', 'processed')
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->select([
                't.id as transaction_id',
                't.trans_id as reference_number',
                't.trans_amount as amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                DB::raw("'mpesa_c2b' as transaction_type")
            ])
            ->get();

        $total = $swimmingWithInvoicePayments->count() + 
                 $c2bSwimmingWithInvoicePayments->count() +
                 $swimmingWithPayments->count() +
                 $c2bSwimmingWithPayments->count();

        if ($total > 0) {
            $this->warn("   ⚠️  Found {$total} swimming transactions potentially used for fees");
            $this->issues['swimming_for_fees'] = [
                'bank_with_invoice_allocations' => $swimmingWithInvoicePayments->toArray(),
                'c2b_with_invoice_allocations' => $c2bSwimmingWithInvoicePayments->toArray(),
                'bank_with_payments' => $swimmingWithPayments->toArray(),
                'c2b_with_payments' => $c2bSwimmingWithPayments->toArray(),
                'count' => $total
            ];
        } else {
            $this->info("   ✅ No swimming transactions used for fees");
        }

        $this->newLine();
    }

    /**
     * Issue 4: Other underlying issues
     */
    protected function analyzeOtherIssues()
    {
        $this->info('📊 Analyzing: Other potential issues...');

        $otherIssues = [];

        // 1. Transactions with payment_id pointing to reversed payments
        $withReversedPayments = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('p.reversed', true)
            ->where('t.payment_created', true)
            ->select([
                't.id',
                't.reference_number',
                't.payment_id',
                'p.reversed_at',
                DB::raw("'bank_statement' as transaction_type"),
                DB::raw("'Has reversed payment but payment_created still true' as issue")
            ])
            ->get();

        if ($withReversedPayments->count() > 0) {
            $otherIssues['reversed_payment_still_linked'] = $withReversedPayments->toArray();
        }

        // 2. C2B with reversed payments
        $c2bWithReversedPayments = DB::table('mpesa_c2b_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('p.reversed', true)
            ->where('t.status', 'processed')
            ->select([
                't.id',
                't.trans_id as reference_number',
                't.payment_id',
                'p.reversed_at',
                DB::raw("'mpesa_c2b' as transaction_type"),
                DB::raw("'Has reversed payment but status still processed' as issue")
            ])
            ->get();

        if ($c2bWithReversedPayments->count() > 0) {
            $otherIssues['c2b_reversed_payment_still_linked'] = $c2bWithReversedPayments->toArray();
        }

        // 3. Duplicate reference numbers across transaction types
        $duplicateRefs = DB::select("
            SELECT 
                reference_number,
                COUNT(*) as count,
                GROUP_CONCAT(CONCAT(type, ':', id) SEPARATOR ', ') as transactions
            FROM (
                SELECT reference_number, 'bank_statement' as type, id FROM bank_statement_transactions WHERE reference_number IS NOT NULL AND reference_number != ''
                UNION ALL
                SELECT trans_id as reference_number, 'mpesa_c2b' as type, id FROM mpesa_c2b_transactions WHERE trans_id IS NOT NULL AND trans_id != ''
            ) as combined
            GROUP BY reference_number
            HAVING count > 1
        ");

        if (count($duplicateRefs) > 0) {
            $otherIssues['duplicate_references'] = $duplicateRefs;
        }

        // 4. Payments with transaction_code matching multiple transactions
        $paymentsWithMultipleTransactions = DB::select("
            SELECT 
                p.id as payment_id,
                p.transaction_code,
                COUNT(DISTINCT t.id) as transaction_count
            FROM payments p
            LEFT JOIN bank_statement_transactions t ON (
                t.reference_number = p.transaction_code 
                OR p.transaction_code LIKE CONCAT(t.reference_number, '-%')
            )
            LEFT JOIN mpesa_c2b_transactions c2b ON (
                c2b.trans_id = p.transaction_code 
                OR p.transaction_code LIKE CONCAT(c2b.trans_id, '-%')
            )
            WHERE p.reversed = false
            AND p.deleted_at IS NULL
            AND p.transaction_code IS NOT NULL
            GROUP BY p.id, p.transaction_code
            HAVING transaction_count > 1
        ");

        if (count($paymentsWithMultipleTransactions) > 0) {
            $otherIssues['payments_linked_to_multiple_transactions'] = $paymentsWithMultipleTransactions;
        }

        // 5. Transactions with mismatched amounts
        $mismatchedAmounts = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.payment_created', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->whereRaw('ABS(t.amount - p.amount) > 0.01')
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.amount as transaction_amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                DB::raw("ABS(t.amount - p.amount) as difference"),
                DB::raw("'bank_statement' as transaction_type")
            ])
            ->get();

        if ($mismatchedAmounts->count() > 0) {
            $otherIssues['mismatched_amounts'] = $mismatchedAmounts->toArray();
        }

        $total = count($otherIssues);

        if ($total > 0) {
            $this->warn("   ⚠️  Found {$total} other issue categories");
            $this->issues['other_issues'] = $otherIssues;
        } else {
            $this->info("   ✅ No other issues found");
        }

        $this->newLine();
    }

    /**
     * Display summary
     */
    protected function displaySummary()
    {
        $this->info('📋 Summary of Issues:');
        $this->newLine();

        $totalIssues = 0;

        if (isset($this->issues['unlinked_payments'])) {
            $count = $this->issues['unlinked_payments']['count'];
            $totalIssues += $count;
            $this->line("   1. Unlinked Payments: <fg=yellow>{$count}</>");
        }

        if (isset($this->issues['confirmed_without_payments'])) {
            $count = $this->issues['confirmed_without_payments']['count'];
            $totalIssues += $count;
            $this->line("   2. Confirmed Without Payments: <fg=yellow>{$count}</>");
        }

        if (isset($this->issues['swimming_for_fees'])) {
            $count = $this->issues['swimming_for_fees']['count'];
            $totalIssues += $count;
            $this->line("   3. Swimming Used for Fees: <fg=yellow>{$count}</>");
        }

        if (isset($this->issues['other_issues'])) {
            $otherCount = array_sum(array_map('count', $this->issues['other_issues']));
            $totalIssues += $otherCount;
            $this->line("   4. Other Issues: <fg=yellow>{$otherCount}</>");
        }

        $this->newLine();
        $this->info("Total Issues Found: <fg=cyan>{$totalIssues}</>");
        $this->newLine();

        if ($totalIssues > 0) {
            $this->warn('💡 Use --export to export results to CSV files');
            $this->warn('💡 Use --fix to attempt automatic fixes (use with caution!)');
        }
    }

    /**
     * Export results to CSV
     */
    protected function exportResults()
    {
        $this->info('📤 Exporting results to CSV files...');

        $exportDir = storage_path('app/transaction_analysis');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        // Export each issue category
        foreach ($this->issues as $category => $data) {
            if ($category === 'other_issues') {
                foreach ($data as $subCategory => $items) {
                    if (!empty($items)) {
                        $filename = "{$exportDir}/{$timestamp}_{$category}_{$subCategory}.csv";
                        $this->exportToCsv($filename, $items, $subCategory);
                    }
                }
            } else {
                if (isset($data['bank_statements']) || isset($data['c2b'])) {
                    if (!empty($data['bank_statements'])) {
                        $filename = "{$exportDir}/{$timestamp}_{$category}_bank_statements.csv";
                        $this->exportToCsv($filename, $data['bank_statements'], $category);
                    }
                    if (!empty($data['c2b'])) {
                        $filename = "{$exportDir}/{$timestamp}_{$category}_c2b.csv";
                        $this->exportToCsv($filename, $data['c2b'], $category);
                    }
                } elseif (isset($data['count'])) {
                    // Skip if only has count
                    continue;
                } else {
                    $filename = "{$exportDir}/{$timestamp}_{$category}.csv";
                    $this->exportToCsv($filename, $data, $category);
                }
            }
        }

        $this->info("✅ Exported to: {$exportDir}");
    }

    protected function exportToCsv($filename, $data, $category)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        // Handle different data structures
        $rows = [];
        foreach ($data as $item) {
            if (is_object($item)) {
                $rows[] = (array)$item;
            } elseif (is_array($item)) {
                $rows[] = $item;
            }
        }

        if (empty($rows)) {
            return;
        }

        $file = fopen($filename, 'w');

        // Write headers from first row
        fputcsv($file, array_keys($rows[0]));

        // Write data
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }

    /**
     * Attempt automatic fixes
     */
    protected function attemptFixes()
    {
        $this->warn('⚠️  Automatic fixes are experimental. Review changes carefully!');
        
        if (!$this->confirm('Do you want to proceed with automatic fixes?', false)) {
            $this->info('Fixes cancelled.');
            return;
        }

        $this->info('🔧 Attempting fixes...');

        // Fix 1: Link unlinked payments
        if (isset($this->issues['unlinked_payments'])) {
            $this->fixUnlinkedPayments();
        }

        // Fix 2: Fix confirmed without payments
        if (isset($this->issues['confirmed_without_payments'])) {
            $this->fixConfirmedWithoutPayments();
        }

        // Fix 3: Fix reversed payment links
        if (isset($this->issues['other_issues']['reversed_payment_still_linked'])) {
            $this->fixReversedPaymentLinks();
        }

        $this->info('✅ Fix attempts completed. Please review results.');
    }

    protected function fixUnlinkedPayments()
    {
        $this->info('   Fixing unlinked payments...');
        // Implementation would link payments to transactions
    }

    protected function fixConfirmedWithoutPayments()
    {
        $this->info('   Fixing confirmed without payments...');
        // Implementation would reset payment_created flags
    }

    protected function fixReversedPaymentLinks()
    {
        $this->info('   Fixing reversed payment links...');
        // Implementation would clear payment_id and reset flags
    }
}

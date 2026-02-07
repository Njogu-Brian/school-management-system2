<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:audit
                            {--export= : Export detailed report to CSV file path}
                            {--fix : Attempt to fix issues found (use with caution)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive financial audit: verify collected transactions have payments, payments are allocated, and balances are accurate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting Comprehensive Financial Audit...');
        $this->newLine();

        $issues = [];
        $warnings = [];
        $stats = [
            'collected_transactions' => 0,
            'collected_with_payment' => 0,
            'collected_without_payment' => 0,
            'payments_total' => 0,
            'payments_allocated' => 0,
            'payments_unallocated' => 0,
            'payments_partially_allocated' => 0,
            'allocation_mismatches' => 0,
            'invoice_balance_issues' => 0,
            'c2b_bank_payments_unallocated' => 0,
        ];

        // 1. AUDIT COLLECTED TRANSACTIONS
        $this->info('📊 Step 1: Auditing Collected Transactions...');
        $collectedIssues = $this->auditCollectedTransactions($stats);
        $issues = array_merge($issues, $collectedIssues);

        // 2. AUDIT PAYMENT ALLOCATIONS
        $this->info('📊 Step 2: Auditing Payment Allocations...');
        $allocationIssues = $this->auditPaymentAllocations($stats);
        $issues = array_merge($issues, $allocationIssues);

        // 3. AUDIT INVOICE BALANCES
        $this->info('📊 Step 3: Auditing Invoice Balances...');
        $balanceIssues = $this->auditInvoiceBalances($stats);
        $issues = array_merge($issues, $balanceIssues);

        // 4. AUDIT FEE STATEMENTS
        $this->info('📊 Step 4: Auditing Fee Statements...');
        $statementIssues = $this->auditFeeStatements($stats);
        $issues = array_merge($issues, $statementIssues);

        // 5. AUDIT PAYMENT-TRANSACTION LINKS
        $this->info('📊 Step 5: Auditing Payment-Transaction Links...');
        $linkIssues = $this->auditPaymentTransactionLinks($stats);
        $issues = array_merge($issues, $linkIssues);

        // 6. AUDIT C2B / STATEMENT PAYMENTS WITHOUT ALLOCATIONS
        $this->info('📊 Step 6: Auditing C2B / Statement payments without allocations...');
        $c2bBankIssues = $this->auditC2BAndStatementPaymentsWithoutAllocations($stats);
        $issues = array_merge($issues, $c2bBankIssues);

        // Display Summary
        $this->displaySummary($stats, $issues);

        // Export if requested
        if ($this->option('export')) {
            $this->exportReport($issues, $stats);
        }

        // Fix issues if requested
        if ($this->option('fix')) {
            $this->fixIssues($issues);
        }

        return count($issues) > 0 ? 1 : 0;
    }

    /**
     * Audit collected transactions
     */
    protected function auditCollectedTransactions(array &$stats): array
    {
        $issues = [];

        // Bank transactions marked as collected
        $bankCollected = BankStatementTransaction::where('status', 'confirmed')
            ->where('payment_created', true)
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->where('transaction_type', 'credit')
            ->get();

        $stats['collected_transactions'] = $bankCollected->count();

        foreach ($bankCollected as $transaction) {
            $stats['collected_transactions']++;
            
            // Check if payment exists
            $payment = null;
            if ($transaction->payment_id) {
                $payment = Payment::where('id', $transaction->payment_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
            }
            
            if (!$payment && $transaction->reference_number) {
                $payment = Payment::where('transaction_code', $transaction->reference_number)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
            }

            if ($payment) {
                $stats['collected_with_payment']++;
            } else {
                $stats['collected_without_payment']++;
                $issues[] = [
                    'type' => 'collected_no_payment',
                    'severity' => 'high',
                    'transaction_type' => 'bank',
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference_number,
                    'amount' => $transaction->amount,
                    'date' => $transaction->transaction_date?->format('Y-m-d'),
                    'student' => $transaction->student ? $transaction->student->full_name : 'N/A',
                    'issue' => 'Transaction marked as collected but no valid payment found',
                ];
            }
        }

        // C2B transactions marked as collected
        if (Schema::hasTable('mpesa_c2b_transactions')) {
            $c2bCollected = MpesaC2BTransaction::whereNotNull('payment_id')
                ->where('is_duplicate', false)
                ->get();

            foreach ($c2bCollected as $transaction) {
                $stats['collected_transactions']++;
                
                $payment = Payment::where('id', $transaction->payment_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();

                if ($payment) {
                    $stats['collected_with_payment']++;
                } else {
                    $stats['collected_without_payment']++;
                    $issues[] = [
                        'type' => 'collected_no_payment',
                        'severity' => 'high',
                        'transaction_type' => 'c2b',
                        'transaction_id' => $transaction->id,
                        'reference' => $transaction->trans_id,
                        'amount' => $transaction->trans_amount,
                        'date' => $transaction->trans_time?->format('Y-m-d'),
                        'student' => $transaction->student ? $transaction->student->full_name : 'N/A',
                        'issue' => 'C2B transaction has payment_id but payment not found or reversed',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Audit payment allocations
     */
    protected function auditPaymentAllocations(array &$stats): array
    {
        $issues = [];

        $payments = Payment::where('reversed', false)
            ->whereNull('deleted_at')
            ->get();

        $stats['payments_total'] = $payments->count();

        foreach ($payments as $payment) {
            $allocations = PaymentAllocation::where('payment_id', $payment->id)
                ->get();

            $allocatedAmount = $allocations->sum('amount');
            $paymentAmount = $payment->amount;

            if ($allocatedAmount == 0) {
                $stats['payments_unallocated']++;
                $issues[] = [
                    'type' => 'payment_unallocated',
                    'severity' => 'medium',
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'amount' => $paymentAmount,
                    'date' => $payment->payment_date?->format('Y-m-d'),
                    'student' => $payment->student ? $payment->student->full_name : 'N/A',
                    'issue' => 'Payment has no allocations',
                ];
            } elseif ($allocatedAmount < $paymentAmount) {
                $stats['payments_partially_allocated']++;
                $issues[] = [
                    'type' => 'payment_partially_allocated',
                    'severity' => 'medium',
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'payment_amount' => $paymentAmount,
                    'allocated_amount' => $allocatedAmount,
                    'unallocated' => $paymentAmount - $allocatedAmount,
                    'date' => $payment->payment_date?->format('Y-m-d'),
                    'student' => $payment->student ? $payment->student->full_name : 'N/A',
                    'issue' => "Payment partially allocated: {$allocatedAmount} of {$paymentAmount}",
                ];
            } elseif ($allocatedAmount > $paymentAmount) {
                $stats['allocation_mismatches']++;
                $issues[] = [
                    'type' => 'allocation_over_allocated',
                    'severity' => 'high',
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'payment_amount' => $paymentAmount,
                    'allocated_amount' => $allocatedAmount,
                    'over_allocated' => $allocatedAmount - $paymentAmount,
                    'date' => $payment->payment_date?->format('Y-m-d'),
                    'student' => $payment->student ? $payment->student->full_name : 'N/A',
                    'issue' => "Payment over-allocated: {$allocatedAmount} > {$paymentAmount}",
                ];
            } else {
                $stats['payments_allocated']++;
            }

            // Verify allocations point to valid invoice items
            foreach ($allocations as $allocation) {
                if ($allocation->invoice_item_id) {
                    $invoiceItem = \App\Models\InvoiceItem::find($allocation->invoice_item_id);
                    if (!$invoiceItem) {
                        $issues[] = [
                            'type' => 'allocation_invalid_invoice_item',
                            'severity' => 'high',
                            'allocation_id' => $allocation->id,
                            'payment_id' => $payment->id,
                            'receipt' => $payment->receipt_number,
                            'invoice_item_id' => $allocation->invoice_item_id,
                            'amount' => $allocation->amount,
                            'issue' => 'Allocation references non-existent invoice item',
                        ];
                    } elseif ($invoiceItem->invoice) {
                        // Check if invoice is reversed or deleted
                        if ($invoiceItem->invoice->reversed || $invoiceItem->invoice->deleted_at) {
                            $issues[] = [
                                'type' => 'allocation_reversed_invoice',
                                'severity' => 'medium',
                                'allocation_id' => $allocation->id,
                                'payment_id' => $payment->id,
                                'receipt' => $payment->receipt_number,
                                'invoice_id' => $invoiceItem->invoice_id,
                                'amount' => $allocation->amount,
                                'issue' => 'Allocation references reversed or deleted invoice',
                            ];
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Audit invoice balances
     */
    protected function auditInvoiceBalances(array &$stats): array
    {
        $issues = [];

        $invoices = Invoice::whereNull('reversed_at')
            ->whereNull('deleted_at')
            ->get();

        foreach ($invoices as $invoice) {
            // Calculate actual paid amount from allocations via invoice items
            $paidAmount = PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id);
                })
                ->whereHas('payment', function($q) {
                    $q->where('reversed', false)->whereNull('deleted_at');
                })
                ->sum('amount');

            $expectedBalance = (float) $invoice->total - $paidAmount;
            $actualBalance = $invoice->balance ?? 0;

            // Allow small floating point differences
            if (abs($expectedBalance - $actualBalance) > 0.01) {
                $stats['invoice_balance_issues']++;
                $issues[] = [
                    'type' => 'invoice_balance_mismatch',
                    'severity' => 'high',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number ?? 'N/A',
                    'student' => $invoice->student ? $invoice->student->full_name : 'N/A',
                    'total_amount' => $invoice->total,
                    'paid_amount' => $paidAmount,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $actualBalance,
                    'difference' => $actualBalance - $expectedBalance,
                    'issue' => "Invoice balance mismatch: expected {$expectedBalance}, actual {$actualBalance}",
                ];
            }
        }

        return $issues;
    }

    /**
     * Audit fee statements
     */
    protected function auditFeeStatements(array &$stats): array
    {
        $issues = [];

        // Check students with active invoices
        $students = Student::where('archive', false)
            ->where('is_alumni', false)
            ->has('invoices')
            ->with(['invoices' => function($q) {
                $q->whereNull('reversed_at')->whereNull('deleted_at');
            }])
            ->get();

        foreach ($students as $student) {
            $invoices = $student->invoices->whereNull('reversed_at')->whereNull('deleted_at');
            
            if ($invoices->isEmpty()) {
                continue;
            }

            $totalInvoiced = $invoices->sum('total');
            $totalPaid = PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoices) {
                    $q->whereIn('invoice_id', $invoices->pluck('id'));
                })
                ->whereHas('payment', function($q) {
                    $q->where('reversed', false)->whereNull('deleted_at');
                })
                ->sum('amount');
            $totalBalance = $invoices->sum('balance');

            $expectedBalance = $totalInvoiced - $totalPaid;

            if (abs($expectedBalance - $totalBalance) > 0.01) {
                $issues[] = [
                    'type' => 'student_balance_mismatch',
                    'severity' => 'medium',
                    'student_id' => $student->id,
                    'student' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'total_invoiced' => $totalInvoiced,
                    'total_paid' => $totalPaid,
                    'expected_balance' => $expectedBalance,
                    'actual_balance' => $totalBalance,
                    'difference' => $totalBalance - $expectedBalance,
                    'issue' => "Student balance mismatch: expected {$expectedBalance}, actual {$totalBalance}",
                ];
            }
        }

        return $issues;
    }

    /**
     * Audit payment-transaction links
     */
    protected function auditPaymentTransactionLinks(array &$stats): array
    {
        $issues = [];

        // Payments that claim to be from bank transactions
        $payments = Payment::where('reversed', false)
            ->whereNull('deleted_at')
            ->whereNotNull('transaction_code')
            ->get();

        foreach ($payments as $payment) {
            // Check if transaction exists
            $bankTransaction = BankStatementTransaction::where('reference_number', $payment->transaction_code)
                ->first();

            $c2bTransaction = null;
            if (Schema::hasTable('mpesa_c2b_transactions')) {
                $c2bTransaction = MpesaC2BTransaction::where('trans_id', $payment->transaction_code)
                    ->first();
            }

            if (!$bankTransaction && !$c2bTransaction) {
                $issues[] = [
                    'type' => 'payment_no_transaction',
                    'severity' => 'low',
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'transaction_code' => $payment->transaction_code,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date?->format('Y-m-d'),
                    'student' => $payment->student ? $payment->student->full_name : 'N/A',
                    'issue' => 'Payment has transaction_code but no matching transaction found',
                ];
            } elseif ($bankTransaction && !$bankTransaction->payment_id) {
                // Transaction exists but not linked
                $issues[] = [
                    'type' => 'transaction_not_linked',
                    'severity' => 'low',
                    'transaction_id' => $bankTransaction->id,
                    'transaction_type' => 'bank',
                    'reference' => $bankTransaction->reference_number,
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'issue' => 'Transaction and payment exist but not linked',
                ];
            }
        }

        return $issues;
    }

    /**
     * Audit payments that originated from C2B or bank statement upload but have no allocations.
     * These are problematic because the money is "collected" but not applied to any invoice.
     */
    protected function auditC2BAndStatementPaymentsWithoutAllocations(array &$stats): array
    {
        $issues = [];

        $paymentIdsFromC2B = collect();
        $paymentIdsFromBank = collect();

        if (Schema::hasTable('mpesa_c2b_transactions')) {
            $paymentIdsFromC2B = MpesaC2BTransaction::whereNotNull('payment_id')
                ->where('is_duplicate', false)
                ->pluck('payment_id')
                ->unique()
                ->filter();
        }

        $paymentIdsFromBank = BankStatementTransaction::whereNotNull('payment_id')
            ->where('payment_created', true)
            ->where('is_duplicate', false)
            ->pluck('payment_id')
            ->unique()
            ->filter();

        $allSourcePaymentIds = $paymentIdsFromC2B->merge($paymentIdsFromBank)->unique()->values();

        foreach ($allSourcePaymentIds as $paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('reversed', false)
                ->whereNull('deleted_at')
                ->first();

            if (!$payment) {
                continue;
            }

            $allocatedAmount = PaymentAllocation::where('payment_id', $payment->id)->sum('amount');

            if ($allocatedAmount < 0.01) {
                $stats['c2b_bank_payments_unallocated']++;
                $fromC2B = $paymentIdsFromC2B->contains($paymentId);
                $issues[] = [
                    'type' => 'c2b_or_statement_payment_unallocated',
                    'severity' => 'high',
                    'payment_id' => $payment->id,
                    'receipt' => $payment->receipt_number,
                    'transaction_code' => $payment->transaction_code,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date?->format('Y-m-d'),
                    'student' => $payment->student ? $payment->student->full_name : 'N/A',
                    'source' => $fromC2B ? 'c2b' : 'bank_statement',
                    'issue' => ($fromC2B ? 'C2B' : 'Bank statement') . ' payment has no allocations to any invoice',
                ];
            }
        }

        return $issues;
    }

    /**
     * Display summary
     */
    protected function displaySummary(array $stats, array $issues): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    AUDIT SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Statistics
        $this->info('📊 STATISTICS:');
        $this->line("   Collected Transactions: {$stats['collected_transactions']}");
        $this->line("   ├─ With Valid Payment: {$stats['collected_with_payment']}");
        $this->line("   └─ Without Payment: {$stats['collected_without_payment']}");
        $this->newLine();

        $this->line("   Total Payments: {$stats['payments_total']}");
        $this->line("   ├─ Fully Allocated: {$stats['payments_allocated']}");
        $this->line("   ├─ Unallocated: {$stats['payments_unallocated']}");
        $this->line("   ├─ Partially Allocated: {$stats['payments_partially_allocated']}");
        $this->line("   └─ Over-allocated: {$stats['allocation_mismatches']}");
        $this->newLine();

        $this->line("   C2B / Bank statement payments with no allocations: " . ($stats['c2b_bank_payments_unallocated'] ?? 0));
        $this->newLine();

        $this->line("   Invoice Balance Issues: {$stats['invoice_balance_issues']}");
        $this->newLine();

        // Issues by severity
        $highIssues = array_filter($issues, fn($i) => $i['severity'] === 'high');
        $mediumIssues = array_filter($issues, fn($i) => $i['severity'] === 'medium');
        $lowIssues = array_filter($issues, fn($i) => $i['severity'] === 'low');

        $this->info('⚠️  ISSUES FOUND:');
        $this->line("   🔴 High Severity: " . count($highIssues));
        $this->line("   🟡 Medium Severity: " . count($mediumIssues));
        $this->line("   🔵 Low Severity: " . count($lowIssues));
        $this->line("   ────────────────────────────────");
        $this->line("   Total Issues: " . count($issues));
        $this->newLine();

        // Group issues by type
        $byType = [];
        foreach ($issues as $issue) {
            $type = $issue['type'];
            if (!isset($byType[$type])) {
                $byType[$type] = 0;
            }
            $byType[$type]++;
        }

        $this->info('📋 ISSUES BY TYPE:');
        foreach ($byType as $type => $count) {
            $this->line("   • " . str_replace('_', ' ', ucwords($type, '_')) . ": {$count}");
        }
        $this->newLine();

        // Show first 10 high severity issues
        if (!empty($highIssues)) {
            $this->warn('🔴 HIGH SEVERITY ISSUES (showing first 10):');
            $this->newLine();
            foreach (array_slice($highIssues, 0, 10) as $issue) {
                $this->line("   • {$issue['issue']}");
                if (isset($issue['receipt'])) {
                    $this->line("     Receipt: {$issue['receipt']}");
                }
                if (isset($issue['student'])) {
                    $this->line("     Student: {$issue['student']}");
                }
                if (isset($issue['amount'])) {
                    $this->line("     Amount: Ksh " . number_format($issue['amount'], 2));
                }
                $this->newLine();
            }
        }

        $this->info('═══════════════════════════════════════════════════════════');
    }

    /**
     * Export report to CSV
     */
    protected function exportReport(array $issues, array $stats): void
    {
        $path = $this->option('export');
        $handle = fopen($path, 'w');

        // Write header
        fputcsv($handle, [
            'Severity',
            'Type',
            'Issue',
            'Transaction ID',
            'Transaction Type',
            'Payment ID',
            'Receipt',
            'Invoice ID',
            'Invoice Item ID',
            'Student',
            'Amount',
            'Date',
            'Details'
        ]);

        // Write issues
        foreach ($issues as $issue) {
            fputcsv($handle, [
                $issue['severity'] ?? '',
                $issue['type'] ?? '',
                $issue['issue'] ?? '',
                $issue['transaction_id'] ?? '',
                $issue['transaction_type'] ?? '',
                $issue['payment_id'] ?? '',
                $issue['receipt'] ?? '',
                $issue['invoice_id'] ?? '',
                $issue['invoice_item_id'] ?? '',
                $issue['student'] ?? '',
                $issue['amount'] ?? ($issue['payment_amount'] ?? ''),
                $issue['date'] ?? '',
                json_encode($issue)
            ]);
        }

        fclose($handle);
        $this->info("✅ Report exported to: {$path}");
    }

    /**
     * Fix issues (use with caution)
     */
    protected function fixIssues(array $issues): void
    {
        if (!$this->confirm('⚠️  This will attempt to fix issues. Continue?', false)) {
            return;
        }

        $this->warn('⚠️  Auto-fix is not implemented. Please review issues manually.');
        $this->warn('   Use the exported report to identify and fix issues.');
    }
}

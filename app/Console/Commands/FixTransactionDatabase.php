<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Family;
use App\Models\TransactionFixAudit;
use App\Models\SwimmingWallet;
use App\Models\SwimmingLedger;
use App\Models\PaymentAllocation;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTransactionDatabase extends Command
{
    protected $signature = 'transactions:fix-all 
                            {--dry-run : Show what would be fixed without applying changes}
                            {--phase=all : Which phase to run (1,2,3,4,all)}';

    protected $description = 'Fix all transaction database issues with full reversibility';

    protected $auditLog = [];
    protected $stats = [
        'fixed' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🔧 Starting Comprehensive Transaction Database Fix');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $phase = $this->option('phase');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be applied');
            $this->newLine();
        }

        try {
            DB::beginTransaction();

            // Phase 1: Critical Fixes
            if ($phase === 'all' || $phase === '1') {
                $this->info('📋 Phase 1: Critical Fixes');
                $this->line('─────────────────────────────────────────');
                $this->fixReversedPaymentLinks($dryRun);
                $this->fixConfirmedWithoutPayments($dryRun);
                $this->newLine();
            }

            // Phase 2: Swimming Transactions
            if ($phase === 'all' || $phase === '2') {
                $this->info('📋 Phase 2: Swimming Transactions');
                $this->line('─────────────────────────────────────────');
                $this->fixSwimmingTransactions($dryRun);
                $this->newLine();
            }

            // Phase 3: Link Unlinked Payments
            if ($phase === 'all' || $phase === '3') {
                $this->info('📋 Phase 3: Link Unlinked Payments');
                $this->line('─────────────────────────────────────────');
                $this->phase3StartCount = $this->stats['fixed'];
                $this->fixUnlinkedPayments($dryRun);
                $this->newLine();
            }

            // Phase 4: Data Validation
            if ($phase === 'all' || $phase === '4') {
                $this->info('📋 Phase 4: Data Validation');
                $this->line('─────────────────────────────────────────');
                $this->validateAndFixMismatches($dryRun);
                $this->newLine();
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('⚠️  DRY RUN - All changes rolled back');
                $this->displaySummary();
            } else {
                // Save all audit logs
                $this->saveAuditLogs();
                DB::commit();
                $this->info('✅ All fixes applied successfully!');
                $this->displaySummary();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during fix: ' . $e->getMessage());
            Log::error('Transaction fix error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Phase 1.1: Fix Reversed Payment Links
     */
    protected function fixReversedPaymentLinks($dryRun)
    {
        $this->info('   Fixing reversed payment links...');

        // Bank Statement Transactions
        $bankTransactions = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('p.reversed', true)
            ->where('t.payment_created', true)
            ->select('t.*', 'p.id as payment_id', 'p.reversed_at')
            ->get();

        foreach ($bankTransactions as $transaction) {
            $oldValues = [
                'payment_id' => $transaction->payment_id,
                'payment_created' => $transaction->payment_created,
                'status' => $transaction->status,
            ];

            $newStatus = in_array($transaction->match_status, ['matched', 'manual']) 
                ? 'draft' 
                : $transaction->status;

            $newValues = [
                'payment_id' => null,
                'payment_created' => false,
                'status' => $newStatus,
            ];

            $this->logChange('reset_reversed_payment', 'bank_statement_transaction', $transaction->id, $oldValues, $newValues, 'Payment was reversed but transaction not updated');

            if (!$dryRun) {
                BankStatementTransaction::where('id', $transaction->id)->update($newValues);
            }

            $this->stats['fixed']++;
        }

        // C2B Transactions
        $c2bTransactions = DB::table('mpesa_c2b_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('p.reversed', true)
            ->where('t.status', 'processed')
            ->select('t.*', 'p.id as payment_id', 'p.reversed_at')
            ->get();

        foreach ($c2bTransactions as $transaction) {
            $oldValues = [
                'payment_id' => $transaction->payment_id,
                'status' => $transaction->status,
            ];

            $newStatus = in_array($transaction->allocation_status, ['auto_matched', 'manually_allocated']) 
                ? 'pending' 
                : 'pending';

            $newValues = [
                'payment_id' => null,
                'status' => $newStatus,
            ];

            $this->logChange('reset_reversed_payment', 'mpesa_c2b_transaction', $transaction->id, $oldValues, $newValues, 'Payment was reversed but transaction not updated');

            if (!$dryRun) {
                MpesaC2BTransaction::where('id', $transaction->id)->update($newValues);
            }

            $this->stats['fixed']++;
        }

        $this->info("      ✅ Fixed " . ($bankTransactions->count() + $c2bTransactions->count()) . " reversed payment links");
    }

    /**
     * Phase 1.2: Fix Confirmed Without Payments
     */
    protected function fixConfirmedWithoutPayments($dryRun)
    {
        $this->info('   Fixing confirmed transactions without valid payments...');

        // Bank Statement Transactions
        $bankTransactions = DB::table('bank_statement_transactions as t')
            ->leftJoin('payments as p', function($join) {
                $join->on('p.id', '=', 't.payment_id')
                     ->where('p.reversed', false)
                     ->whereNull('p.deleted_at');
            })
            ->where(function($q) {
                $q->where('t.status', 'confirmed')
                  ->orWhere('t.payment_created', true);
            })
            ->where(function($q) {
                $q->whereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select('t.*')
            ->get();

        foreach ($bankTransactions as $transaction) {
            $oldValues = [
                'payment_id' => $transaction->payment_id,
                'payment_created' => $transaction->payment_created,
                'status' => $transaction->status,
            ];

            $newStatus = in_array($transaction->match_status, ['matched', 'manual']) 
                ? 'draft' 
                : 'draft';

            $newValues = [
                'payment_id' => null,
                'payment_created' => false,
                'status' => $newStatus,
            ];

            $this->logChange('reset_confirmed_no_payment', 'bank_statement_transaction', $transaction->id, $oldValues, $newValues, 'Transaction marked confirmed but payment is invalid');

            if (!$dryRun) {
                BankStatementTransaction::where('id', $transaction->id)->update($newValues);
            }

            $this->stats['fixed']++;
        }

        // C2B Transactions
        $c2bTransactions = DB::table('mpesa_c2b_transactions as t')
            ->leftJoin('payments as p', function($join) {
                $join->on('p.id', '=', 't.payment_id')
                     ->where('p.reversed', false)
                     ->whereNull('p.deleted_at');
            })
            ->where('t.status', 'processed')
            ->where(function($q) {
                $q->whereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select('t.*')
            ->get();

        foreach ($c2bTransactions as $transaction) {
            $oldValues = [
                'payment_id' => $transaction->payment_id,
                'status' => $transaction->status,
            ];

            $newStatus = in_array($transaction->allocation_status, ['auto_matched', 'manually_allocated']) 
                ? 'pending' 
                : 'pending';

            $newValues = [
                'payment_id' => null,
                'status' => $newStatus,
            ];

            $this->logChange('reset_confirmed_no_payment', 'mpesa_c2b_transaction', $transaction->id, $oldValues, $newValues, 'Transaction marked processed but payment is invalid');

            if (!$dryRun) {
                MpesaC2BTransaction::where('id', $transaction->id)->update($newValues);
            }

            $this->stats['fixed']++;
        }

        $this->info("      ✅ Fixed " . ($bankTransactions->count() + $c2bTransactions->count()) . " confirmed transactions without payments");
    }

    /**
     * Phase 2: Fix Swimming Transactions
     */
    protected function fixSwimmingTransactions($dryRun)
    {
        $this->info('   Fixing swimming transactions used for fees...');

        // Bank Statement Transactions with invoice allocations
        $swimmingTransactions = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->join('payment_allocations as pa', 'pa.payment_id', '=', 'p.id')
            ->join('invoice_items as ii', 'ii.id', '=', 'pa.invoice_item_id')
            ->where('t.is_swimming_transaction', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->select([
                't.id as transaction_id',
                't.student_id',
                'p.id as payment_id',
                'pa.id as allocation_id',
                'pa.amount as allocation_amount',
                'ii.invoice_id',
            ])
            ->get()
            ->groupBy('transaction_id');

        foreach ($swimmingTransactions as $transactionId => $allocations) {
            $transaction = BankStatementTransaction::find($transactionId);
            if (!$transaction || !$transaction->student_id) {
                continue;
            }

            $student = Student::find($transaction->student_id);
            if (!$student) {
                continue;
            }

            $totalAllocationAmount = $allocations->sum('allocation_amount');

            // Get or create swimming wallet
            $wallet = SwimmingWallet::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'balance' => 0,
                    'total_credited' => 0,
                    'total_debited' => 0,
                ]
            );

            $oldWalletBalance = $wallet->balance;
            $newWalletBalance = $oldWalletBalance + $totalAllocationAmount;

            // Log wallet credit
            $this->logChange('swimming_wallet_credit', 'swimming_wallet', $wallet->id, 
                ['balance' => $oldWalletBalance], 
                ['balance' => $newWalletBalance],
                "Credited from swimming transaction #{$transactionId} (reversing invoice allocations)");

            // Reverse each allocation
            foreach ($allocations as $allocation) {
                    $invoice = Invoice::find($allocation->invoice_id);
                if ($invoice) {
                    $oldInvoicePaid = $invoice->paid_amount ?? 0;

                    $this->logChange('reverse_invoice_allocation', 'payment_allocation', $allocation->allocation_id,
                        ['amount' => $allocation->allocation_amount, 'invoice_id' => $allocation->invoice_id],
                        ['deleted' => true],
                        "Reversing allocation from swimming transaction");

                    if (!$dryRun) {
                        // Delete allocation
                        PaymentAllocation::where('id', $allocation->allocation_id)->delete();

                        // Recalculate invoice (this will update paid_amount, balance, and status)
                        $invoice->recalculate();
                    }
                }
            }

            if (!$dryRun) {
                // Credit swimming wallet
                $wallet->balance = $newWalletBalance;
                $wallet->total_credited += $totalAllocationAmount;
                $wallet->last_transaction_at = now();
                $wallet->save();

                // Create ledger entry
                SwimmingLedger::create([
                    'student_id' => $student->id,
                    'type' => SwimmingLedger::TYPE_CREDIT,
                    'amount' => $totalAllocationAmount,
                    'balance_after' => $newWalletBalance,
                    'source' => SwimmingLedger::SOURCE_ADJUSTMENT,
                    'source_id' => $transaction->id,
                    'source_type' => BankStatementTransaction::class,
                    'description' => "Correction: Swimming payment from transaction #{$transaction->reference_number} (reversed from fees)",
                    'created_by' => 1, // System user
                ]);

                // Update payment allocated amount
                $payment = Payment::find($allocations->first()->payment_id);
                if ($payment) {
                    $payment->updateAllocationTotals();
                }
            }

            $this->stats['fixed']++;
        }

        $this->info("      ✅ Fixed " . $swimmingTransactions->count() . " swimming transactions");
    }

    /**
     * Phase 3: Link Unlinked Payments
     */
    protected function fixUnlinkedPayments($dryRun)
    {
        $this->info('   Linking unlinked payments (using family_id for siblings)...');

        // Get transactions with unlinked payments
        $unlinked = DB::table('bank_statement_transactions as t')
            ->join('payments as p', function($join) {
                $join->on('p.transaction_code', '=', 't.reference_number')
                     ->orWhere(function($q) {
                         $q->whereColumn('p.transaction_code', 'LIKE', DB::raw("CONCAT(t.reference_number, '-%')"));
                     });
            })
            ->whereNotNull('t.reference_number')
            ->where('t.reference_number', '!=', '')
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->where(function($q) {
                $q->whereNull('t.payment_id')
                  ->orWhereColumn('t.payment_id', '!=', 'p.id');
            })
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.student_id as transaction_student_id',
                'p.id as payment_id',
                'p.transaction_code',
                'p.student_id as payment_student_id',
            ])
            ->get()
            ->groupBy('transaction_id');

        foreach ($unlinked as $transactionId => $payments) {
            $transaction = BankStatementTransaction::find($transactionId);
            if (!$transaction) {
                continue;
            }

            // Check if this is sibling sharing
            $isSiblingSharing = false;
            $primaryPayment = null;

            if ($transaction->student_id) {
                $transactionStudent = Student::find($transaction->student_id);
                if ($transactionStudent && $transactionStudent->family_id) {
                    // Get all payments for students in the same family
                    $familyStudentIds = Student::where('family_id', $transactionStudent->family_id)
                        ->pluck('id');

                    foreach ($payments as $payment) {
                        if ($familyStudentIds->contains($payment->payment_student_id)) {
                            $isSiblingSharing = true;
                            // Use the payment that matches the transaction's student as primary
                            if ($payment->payment_student_id == $transaction->student_id) {
                                $primaryPayment = $payment;
                            }
                        }
                    }
                }
            }

            // If not sibling sharing, link the first matching payment
            if (!$isSiblingSharing && $payments->count() == 1) {
                $payment = $payments->first();
                
                $oldValues = [
                    'payment_id' => $transaction->payment_id,
                    'payment_created' => $transaction->payment_created,
                ];

                $newValues = [
                    'payment_id' => $payment->payment_id,
                    'payment_created' => true,
                ];

                $this->logChange('link_payment', 'bank_statement_transaction', $transactionId, $oldValues, $newValues, 'Linking payment by reference number');

                if (!$dryRun) {
                    BankStatementTransaction::where('id', $transactionId)->update($newValues);
                }

                $this->stats['fixed']++;
            } elseif ($isSiblingSharing && $primaryPayment) {
                // Link primary payment for sibling sharing
                $oldValues = [
                    'payment_id' => $transaction->payment_id,
                    'payment_created' => $transaction->payment_created,
                ];

                $newValues = [
                    'payment_id' => $primaryPayment->payment_id,
                    'payment_created' => true,
                ];

                $this->logChange('link_payment_sibling', 'bank_statement_transaction', $transactionId, $oldValues, $newValues, 'Linking primary payment for sibling sharing (confirmed via family_id)');

                if (!$dryRun) {
                    BankStatementTransaction::where('id', $transactionId)->update($newValues);
                }

                $this->stats['fixed']++;
            } else {
                // Multiple payments, need manual review - skip for now
                $this->stats['skipped']++;
            }
        }

        $linkedCount = $this->stats['fixed'] - $this->phase3StartCount;
        $this->info("      ✅ Linked {$linkedCount} payments");
    }

    /**
     * Phase 4: Validate and Fix Mismatches
     */
    protected function validateAndFixMismatches($dryRun)
    {
        $this->info('   Validating mismatched amounts...');

        // Check for mismatches that aren't from sibling sharing
        $mismatches = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.payment_created', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->whereRaw('ABS(t.amount - p.amount) > 0.01')
            ->select([
                't.id',
                't.reference_number',
                't.amount as transaction_amount',
                't.student_id',
                'p.amount as payment_amount',
                DB::raw("ABS(t.amount - p.amount) as difference")
            ])
            ->get();

        foreach ($mismatches as $mismatch) {
            $transaction = BankStatementTransaction::find($mismatch->id);
            if (!$transaction) {
                continue;
            }

            // Check if this is sibling sharing
            $isSiblingSharing = false;
            if ($transaction->student_id) {
                $student = Student::find($transaction->student_id);
                if ($student && $student->family_id) {
                    // Check if there are other payments for siblings
                    $siblingStudentIds = Student::where('family_id', $student->family_id)
                        ->pluck('id');

                    $siblingPayments = Payment::where('transaction_code', 'LIKE', $transaction->reference_number . '%')
                        ->whereIn('student_id', $siblingStudentIds)
                        ->where('reversed', false)
                        ->sum('amount');

                    if ($siblingPayments > 0 && abs($siblingPayments - $transaction->amount) < 0.01) {
                        $isSiblingSharing = true;
                    }
                }
            }

            if (!$isSiblingSharing) {
                // This is a real mismatch - log for review
                $this->logChange('mismatch_detected', 'bank_statement_transaction', $mismatch->id,
                    ['transaction_amount' => $mismatch->transaction_amount, 'payment_amount' => $mismatch->payment_amount],
                    ['needs_review' => true],
                    "Amount mismatch detected (not from sibling sharing)");

                $this->stats['skipped']++;
            }
        }

        $this->info("      ✅ Validated " . $mismatches->count() . " potential mismatches");
    }

    /**
     * Helper: Log change for reversibility
     */
    protected function logChange($fixType, $entityType, $entityId, $oldValues, $newValues, $reason = null)
    {
        $this->auditLog[] = [
            'fix_type' => $fixType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'applied' => !$this->option('dry-run'),
            'applied_at' => now(),
        ];
    }

    /**
     * Save all audit logs
     */
    protected function saveAuditLogs()
    {
        if (empty($this->auditLog)) {
            return;
        }

        foreach ($this->auditLog as $log) {
            TransactionFixAudit::create($log);
        }
    }

    protected $phase3StartCount = 0;

    /**
     * Display summary
     */
    protected function displaySummary()
    {
        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("   Fixed: {$this->stats['fixed']}");
        $this->line("   Skipped: {$this->stats['skipped']}");
        $this->line("   Errors: {$this->stats['errors']}");
        $this->line("   Audit Logs: " . count($this->auditLog));
    }
}

<?php

namespace App\Services;

use App\Models\{
    BankStatementTransaction, SwimmingTransactionAllocation, Student, Payment
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Swimming Transaction Service
 * Handles reclassification of bank transactions for swimming payments
 */
class SwimmingTransactionService
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Mark transaction as swimming transaction.
     * Cannot mark if transaction has a linked fee payment (same as: fee payments cannot be used for swimming).
     */
    public function markAsSwimming(BankStatementTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Check if column exists before updating
            if (!\Illuminate\Support\Facades\Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
                // Column doesn't exist - need to run migration first
                throw new \Exception('Swimming transaction column not found. Please run the migration: php artisan migrate');
            }
            
            // Check if already marked as swimming
            if ($transaction->is_swimming_transaction) {
                throw new \Exception('Transaction is already marked as swimming.');
            }
            
            // Cannot mark as swimming if transaction has a linked fee payment
            if ($transaction->payment_id) {
                $payment = Payment::find($transaction->payment_id);
                if ($payment && !$payment->reversed) {
                    throw new \Exception('Cannot mark as swimming: this transaction has a linked fee payment (Receipt: ' . ($payment->receipt_number ?? $payment->transaction_code) . '). Only transactions without fee payments can be marked as swimming.');
                }
            }
            if ($transaction->payment_created) {
                throw new \Exception('Cannot mark as swimming: this transaction has already been used for fee collection. Only uncollected transactions can be marked as swimming.');
            }
            
            $updateData = ['is_swimming_transaction' => true];
            
            // Also set swimming_allocated_amount if column exists
            if (\Illuminate\Support\Facades\Schema::hasColumn('bank_statement_transactions', 'swimming_allocated_amount')) {
                $updateData['swimming_allocated_amount'] = 0;
            }
            
            $transaction->update($updateData);
            
            Log::info('Transaction marked as swimming', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);
        });
    }

    /**
     * Unmark transaction as swimming transaction
     */
    public function unmarkAsSwimming(BankStatementTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Check if allocations table exists
            if (!Schema::hasTable('swimming_transaction_allocations')) {
                // Table doesn't exist, safe to unmark
                $transaction->update([
                    'is_swimming_transaction' => false,
                    'swimming_allocated_amount' => 0,
                ]);
                return;
            }
            
            // Check if any allocations exist
            $allocations = SwimmingTransactionAllocation::where('bank_statement_transaction_id', $transaction->id)
                ->where('status', '!=', SwimmingTransactionAllocation::STATUS_REVERSED)
                ->exists();
            
            if ($allocations) {
                throw new \Exception('Cannot unmark transaction: allocations already exist. Reverse allocations first.');
            }
            
            $transaction->update([
                'is_swimming_transaction' => false,
                'swimming_allocated_amount' => 0,
            ]);
        });
    }

    /**
     * Allocate transaction amount to student(s)
     */
    public function allocateToStudents(
        BankStatementTransaction $transaction,
        array $allocations // [['student_id' => X, 'amount' => Y], ...]
    ): array {
        // Check if allocations table exists
        if (!Schema::hasTable('swimming_transaction_allocations')) {
            throw new \Exception('swimming_transaction_allocations table does not exist. Please run migrations: php artisan migrate');
        }
        
        return DB::transaction(function () use ($transaction, $allocations) {
            $totalAllocated = 0;
            $createdAllocations = [];
            
            foreach ($allocations as $allocation) {
                $studentId = $allocation['student_id'];
                $amount = (float) $allocation['amount'];
                
                // Validate amount
                if ($amount <= 0) {
                    continue;
                }
                
                $totalAllocated += $amount;
                
                // Validate total doesn't exceed transaction amount
                if ($totalAllocated > $transaction->amount) {
                    throw new \Exception("Total allocation ({$totalAllocated}) exceeds transaction amount ({$transaction->amount})");
                }
                
                $student = Student::findOrFail($studentId);
                
                // Create allocation record
                $allocationRecord = SwimmingTransactionAllocation::create([
                    'bank_statement_transaction_id' => $transaction->id,
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'status' => SwimmingTransactionAllocation::STATUS_PENDING,
                    'allocated_by' => auth()->id(),
                    'allocated_at' => now(),
                ]);
                
                $createdAllocations[] = $allocationRecord;
            }
            
            // Update transaction
            $transaction->update([
                'is_swimming_transaction' => true,
                'swimming_allocated_amount' => $totalAllocated,
            ]);
            
            return $createdAllocations;
        });
    }

    /**
     * Allocate and immediately process swimming split allocations without marking the transaction as swimming-only.
     */
    public function allocateSplitAndProcess(BankStatementTransaction $transaction, array $allocations): array
    {
        if (!Schema::hasTable('swimming_transaction_allocations')) {
            throw new \Exception('swimming_transaction_allocations table does not exist. Please run migrations: php artisan migrate');
        }

        return DB::transaction(function () use ($transaction, $allocations) {
            $totalAllocated = 0;
            $createdAllocations = [];

            foreach ($allocations as $allocation) {
                $studentId = (int) ($allocation['student_id'] ?? 0);
                $amount = (float) ($allocation['amount'] ?? 0);
                if ($studentId <= 0 || $amount <= 0) {
                    continue;
                }

                $totalAllocated += $amount;
                if ($totalAllocated > (float) $transaction->amount + 0.01) {
                    throw new \Exception("Total swimming allocation ({$totalAllocated}) exceeds transaction amount ({$transaction->amount})");
                }

                $student = Student::findOrFail($studentId);
                $allocationRecord = SwimmingTransactionAllocation::create([
                    'bank_statement_transaction_id' => $transaction->id,
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'status' => SwimmingTransactionAllocation::STATUS_PENDING,
                    'allocated_by' => auth()->id(),
                    'allocated_at' => now(),
                ]);
                $createdAllocations[] = $allocationRecord;

                $this->walletService->creditFromBankTransaction(
                    $student,
                    $transaction,
                    $amount,
                    "Swimming split from transaction #{$transaction->reference_number}"
                );

                $allocationRecord->update([
                    'status' => SwimmingTransactionAllocation::STATUS_ALLOCATED,
                    'notes' => trim(($allocationRecord->notes ?? '') . "\nProcessed in split allocation."),
                ]);
            }

            $transaction->update([
                'swimming_allocated_amount' => (float) ($transaction->swimming_allocated_amount ?? 0) + $totalAllocated,
            ]);

            return [
                'allocations' => $createdAllocations,
                'payments' => [],
                'total' => $totalAllocated,
            ];
        });
    }

    /**
     * Process pending allocations (credit wallets)
     */
    public function processPendingAllocations(?int $allocationId = null): array
    {
        // Check if allocations table exists
        if (!Schema::hasTable('swimming_transaction_allocations')) {
            Log::warning('swimming_transaction_allocations table does not exist. Please run migrations.');
            return [
                'processed' => 0,
                'failed' => 0,
                'errors' => ['Table swimming_transaction_allocations does not exist. Please run migrations.'],
            ];
        }
        
        $query = SwimmingTransactionAllocation::where('status', SwimmingTransactionAllocation::STATUS_PENDING);
        
        if ($allocationId) {
            $query->where('id', $allocationId);
        }
        
        $allocations = $query->with(['student', 'bankStatementTransaction'])->get();
        
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($allocations as $allocation) {
            try {
                // Credit wallet directly (no Payment created)
                $this->walletService->creditFromBankTransaction(
                    $allocation->student,
                    $allocation->bankStatementTransaction,
                    $allocation->amount,
                    "Swimming allocation from transaction #{$allocation->bankStatementTransaction->reference_number}"
                );
                
                // Update allocation status
                $allocation->update([
                    'status' => SwimmingTransactionAllocation::STATUS_ALLOCATED,
                ]);
                
                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('Failed to process swimming allocation', [
                    'allocation_id' => $allocation->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results['failed']++;
                $results['errors'][] = [
                    'allocation_id' => $allocation->id,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get or create payment from transaction
     * For shared transactions, creates one payment per student with their allocated amount
     */
    protected function getOrCreatePaymentFromTransaction(BankStatementTransaction $transaction, Student $student, ?float $allocationAmount = null): Payment
    {
        // For shared transactions, check if payment already exists for this student
        // Use transaction_code + student_id to find existing payment
        if ($transaction->reference_number) {
            $existingPayment = Payment::where('transaction_code', $transaction->reference_number)
                ->where('student_id', $student->id)
                ->where('reversed', false)
                ->first();
            
            if ($existingPayment) {
                return $existingPayment;
            }
        }
        
        // Use allocation amount if provided (for shared transactions), otherwise use full transaction amount
        $paymentAmount = $allocationAmount ?? $transaction->amount;
        
        // Create new payment
        $paymentMethod = \App\Models\PaymentMethod::where('name', 'like', '%bank%')->first();
        
        // For shared transactions, use modified transaction_code to avoid unique constraint
        $transactionCode = $transaction->reference_number;
        if ($transaction->is_shared && $allocationAmount) {
            $transactionCode = $transaction->reference_number . '-SWIM-' . $student->id;
        }
        
        $payment = Payment::create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
            'amount' => $paymentAmount,
            'payment_method_id' => $paymentMethod?->id,
            'payment_method' => $paymentMethod?->name ?? 'Bank Transfer',
            'transaction_code' => $transactionCode,
            'receipt_number' => 'SWIM-' . $transaction->id . '-' . $student->id . '-' . time(),
            'payer_name' => $transaction->payer_name ?? $student->first_name . ' ' . $student->last_name,
            'payer_type' => 'parent',
            'narration' => $transaction->description . ' (Swimming)',
            'payment_date' => $transaction->transaction_date,
            'bank_account_id' => $transaction->bank_account_id,
            // Mark as allocated since swimming payments go directly to wallet (not to invoice items)
            'allocated_amount' => $paymentAmount,
            'unallocated_amount' => 0,
        ]);
        
        // Link transaction to first payment (for reference)
        if (!$transaction->payment_id) {
            $transaction->update(['payment_id' => $payment->id]);
        }
        
        return $payment;
    }

    protected function createSplitSwimmingPayment(BankStatementTransaction $transaction, Student $student, float $amount, int $allocationId): Payment
    {
        $paymentMethod = \App\Models\PaymentMethod::where('name', 'like', '%bank%')->first();
        $transactionCode = ($transaction->reference_number ?: 'TXN') . '-SWIM-' . $student->id . '-' . $allocationId;

        return Payment::create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
            'amount' => $amount,
            'payment_method_id' => $paymentMethod?->id,
            'payment_method' => $paymentMethod?->name ?? 'Bank Transfer',
            'transaction_code' => $transactionCode,
            'receipt_number' => 'SWIM-' . $transaction->id . '-' . $student->id . '-' . $allocationId,
            'payer_name' => $transaction->payer_name ?? $student->first_name . ' ' . $student->last_name,
            'payer_type' => 'parent',
            'narration' => $transaction->description . ' (Swimming Split)',
            'payment_date' => $transaction->transaction_date,
            'bank_account_id' => $transaction->bank_account_id,
            'allocated_amount' => $amount,
            'unallocated_amount' => 0,
        ]);
    }

    /**
     * Reverse allocation
     */
    public function reverseAllocation(SwimmingTransactionAllocation $allocation, string $reason): void
    {
        DB::transaction(function () use ($allocation, $reason) {
            if ($allocation->isAllocated()) {
                // TODO: Reverse wallet credit if needed
                // This would require tracking which ledger entries were created from this allocation
            }
            
            $allocation->update([
                'status' => SwimmingTransactionAllocation::STATUS_REVERSED,
                'notes' => ($allocation->notes ?? '') . "\nReversed: {$reason}",
            ]);
            
            // Update transaction allocated amount
            $transaction = $allocation->bankStatementTransaction;
            $transaction->update([
                'swimming_allocated_amount' => max(0, $transaction->swimming_allocated_amount - $allocation->amount),
            ]);
        });
    }
}

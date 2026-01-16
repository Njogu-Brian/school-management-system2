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
     * Mark transaction as swimming transaction
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
                // Find or create payment from transaction
                $payment = $this->getOrCreatePaymentFromTransaction($allocation->bankStatementTransaction, $allocation->student);
                
                // Credit wallet
                $this->walletService->creditFromTransaction(
                    $allocation->student,
                    $payment,
                    $allocation->amount,
                    "Swimming payment allocation from transaction #{$allocation->bankStatementTransaction->reference_number}"
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
     */
    protected function getOrCreatePaymentFromTransaction(BankStatementTransaction $transaction, Student $student): Payment
    {
        // Check if payment already exists
        if ($transaction->payment_id) {
            $payment = Payment::find($transaction->payment_id);
            if ($payment && $payment->student_id === $student->id) {
                return $payment;
            }
        }
        
        // Create new payment
        $paymentMethod = \App\Models\PaymentMethod::where('name', 'like', '%bank%')->first();
        
        $payment = Payment::create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
            'amount' => $transaction->amount,
            'payment_method_id' => $paymentMethod?->id,
            'payment_method' => $paymentMethod?->name ?? 'Bank Transfer',
            'transaction_code' => $transaction->reference_number,
            'receipt_number' => 'SWIM-' . $transaction->id . '-' . time(),
            'payer_name' => $transaction->payer_name ?? $student->first_name . ' ' . $student->last_name,
            'payer_type' => 'parent',
            'narration' => $transaction->description . ' (Swimming)',
            'payment_date' => $transaction->transaction_date,
            'bank_account_id' => $transaction->bank_account_id,
        ]);
        
        // Link transaction to payment
        $transaction->update(['payment_id' => $payment->id]);
        
        return $payment;
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

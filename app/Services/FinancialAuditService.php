<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;

class FinancialAuditService
{
    /**
     * Log payment reversal
     */
    public static function logPaymentReversal(Payment $payment, array $oldValues = null): void
    {
        AuditLog::log('payment_reversed', $payment, $oldValues, [
            'reversed_by' => auth()->id(),
            'reversed_at' => now()->toDateTimeString(),
            'amount' => $payment->amount,
            'transaction_code' => $payment->transaction_code,
            'receipt_number' => $payment->receipt_number,
            'student_id' => $payment->student_id,
        ], ['financial', 'payment', 'reversal']);
    }
    
    /**
     * Log shared allocation edit for payment
     */
    public static function logPaymentSharedAllocationEdit(
        Payment $payment, 
        array $oldAllocations, 
        array $newAllocations
    ): void {
        AuditLog::log('payment_shared_allocation_edited', $payment, 
            ['shared_allocations' => $oldAllocations],
            ['shared_allocations' => $newAllocations],
            ['financial', 'payment', 'edit', 'shared']
        );
    }
    
    /**
     * Log transaction shared allocation edit
     */
    public static function logTransactionSharedAllocationEdit(
        BankStatementTransaction|MpesaC2BTransaction $transaction, 
        array $oldAllocations, 
        array $newAllocations
    ): void {
        AuditLog::log('transaction_shared_allocation_edited', $transaction, 
            ['shared_allocations' => $oldAllocations],
            ['shared_allocations' => $newAllocations],
            ['financial', 'transaction', 'edit', 'shared']
        );
    }
    
    /**
     * Log transaction archive
     */
    public static function logTransactionArchive(
        BankStatementTransaction $transaction, 
        int $paymentsReversed = 0
    ): void {
        AuditLog::log('transaction_archived', $transaction, null, [
            'archived_by' => auth()->id(),
            'archived_at' => now()->toDateTimeString(),
            'payments_reversed' => $paymentsReversed,
            'amount' => $transaction->amount,
            'reference_number' => $transaction->reference_number,
        ], ['financial', 'transaction', 'archive']);
    }
    
    /**
     * Log transaction rejection
     */
    public static function logTransactionRejection(
        BankStatementTransaction $transaction
    ): void {
        AuditLog::log('transaction_rejected', $transaction, [
            'status' => $transaction->getOriginal('status'),
            'payment_id' => $transaction->getOriginal('payment_id'),
        ], [
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now()->toDateTimeString(),
        ], ['financial', 'transaction', 'rejection']);
    }
    
    /**
     * Log payment transfer
     */
    public static function logPaymentTransfer(
        Payment $originalPayment,
        Payment $newPayment,
        float $amount
    ): void {
        AuditLog::log('payment_transferred', $originalPayment, null, [
            'transferred_to_payment_id' => $newPayment->id,
            'transferred_to_student_id' => $newPayment->student_id,
            'transferred_amount' => $amount,
            'transferred_by' => auth()->id(),
            'transferred_at' => now()->toDateTimeString(),
        ], ['financial', 'payment', 'transfer']);
    }
}

<?php

namespace App\Services;

use App\Models\{
    SwimmingWallet, SwimmingLedger, Student, OptionalFee, Payment, User
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Swimming Wallet Service
 * Handles credits, debits, and balance management for swimming wallets
 */
class SwimmingWalletService
{
    /**
     * Credit wallet from a transaction
     */
    public function creditFromTransaction(Student $student, Payment $payment, float $amount, ?string $description = null): SwimmingLedger
    {
        return DB::transaction(function () use ($student, $payment, $amount, $description) {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance + $amount;
            
            // Update wallet
            $wallet->update([
                'balance' => $newBalance,
                'total_credited' => $wallet->total_credited + $amount,
                'last_transaction_at' => now(),
            ]);
            
            // Create ledger entry
            $ledger = SwimmingLedger::create([
                'student_id' => $student->id,
                'type' => SwimmingLedger::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => SwimmingLedger::SOURCE_TRANSACTION,
                'source_id' => $payment->id,
                'source_type' => Payment::class,
                'description' => $description ?? "Swimming payment from transaction #{$payment->transaction_code}",
                'created_by' => auth()->id(),
            ]);
            
            Log::info('Swimming wallet credited', [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'balance' => $newBalance,
            ]);
            
            return $ledger;
        });
    }

    /**
     * Credit wallet from optional fee
     */
    public function creditFromOptionalFee(Student $student, OptionalFee $optionalFee, float $amount, ?string $description = null): SwimmingLedger
    {
        return DB::transaction(function () use ($student, $optionalFee, $amount, $description) {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance + $amount;
            
            // Update wallet
            $wallet->update([
                'balance' => $newBalance,
                'total_credited' => $wallet->total_credited + $amount,
                'last_transaction_at' => now(),
            ]);
            
            // Create ledger entry
            $ledger = SwimmingLedger::create([
                'student_id' => $student->id,
                'type' => SwimmingLedger::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => SwimmingLedger::SOURCE_OPTIONAL_FEE,
                'source_id' => $optionalFee->id,
                'source_type' => OptionalFee::class,
                'description' => $description ?? "Swimming termly fee for Term {$optionalFee->term}",
                'created_by' => auth()->id(),
            ]);
            
            return $ledger;
        });
    }

    /**
     * Credit wallet from admin adjustment
     */
    public function creditFromAdjustment(Student $student, float $amount, string $description, ?User $user = null): SwimmingLedger
    {
        return DB::transaction(function () use ($student, $amount, $description, $user) {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance + $amount;
            
            // Update wallet
            $wallet->update([
                'balance' => $newBalance,
                'total_credited' => $wallet->total_credited + $amount,
                'last_transaction_at' => now(),
            ]);
            
            // Create ledger entry
            $ledger = SwimmingLedger::create([
                'student_id' => $student->id,
                'type' => SwimmingLedger::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => SwimmingLedger::SOURCE_ADJUSTMENT,
                'description' => $description,
                'created_by' => $user?->id ?? auth()->id(),
            ]);
            
            return $ledger;
        });
    }

    /**
     * Debit wallet for attendance
     */
    public function debitForAttendance(Student $student, float $amount, int $attendanceId, ?string $description = null): SwimmingLedger
    {
        return DB::transaction(function () use ($student, $amount, $attendanceId, $description) {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            
            // Allow negative balances - this allows tracking unpaid amounts owed by parents
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance - $amount;
            
            // Update wallet
            $wallet->update([
                'balance' => $newBalance,
                'total_debited' => $wallet->total_debited + $amount,
                'last_transaction_at' => now(),
            ]);
            
            // Create ledger entry
            $ledger = SwimmingLedger::create([
                'student_id' => $student->id,
                'type' => SwimmingLedger::TYPE_DEBIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => SwimmingLedger::SOURCE_ATTENDANCE,
                'source_id' => $attendanceId,
                'source_type' => \App\Models\SwimmingAttendance::class,
                'swimming_attendance_id' => $attendanceId,
                'description' => $description ?? "Swimming session attendance",
                'created_by' => auth()->id(),
            ]);
            
            return $ledger;
        });
    }

    /**
     * Get wallet balance for student
     */
    public function getBalance(Student $student): float
    {
        $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
        return (float) $wallet->balance;
    }

    /**
     * Check if student has sufficient balance
     */
    public function hasSufficientBalance(Student $student, float $amount): bool
    {
        $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
        return $wallet->hasSufficientBalance($amount);
    }

    /**
     * Reverse/refund a debit for attendance (when unmarking attendance)
     * This credits the wallet back with the amount that was previously debited
     */
    public function reverseAttendanceDebit(Student $student, int $attendanceId, float $amount, ?string $description = null): SwimmingLedger
    {
        return DB::transaction(function () use ($student, $attendanceId, $amount, $description) {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance + $amount;
            
            // Update wallet - credit back the amount
            $wallet->update([
                'balance' => $newBalance,
                'total_debited' => max(0, $wallet->total_debited - $amount), // Reduce total debited
                'last_transaction_at' => now(),
            ]);
            
            // Create ledger entry for the reversal/refund
            $ledger = SwimmingLedger::create([
                'student_id' => $student->id,
                'type' => SwimmingLedger::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source' => SwimmingLedger::SOURCE_ADJUSTMENT,
                'source_id' => $attendanceId,
                'source_type' => \App\Models\SwimmingAttendance::class,
                'swimming_attendance_id' => $attendanceId,
                'description' => $description ?? "Attendance reversal - swimming session unmarked",
                'created_by' => auth()->id(),
            ]);
            
            Log::info('Swimming wallet credited (attendance reversal)', [
                'student_id' => $student->id,
                'attendance_id' => $attendanceId,
                'amount' => $amount,
                'balance' => $newBalance,
            ]);
            
            return $ledger;
        });
    }
}

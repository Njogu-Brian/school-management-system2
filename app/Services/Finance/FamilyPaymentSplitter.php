<?php

namespace App\Services\Finance;

use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Services\StudentBalanceService;
use Illuminate\Support\Facades\Log;

/**
 * Detect when a payment matched to one child is actually a family/sibling payment
 * and split it across the household using SiblingPaymentAllocator.
 */
class FamilyPaymentSplitter
{
    public const FAMILY_TOTAL_TOLERANCE = 10.0;

    public const EXCESS_SHARE_THRESHOLD = 500.0;

    /**
     * @param  array<int|string, float|int>  $balances  keyed by student id
     */
    public static function shouldShare(array $balances, float $payment, int $matchedStudentId): bool
    {
        $positive = [];
        foreach ($balances as $id => $balance) {
            $b = (float) $balance;
            if ($b > 0.009) {
                $positive[(int) $id] = $b;
            }
        }

        if (count($positive) < 2 || $payment <= 0) {
            return false;
        }

        $familyTotal = array_sum($positive);
        if (abs($payment - $familyTotal) <= self::FAMILY_TOTAL_TOLERANCE) {
            return true;
        }

        $matchedBalance = $positive[$matchedStudentId] ?? 0.0;
        $excess = $payment - $matchedBalance;
        $siblingOwed = $familyTotal - $matchedBalance;

        return $excess >= self::EXCESS_SHARE_THRESHOLD && $siblingOwed >= 0.5;
    }

    /**
     * @param  array<int|string, float|int>  $balances  keyed by student id
     * @return array<int, array{student_id: int, amount: float}>|null
     */
    public static function allocations(array $balances, float $payment, int $matchedStudentId): ?array
    {
        if (! self::shouldShare($balances, $payment, $matchedStudentId)) {
            return null;
        }

        $ordered = [];
        if (array_key_exists($matchedStudentId, $balances)) {
            $ordered[$matchedStudentId] = (float) $balances[$matchedStudentId];
        }
        foreach ($balances as $id => $balance) {
            $id = (int) $id;
            if ($id !== $matchedStudentId) {
                $ordered[$id] = (float) $balance;
            }
        }

        $alloc = SiblingPaymentAllocator::allocate($ordered, $payment);
        $out = [];
        foreach ($alloc as $id => $amount) {
            if ((float) $amount > 0.009) {
                $out[] = ['student_id' => (int) $id, 'amount' => round((float) $amount, 2)];
            }
        }

        return count($out) >= 2 ? $out : null;
    }

    /**
     * @param  array<int>  $siblingIds
     * @return array<int, array{student_id: int, amount: float}>
     */
    public static function allocationsForSiblings(array $siblingIds, float $payment, ?int $preferredStudentId = null): array
    {
        $siblingIds = array_values(array_unique(array_filter(array_map('intval', $siblingIds))));
        if ($siblingIds === [] || $payment <= 0) {
            return [];
        }

        $balances = [];
        foreach ($siblingIds as $id) {
            $balances[$id] = max(0.0, StudentBalanceService::getTotalOutstandingBalance($id));
        }

        $preferred = $preferredStudentId ?: $siblingIds[0];
        $alloc = self::allocations($balances, $payment, $preferred);
        if ($alloc) {
            return $alloc;
        }

        $ordered = [];
        if (isset($balances[$preferred])) {
            $ordered[$preferred] = $balances[$preferred];
        }
        foreach ($balances as $id => $balance) {
            if ($id !== $preferred) {
                $ordered[$id] = $balance;
            }
        }

        $split = SiblingPaymentAllocator::allocate($ordered, $payment);
        $out = [];
        foreach ($split as $id => $amount) {
            if ((float) $amount > 0.009) {
                $out[] = ['student_id' => (int) $id, 'amount' => round((float) $amount, 2)];
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{student_id: int, amount: float}>|null
     */
    public static function allocationsForStudent(int $studentId, float $payment): ?array
    {
        $student = Student::find($studentId);
        if (! $student || ! $student->family_id) {
            return null;
        }

        $siblings = Student::query()
            ->where('family_id', $student->family_id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('id')
            ->get();

        if ($siblings->count() < 2) {
            return null;
        }

        $balances = [];
        foreach ($siblings as $sibling) {
            $balances[(int) $sibling->id] = max(0.0, StudentBalanceService::getTotalOutstandingBalance($sibling->id));
        }

        return self::allocations($balances, $payment, $studentId);
    }

    public static function applyToPaymentTransaction(PaymentTransaction $transaction): bool
    {
        if ($transaction->is_shared) {
            return false;
        }
        if ($transaction->parent_wallet_id) {
            return false;
        }
        if (str_starts_with((string) $transaction->account_reference, 'SWIM-')) {
            return false;
        }
        if (! $transaction->student_id) {
            return false;
        }

        $alloc = self::allocationsForStudent((int) $transaction->student_id, (float) $transaction->amount);
        if (! $alloc) {
            return false;
        }

        $transaction->is_shared = true;
        $transaction->shared_allocations = $alloc;
        $transaction->invoice_id = null;
        $transaction->save();

        Log::info('Promoted STK payment to family sibling split', [
            'transaction_id' => $transaction->id,
            'student_id' => $transaction->student_id,
            'amount' => $transaction->amount,
            'allocations' => $alloc,
        ]);

        return true;
    }
}

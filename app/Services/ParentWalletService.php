<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ParentInfo;
use App\Models\ParentWallet;
use App\Models\ParentWalletLedger;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParentWalletService
{
    public function __construct(protected PaymentAllocationService $allocationService) {}

    public function getOrCreate(int $parentInfoId): ParentWallet
    {
        return ParentWallet::getOrCreateForParent($parentInfoId);
    }

    /**
     * Credit a successful M-Pesa (or other) deposit, then auto-apply to due invoices.
     *
     * @return array{wallet: ParentWallet, credited: float, applied_to_fees: float, remaining: float}
     */
    public function creditDeposit(
        int $parentInfoId,
        float $amount,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $meta = null,
        ?int $createdBy = null
    ): array {
        return DB::transaction(function () use ($parentInfoId, $amount, $referenceType, $referenceId, $meta, $createdBy) {
            $amount = round($amount, 2);
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Deposit amount must be greater than zero.');
            }

            $wallet = ParentWallet::getOrCreateForParent($parentInfoId);
            $wallet = ParentWallet::whereKey($wallet->id)->lockForUpdate()->first();

            $newBalance = round((float) $wallet->balance + $amount, 2);
            $wallet->update([
                'balance' => $newBalance,
                'total_credited' => round((float) $wallet->total_credited + $amount, 2),
                'last_transaction_at' => now(),
            ]);

            ParentWalletLedger::create([
                'parent_wallet_id' => $wallet->id,
                'type' => ParentWalletLedger::TYPE_DEPOSIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta,
                'created_by' => $createdBy,
            ]);

            $applied = $this->allocateDueFeesForFamily($wallet->fresh(), $createdBy);

            return [
                'wallet' => $wallet->fresh(),
                'credited' => $amount,
                'applied_to_fees' => $applied,
                'remaining' => (float) $wallet->fresh()->balance,
            ];
        });
    }

    /**
     * Apply wallet balance to due invoices across children (oldest due first).
     */
    public function allocateDueFeesForFamily(ParentWallet $wallet, ?int $createdBy = null): float
    {
        $students = Student::where('parent_id', $wallet->parent_info_id)
            ->where('archive', 0)
            ->orderBy('id')
            ->get();

        $appliedTotal = 0.0;

        foreach ($students as $student) {
            $due = StudentBalanceService::getTotalOutstandingBalance($student, true);
            if ($due <= 0) {
                continue;
            }

            $wallet->refresh();
            $available = (float) $wallet->balance;
            if ($available <= 0) {
                break;
            }

            $toApply = round(min($available, $due), 2);
            if ($toApply <= 0) {
                continue;
            }

            $appliedTotal += $this->payStudentFeesFromWallet($wallet, $student, $toApply, null, $createdBy, true);
        }

        return round($appliedTotal, 2);
    }

    /**
     * Spend wallet balance against a specific invoice (or auto-allocate to student).
     */
    public function payInvoiceFromWallet(
        ParentWallet $wallet,
        Invoice $invoice,
        float $amount,
        ?int $createdBy = null
    ): Payment {
        $student = Student::findOrFail($invoice->student_id);
        if ((int) $student->parent_id !== (int) $wallet->parent_info_id) {
            throw new \InvalidArgumentException('Invoice does not belong to this family wallet.');
        }

        return $this->createWalletPaymentAndAllocate(
            $wallet,
            $student,
            $amount,
            $invoice,
            $createdBy,
            false
        );
    }

    public function payStudentFeesFromWallet(
        ParentWallet $wallet,
        Student $student,
        float $amount,
        ?Invoice $invoice = null,
        ?int $createdBy = null,
        bool $dueOnly = false
    ): float {
        if ((int) $student->parent_id !== (int) $wallet->parent_info_id) {
            throw new \InvalidArgumentException('Student does not belong to this family wallet.');
        }

        $payment = $this->createWalletPaymentAndAllocate(
            $wallet,
            $student,
            $amount,
            $invoice,
            $createdBy,
            $dueOnly
        );

        return (float) $payment->amount;
    }

    protected function createWalletPaymentAndAllocate(
        ParentWallet $wallet,
        Student $student,
        float $amount,
        ?Invoice $invoice,
        ?int $createdBy,
        bool $dueOnly
    ): Payment {
        return DB::transaction(function () use ($wallet, $student, $amount, $invoice, $createdBy, $dueOnly) {
            $amount = round($amount, 2);
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Amount must be greater than zero.');
            }

            $wallet = ParentWallet::whereKey($wallet->id)->lockForUpdate()->first();
            if (! $wallet->hasSufficientBalance($amount)) {
                throw new \InvalidArgumentException('Insufficient wallet balance.');
            }

            $method = PaymentMethod::query()
                ->where(function ($q) {
                    $q->where('slug', 'wallet')
                        ->orWhere('code', 'wallet')
                        ->orWhere('name', 'like', '%Wallet%');
                })
                ->first();

            $payment = Payment::create([
                'student_id' => $student->id,
                'invoice_id' => $invoice?->id,
                'family_id' => $student->family_id,
                'amount' => $amount,
                'payment_method_id' => $method?->id,
                'payment_method' => 'wallet',
                'payment_channel' => 'parent_wallet',
                'transaction_code' => 'WALLET-'.strtoupper(uniqid()),
                'payer_name' => optional(ParentInfo::find($wallet->parent_info_id))->primary_contact_name
                    ?? 'Parent wallet',
                'payer_type' => 'parent',
                'narration' => $dueOnly
                    ? 'Wallet auto-apply to due fees'
                    : 'Payment from parent wallet',
                'payment_date' => now(),
                'receipt_date' => now(),
                'status' => 'approved',
                'created_by' => $createdBy,
            ]);

            $newBalance = round((float) $wallet->balance - $amount, 2);
            $wallet->update([
                'balance' => $newBalance,
                'total_debited' => round((float) $wallet->total_debited + $amount, 2),
                'last_transaction_at' => now(),
            ]);

            ParentWalletLedger::create([
                'parent_wallet_id' => $wallet->id,
                'type' => $dueOnly ? ParentWalletLedger::TYPE_FEE_ALLOCATION : ParentWalletLedger::TYPE_SPEND,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'meta' => [
                    'student_id' => $student->id,
                    'invoice_id' => $invoice?->id,
                    'due_only' => $dueOnly,
                ],
                'created_by' => $createdBy,
            ]);

            try {
                if ($invoice) {
                    $this->allocationService->allocateToInvoice($payment, $invoice);
                } else {
                    $this->allocationService->autoAllocate($payment, $student->id);
                }
            } catch (\Throwable $e) {
                Log::error('Parent wallet fee allocation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            return $payment->fresh();
        });
    }
}

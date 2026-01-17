<?php

namespace App\Observers;

use App\Models\OptionalFee;
use App\Services\SwimmingWalletService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OptionalFeeObserver
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle the OptionalFee "created" event.
     */
    public function created(OptionalFee $optionalFee): void
    {
        $this->handleBillingChange($optionalFee, 'created');
    }

    /**
     * Handle the OptionalFee "updated" event.
     */
    public function updated(OptionalFee $optionalFee): void
    {
        // Check if status changed from non-billed to billed, or vice versa
        if ($optionalFee->wasChanged('status') || $optionalFee->wasChanged('votehead_id')) {
            $this->handleBillingChange($optionalFee, 'updated');
        }
    }

    /**
     * Handle the OptionalFee "deleted" event.
     */
    public function deleted(OptionalFee $optionalFee): void
    {
        $this->handleUnbilling($optionalFee, 'deleted');
    }

    /**
     * Handle billing change (billed or unbilled)
     */
    protected function handleBillingChange(OptionalFee $optionalFee, string $action): void
    {
        // Check if this is a swimming optional fee
        if (!$this->isSwimmingVotehead($optionalFee->votehead_id)) {
            return;
        }

        $student = $optionalFee->student;
        if (!$student) {
            return;
        }

        try {
            DB::transaction(function () use ($optionalFee, $student, $action) {
                if ($optionalFee->status === 'billed') {
                    // Check if wallet was already credited for this optional fee
                    $ledgerExists = \App\Models\SwimmingLedger::where('student_id', $student->id)
                        ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                        ->where('source_id', $optionalFee->id)
                        ->exists();

                    if (!$ledgerExists) {
                        // Credit wallet with the optional fee amount (typically 1200)
                        $this->walletService->creditFromOptionalFee(
                            $student,
                            $optionalFee,
                            (float) $optionalFee->amount,
                            "Swimming termly fee for Term {$optionalFee->term} (automatic)"
                        );

                        Log::info('Automatically credited swimming wallet from optional fee billing', [
                            'optional_fee_id' => $optionalFee->id,
                            'student_id' => $student->id,
                            'amount' => $optionalFee->amount,
                        ]);
                    }
                } else {
                    // Status changed to non-billed (exempt or removed)
                    $this->handleUnbilling($optionalFee, $action);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to automatically credit/debit swimming wallet from optional fee', [
                'optional_fee_id' => $optionalFee->id,
                'student_id' => $optionalFee->student_id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle unbilling - debit wallet and convert credits to daily rate
     */
    protected function handleUnbilling(OptionalFee $optionalFee, string $action): void
    {
        // Check if this is a swimming optional fee
        if (!$this->isSwimmingVotehead($optionalFee->votehead_id)) {
            return;
        }

        $student = $optionalFee->student;
        if (!$student) {
            return;
        }

        try {
            DB::transaction(function () use ($optionalFee, $student, $action) {
                // Find ledger entry for this optional fee credit
                $ledger = \App\Models\SwimmingLedger::where('student_id', $student->id)
                    ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                    ->where('source_id', $optionalFee->id)
                    ->first();

                if ($ledger) {
                    $creditAmount = $ledger->amount;
                    
                    // Debit the wallet by the same amount that was credited
                    // This effectively reverses the credit
                    $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($student->id);
                    $wallet->refresh();
                    
                    $oldBalance = $wallet->balance;
                    $newBalance = $oldBalance - $creditAmount;
                    
                    // Update wallet
                    $wallet->update([
                        'balance' => $newBalance,
                        'total_debited' => $wallet->total_debited + $creditAmount,
                        'last_transaction_at' => now(),
                    ]);
                    
                    // Create ledger entry to reverse the credit
                    \App\Models\SwimmingLedger::create([
                        'student_id' => $student->id,
                        'type' => \App\Models\SwimmingLedger::TYPE_DEBIT,
                        'amount' => $creditAmount,
                        'balance_after' => $newBalance,
                        'source' => \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE,
                        'source_id' => $optionalFee->id,
                        'source_type' => OptionalFee::class,
                        'description' => "Swimming termly fee unbilied - credit reversed (Term {$optionalFee->term})",
                        'created_by' => auth()->id(),
                    ]);

                    // Mark the original credit ledger entry as reversed
                    $ledger->update([
                        'description' => ($ledger->description ?? '') . ' (Reversed - unbilled)',
                    ]);

                    Log::info('Automatically debited swimming wallet from optional fee unbilling', [
                        'optional_fee_id' => $optionalFee->id,
                        'student_id' => $student->id,
                        'amount' => $creditAmount,
                        'new_balance' => $newBalance,
                    ]);
                }

                // Note: Converting to daily rate is handled automatically when attendance is marked
                // Students without termly fees get invoiced at daily rate automatically
            });
        } catch (\Exception $e) {
            Log::error('Failed to automatically debit swimming wallet from optional fee unbilling', [
                'optional_fee_id' => $optionalFee->id,
                'student_id' => $optionalFee->student_id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if votehead is a swimming votehead
     */
    protected function isSwimmingVotehead(?int $voteheadId): bool
    {
        if (!$voteheadId) {
            return false;
        }

        $votehead = \App\Models\Votehead::find($voteheadId);
        if (!$votehead) {
            return false;
        }

        // Check if votehead name or code contains "swimming"
        $isSwimming = stripos($votehead->name ?? '', 'swimming') !== false ||
                     stripos($votehead->code ?? '', 'SWIM') !== false;

        // Must also be optional (not mandatory)
        return $isSwimming && !$votehead->is_mandatory;
    }
}

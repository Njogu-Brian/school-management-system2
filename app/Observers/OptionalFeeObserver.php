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
        if ($optionalFee->wasChanged('status')) {
            $oldStatus = $optionalFee->getOriginal('status');
            $newStatus = $optionalFee->status;
            
            // If changed from billed to non-billed (unbilled), handle unbilling
            if ($oldStatus === 'billed' && $newStatus !== 'billed') {
                $this->handleUnbilling($optionalFee, 'updated');
            }
            // If changed to billed, handle billing
            elseif ($newStatus === 'billed' && $oldStatus !== 'billed') {
                $this->handleBillingChange($optionalFee, 'updated');
            }
        }
        
        // If votehead changed, we need to check if it's still swimming
        if ($optionalFee->wasChanged('votehead_id')) {
            $oldVoteheadId = $optionalFee->getOriginal('votehead_id');
            // If old votehead was swimming and status was billed, handle unbilling
            if ($optionalFee->getOriginal('status') === 'billed' && $this->isSwimmingVotehead($oldVoteheadId)) {
                // Create a temporary optionalFee with old votehead for unbilling
                $tempOptionalFee = clone $optionalFee;
                $tempOptionalFee->votehead_id = $oldVoteheadId;
                $this->handleUnbilling($tempOptionalFee, 'updated');
            }
            // If new votehead is swimming and status is billed, handle billing
            if ($optionalFee->status === 'billed' && $this->isSwimmingVotehead($optionalFee->votehead_id)) {
                $this->handleBillingChange($optionalFee, 'updated');
            }
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
     * Handle billing change (billed or unbilled).
     * NOTE: Swimming per term is ONLY set via OptionalFee (manual or import). A student's
     * daily-attendance (wallet) balance must NEVER auto-create or activate swimming per term billing.
     */
    protected function handleBillingChange(OptionalFee $optionalFee, string $action): void
    {
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
                            ->where('type', \App\Models\SwimmingLedger::TYPE_CREDIT)
                            ->exists();

                        if (!$ledgerExists && $optionalFee->amount > 0) {
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

        // Get student ID - try relationship first, fallback to attribute
        $studentId = $optionalFee->student_id ?? ($optionalFee->student ? $optionalFee->student->id : null);
        if (!$studentId) {
            Log::warning('Cannot handle unbilling: student_id not available', [
                'optional_fee_id' => $optionalFee->id,
                'action' => $action,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($optionalFee, $studentId, $action) {
                // First, try to find ledger entry by source_id (most direct match)
                $ledger = \App\Models\SwimmingLedger::where('student_id', $studentId)
                    ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                    ->where('source_id', $optionalFee->id)
                    ->where('type', \App\Models\SwimmingLedger::TYPE_CREDIT)
                    ->whereNull('swimming_attendance_id') // Only reverse optional fee credits, not attendance debits
                    ->first();

                // If not found by source_id, try to find by matching student, votehead, term, and year
                // This handles cases where the OptionalFee ID might not match exactly
                // We'll match by finding ledger entries that haven't been reversed and match the amount/term pattern
                if (!$ledger && $optionalFee->votehead_id && $optionalFee->term && $optionalFee->amount > 0) {
                    // Find credit ledger entries for this student from optional fees that match the amount
                    // and have a description indicating the same term
                    $potentialLedgers = \App\Models\SwimmingLedger::where('student_id', $studentId)
                        ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                        ->where('type', \App\Models\SwimmingLedger::TYPE_CREDIT)
                        ->where('amount', $optionalFee->amount)
                        ->whereNull('swimming_attendance_id')
                        ->whereRaw("description NOT LIKE ?", ["%Reversed%"])
                        ->get();

                    // Try to match by term in description
                    foreach ($potentialLedgers as $potentialLedger) {
                        if (stripos($potentialLedger->description ?? '', "Term {$optionalFee->term}") !== false) {
                            $ledger = $potentialLedger;
                            break;
                        }
                    }
                }


                if ($ledger) {
                    $creditAmount = $ledger->amount;
                    
                    // Check if this credit has already been reversed
                    $alreadyReversed = \App\Models\SwimmingLedger::where('student_id', $studentId)
                        ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                        ->where('source_id', $optionalFee->id)
                        ->where('type', \App\Models\SwimmingLedger::TYPE_DEBIT)
                        ->whereRaw("description LIKE ?", ["%credit reversed%"])
                        ->exists();

                    if ($alreadyReversed) {
                        Log::info('Swimming wallet credit already reversed for optional fee', [
                            'optional_fee_id' => $optionalFee->id,
                            'student_id' => $studentId,
                            'ledger_id' => $ledger->id,
                        ]);
                        return;
                    }
                    
                    // Debit the wallet by the same amount that was credited
                    // This effectively reverses the credit
                    $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($studentId);
                    $wallet->refresh();
                    
                    $oldBalance = $wallet->balance;
                    $newBalance = max(0, $oldBalance - $creditAmount); // Ensure balance doesn't go negative
                    
                    // Update wallet
                    $wallet->update([
                        'balance' => $newBalance,
                        'total_debited' => $wallet->total_debited + $creditAmount,
                        'last_transaction_at' => now(),
                    ]);
                    
                    // Create ledger entry to reverse the credit
                    \App\Models\SwimmingLedger::create([
                        'student_id' => $studentId,
                        'type' => \App\Models\SwimmingLedger::TYPE_DEBIT,
                        'amount' => $creditAmount,
                        'balance_after' => $newBalance,
                        'source' => \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE,
                        'source_id' => $optionalFee->id,
                        'source_type' => OptionalFee::class,
                        'description' => "Swimming termly fee unbilled - credit reversed (Term {$optionalFee->term})",
                        'created_by' => auth()->id(),
                    ]);

                    // Mark the original credit ledger entry as reversed
                    $ledger->update([
                        'description' => ($ledger->description ?? '') . ' (Reversed - unbilled)',
                    ]);

                    Log::info('Automatically debited swimming wallet from optional fee unbilling', [
                        'optional_fee_id' => $optionalFee->id,
                        'student_id' => $studentId,
                        'amount' => $creditAmount,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance,
                        'ledger_id' => $ledger->id,
                    ]);
                } else {
                    Log::warning('Could not find swimming ledger entry to reverse for optional fee', [
                        'optional_fee_id' => $optionalFee->id,
                        'student_id' => $studentId,
                        'votehead_id' => $optionalFee->votehead_id,
                        'term' => $optionalFee->term,
                        'year' => $optionalFee->year,
                        'amount' => $optionalFee->amount,
                    ]);
                }

                // Note: Converting to daily rate is handled automatically when attendance is marked
                // Students without termly fees get invoiced at daily rate automatically
            });
        } catch (\Exception $e) {
            Log::error('Failed to automatically debit swimming wallet from optional fee unbilling', [
                'optional_fee_id' => $optionalFee->id,
                'student_id' => $studentId,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

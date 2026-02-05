<?php

namespace App\Services;

use App\Models\{
    Payment, InvoiceItem, PaymentAllocation, Student, Family, User,
    FeePaymentPlan, FeePaymentPlanInstallment, FeeReminder, OptionalFee
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Payment Allocation Service
 * Handles allocation of payments to invoice items, overpayments, and sibling sharing
 */
class PaymentAllocationService
{
    /**
     * Allocate payment to invoice items
     */
    public function allocatePayment(
        Payment $payment,
        array $allocations // [['invoice_item_id' => X, 'amount' => Y], ...]
    ): Payment {
        // Prevent swimming payments from being allocated to invoice items
        // Swimming payments should only credit wallets, not invoice items
        if (strpos($payment->receipt_number ?? '', 'SWIM-') === 0) {
            throw new \Exception('Swimming payments cannot be allocated to invoice items. They must be credited to swimming wallets only.');
        }
        
        return DB::transaction(function () use ($payment, $allocations) {
            $currentAllocated = (float) $payment->allocations()->sum('amount');
            $totalAllocated = 0;
            
            foreach ($allocations as $allocation) {
                $invoiceItem = InvoiceItem::findOrFail($allocation['invoice_item_id']);
                $amount = (float)$allocation['amount'];
                
                // Validate allocation doesn't exceed payment
                if ($currentAllocated + $totalAllocated + $amount > $payment->amount) {
                    throw new \Exception('Allocation exceeds payment amount.');
                }
                
                // Validate allocation doesn't exceed invoice item balance
                $itemBalance = $invoiceItem->getBalance();
                if ($amount > $itemBalance) {
                    throw new \Exception("Allocation amount exceeds invoice item balance.");
                }
                
                // Create allocation
                $paymentAllocation = PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'amount' => $amount,
                    'allocated_by' => auth()->id(),
                    'allocated_at' => now(),
                ]);
                
                // Check if this is a swimming optional fee payment and credit wallet
                $this->handleSwimmingOptionalFeePayment($invoiceItem, $payment, $amount);
                
                $totalAllocated += $amount;
            }
            
            // Update payment allocation totals
            $payment->updateAllocationTotals();
            
            // Recalculate invoices
            foreach ($allocations as $allocation) {
                $invoiceItem = InvoiceItem::find($allocation['invoice_item_id']);
                if ($invoiceItem) {
                    InvoiceService::recalc($invoiceItem->invoice);
                }
            }
            
            // Log audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'allocated',
                    $payment,
                    ['allocated_amount' => $payment->allocated_amount ?? 0],
                    ['allocated_amount' => $totalAllocated],
                    ['payment_allocation']
                );
            }
            
            return $payment->fresh();
        });
    }
    
    /**
     * Auto-allocate payment to invoice items (FIFO).
     * Optionally prefer a specific term so that term's items are allocated first (e.g. for fees comparison).
     *
     * @param int|null $preferTermId If set, invoice items belonging to this term are allocated first, then by issued_date
     */
    public function autoAllocate(Payment $payment, ?int $studentId = null, ?int $preferTermId = null): Payment
    {
        // Prevent swimming payments from being auto-allocated to invoice items
        // Swimming payments should only credit wallets, not invoice items
        if (strpos($payment->receipt_number ?? '', 'SWIM-') === 0) {
            // Swimming payment - mark as allocated since it goes to wallet
            $payment->update([
                'allocated_amount' => $payment->amount,
                'unallocated_amount' => 0,
            ]);
            return $payment->fresh();
        }
        
        $studentId = $studentId ?? $payment->student_id;
        
        // Get unpaid invoice items for student
        $invoiceItems = InvoiceItem::whereHas('invoice', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })
        ->where('status', 'active')
        ->with(['invoice'])
        ->get()
        ->filter(function ($item) {
            return $item->getBalance() > 0;
        });

        // Sort: if preferTermId set, put that term's items first, then by issued_date
        if ($preferTermId !== null) {
            $invoiceItems = $invoiceItems->sortBy(function ($item) use ($preferTermId) {
                $termId = $item->invoice->term_id ?? -1;
                $issued = $item->invoice->issued_date?->format('Y-m-d') ?? '9999-99-99';
                return [$termId == $preferTermId ? 0 : 1, $issued];
            })->values();
        } else {
            $invoiceItems = $invoiceItems->sortBy('invoice.issued_date')->values();
        }
        
        $remaining = $payment->amount;
        $allocations = [];
        
        foreach ($invoiceItems as $item) {
            if ($remaining <= 0) break;
            
            $balance = $item->getBalance();
            $allocateAmount = min($remaining, $balance);
            
            if ($allocateAmount > 0) {
                $allocations[] = [
                    'invoice_item_id' => $item->id,
                    'amount' => $allocateAmount,
                ];
                $remaining -= $allocateAmount;
            }
        }
        
        if (!empty($allocations)) {
            $this->allocatePayment($payment, $allocations);
        } else {
            // No items to allocate - this is an overpayment
            $payment->update([
                'allocated_amount' => 0,
                'unallocated_amount' => $payment->amount,
            ]);
        }
        
        return $payment->fresh();
    }
    
    /**
     * Share payment across siblings
     */
    public function sharePaymentAcrossSiblings(
        Payment $payment,
        array $siblingAllocations // [['student_id' => X, 'allocations' => [...]], ...]
    ): Payment {
        $totalAllocationAmount = 0;
        
        // Validate total doesn't exceed payment
        foreach ($siblingAllocations as $siblingAlloc) {
            foreach ($siblingAlloc['allocations'] as $alloc) {
                $totalAllocationAmount += $alloc['amount'];
            }
        }
        
        if ($totalAllocationAmount > $payment->amount) {
            throw new \Exception('Total sibling allocations exceed payment amount.');
        }
        
        return DB::transaction(function () use ($payment, $siblingAllocations) {
            foreach ($siblingAllocations as $siblingAlloc) {
                $studentId = $siblingAlloc['student_id'];
                $allocations = $siblingAlloc['allocations'];
                
                // Generate unique receipt number for this sibling payment
                $maxAttempts = 10;
                $attempt = 0;
                do {
                    $receiptNumber = \App\Services\DocumentNumberService::generateReceipt();
                    $exists = \App\Models\Payment::where('receipt_number', $receiptNumber)->exists();
                    $attempt++;
                    
                    if ($exists && $attempt < $maxAttempts) {
                        // Wait a tiny bit and try again (handles race conditions)
                        usleep(10000); // 0.01 seconds
                    }
                } while ($exists && $attempt < $maxAttempts);
                
                if ($exists) {
                    // If still exists after max attempts, append student ID to make it unique
                    $receiptNumber = $receiptNumber . '-S' . $studentId;
                    
                    \Log::warning('Receipt number collision after max attempts, using modified number', [
                        'modified_receipt' => $receiptNumber,
                        'student_id' => $studentId,
                    ]);
                }
                
                // Create sibling payment or use existing
                // Use same transaction code for all siblings, but unique receipt numbers
                $siblingPayment = Payment::firstOrCreate(
                    [
                        'transaction_code' => $payment->transaction_code, // Same transaction code for all siblings
                        'student_id' => $studentId,
                    ],
                    [
                        'receipt_number' => $receiptNumber, // Unique receipt number for each sibling
                        'family_id' => $payment->family_id,
                        'amount' => array_sum(array_column($allocations, 'amount')),
                        'payment_method' => $payment->payment_method,
                        'payment_method_id' => $payment->payment_method_id,
                        'payment_date' => $payment->payment_date,
                        'bank_account_id' => $payment->bank_account_id,
                        'payer_name' => $payment->payer_name,
                        'payer_type' => $payment->payer_type,
                        'reference' => $payment->reference,
                        'narration' => $payment->narration . ' (Shared from payment ' . $payment->transaction_code . ')',
                    ]
                );
                
                // Allocate to sibling's invoice items
                $this->allocatePayment($siblingPayment, $allocations);
            }
            
            // Mark original payment as allocated
            $payment->updateAllocationTotals();
            
            return $payment->fresh();
        });
    }
    
    /**
     * Handle overpayment (carry forward)
     */
    public function handleOverpayment(Payment $payment): Payment
    {
        $overpayment = $payment->calculateUnallocatedAmount();
        
        if ($overpayment > 0) {
            $payment->update([
                'unallocated_amount' => $overpayment,
            ]);
            
            // Create credit note or carry forward to next term
            // Implementation depends on business rules
        }
        
        return $payment->fresh();
    }
    
    /**
     * Get allocation suggestions for payment
     */
    public function getAllocationSuggestions(Payment $payment): Collection
    {
        $student = $payment->student;
        $family = $student->family;
        
        // Get unpaid invoice items
        $items = InvoiceItem::whereHas('invoice', function ($q) use ($student, $family) {
            if ($payment->family_id && $family) {
                // Include all siblings' invoices
                $q->whereHas('student', function ($sq) use ($family) {
                    $sq->where('family_id', $family->id);
                });
            } else {
                $q->where('student_id', $student->id);
            }
        })
        ->where('status', 'active')
        ->with(['invoice.student', 'votehead'])
        ->get()
        ->filter(function ($item) {
            return $item->getBalance() > 0;
        })
        ->sortBy('invoice.issued_date');
        
        return $items->map(function ($item) {
            return [
                'invoice_item_id' => $item->id,
                'invoice_id' => $item->invoice_id,
                'invoice_number' => $item->invoice->invoice_number,
                'student_name' => trim($item->invoice->student->first_name . ' ' . $item->invoice->student->last_name) ?? 'Unknown',
                'votehead_name' => $item->votehead->name ?? 'Unknown',
                'amount' => $item->amount,
                'balance' => $item->getBalance(),
                'due_date' => $item->invoice->due_date,
                'suggested_allocation' => min($item->getBalance(), $item->invoice->balance),
            ];
        });
    }

    /**
     * Allocate payment to payment plan installments (oldest unpaid first).
     * Optionally limit the amount applied (e.g. when splitting one payment across multiple plans).
     * Returns the amount actually applied to installments.
     */
    public function allocatePaymentToInstallments(Payment $payment, FeePaymentPlan $paymentPlan, ?float $maxAmount = null): float
    {
        $limit = $maxAmount !== null ? min((float) $payment->amount, $maxAmount) : (float) $payment->amount;
        $amountApplied = 0;

        DB::transaction(function () use ($payment, $paymentPlan, $limit, &$amountApplied) {
            $remainingAmount = $limit;

            $unpaidInstallments = FeePaymentPlanInstallment::where('payment_plan_id', $paymentPlan->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->get();

            foreach ($unpaidInstallments as $installment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $outstanding = $installment->amount - $installment->paid_amount;
                if ($outstanding <= 0) {
                    continue;
                }

                $amountToApply = min($remainingAmount, $outstanding);
                $newPaidAmount = $installment->paid_amount + $amountToApply;

                $installment->update([
                    'paid_amount' => $newPaidAmount,
                    'paid_date' => $installment->paid_date ?? now()->toDateString(),
                    'status' => $newPaidAmount >= $installment->amount ? 'paid' : 'partial',
                    'payment_id' => $payment->id,
                ]);

                $remainingAmount -= $amountToApply;
                $amountApplied += $amountToApply;

                if ($installment->status === 'paid') {
                    FeeReminder::where('payment_plan_installment_id', $installment->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);
                }
            }

            $paymentPlan->refresh();
            $totalPaid = $paymentPlan->installments()->sum('paid_amount');

            if ($totalPaid >= $paymentPlan->total_amount) {
                $paymentPlan->update(['status' => 'completed']);
                FeeReminder::where('payment_plan_id', $paymentPlan->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }
        });

        return $amountApplied;
    }

    /**
     * Auto-allocate payment (try installments first, then invoice items)
     */
    public function autoAllocateWithInstallments(Payment $payment, ?int $studentId = null): Payment
    {
        $studentId = $studentId ?? $payment->student_id;

        $activePaymentPlans = FeePaymentPlan::where('student_id', $studentId)
            ->whereIn('status', ['active', 'compliant', 'overdue'])
            ->with('installments')
            ->orderBy('start_date')
            ->get();

        $remainingAmount = (float) $payment->amount;

        foreach ($activePaymentPlans as $plan) {
            if ($remainingAmount <= 0) {
                break;
            }
            $amountApplied = $this->allocatePaymentToInstallments($payment, $plan, $remainingAmount);
            $remainingAmount -= $amountApplied;
        }

        if ($remainingAmount <= 0) {
            return $payment->fresh();
        }

        return $this->autoAllocate($payment, $studentId);
    }

    /**
     * Handle swimming optional fee payment - credit wallet when paid
     */
    protected function handleSwimmingOptionalFeePayment(InvoiceItem $invoiceItem, Payment $payment, float $amount): void
    {
        try {
            // Check if invoice item is fully paid
            if ($invoiceItem->getBalance() > 0) {
                return; // Not fully paid yet
            }

            // Check if this is a swimming votehead
            $votehead = $invoiceItem->votehead;
            if (!$votehead) {
                return;
            }

            $isSwimmingVotehead = false;
            if (stripos($votehead->name, 'swimming') !== false || 
                stripos($votehead->code ?? '', 'SWIM') !== false) {
                $isSwimmingVotehead = true;
            }

            if (!$isSwimmingVotehead || $votehead->is_mandatory) {
                return; // Not a swimming optional fee
            }

            // Find the optional fee for this student and votehead
            $student = $payment->student;
            if (!$student) {
                return;
            }

            $invoice = $invoiceItem->invoice;
            $year = $invoice->year ?? (int) setting('current_year', date('Y'));
            $term = $invoice->term ?? (int) setting('current_term', 1);

            $optionalFee = OptionalFee::where('student_id', $student->id)
                ->where('votehead_id', $votehead->id)
                ->where('year', $year)
                ->where('term', $term)
                ->where('status', 'billed')
                ->first();

            if (!$optionalFee) {
                return; // Optional fee not found
            }

            // Credit wallet with the full optional fee amount (only once)
            // Check if wallet was already credited for this optional fee
            $walletService = app(\App\Services\SwimmingWalletService::class);
            $ledgerExists = \App\Models\SwimmingLedger::where('student_id', $student->id)
                ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                ->where('source_id', $optionalFee->id)
                ->exists();

            if (!$ledgerExists) {
                // Credit wallet with the full optional fee amount
                $walletService->creditFromOptionalFee(
                    $student,
                    $optionalFee,
                    (float) $optionalFee->amount,
                    "Swimming termly fee payment for Term {$optionalFee->term}"
                );

                Log::info('Swimming wallet credited from termly fee payment', [
                    'student_id' => $student->id,
                    'optional_fee_id' => $optionalFee->id,
                    'amount' => $optionalFee->amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to credit swimming wallet from optional fee payment', [
                'invoice_item_id' => $invoiceItem->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


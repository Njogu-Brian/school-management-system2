<?php

namespace App\Services;

use App\Models\{
    Payment, InvoiceItem, PaymentAllocation, Student, Family, User,
    FeePaymentPlan, FeePaymentPlanInstallment, FeeReminder
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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
        return DB::transaction(function () use ($payment, $allocations) {
            $totalAllocated = 0;
            
            foreach ($allocations as $allocation) {
                $invoiceItem = InvoiceItem::findOrFail($allocation['invoice_item_id']);
                $amount = (float)$allocation['amount'];
                
                // Validate allocation doesn't exceed payment
                if ($totalAllocated + $amount > $payment->amount) {
                    throw new \Exception('Allocation exceeds payment amount.');
                }
                
                // Validate allocation doesn't exceed invoice item balance
                $itemBalance = $invoiceItem->getBalance();
                if ($amount > $itemBalance) {
                    throw new \Exception("Allocation amount exceeds invoice item balance.");
                }
                
                // Create allocation
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'amount' => $amount,
                    'allocated_by' => auth()->id(),
                    'allocated_at' => now(),
                ]);
                
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
     * Auto-allocate payment to invoice items (FIFO)
     */
    public function autoAllocate(Payment $payment, ?int $studentId = null): Payment
    {
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
        })
        ->sortBy('invoice.issued_date');
        
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
     * Allocate payment to payment plan installments (oldest unpaid first)
     * Applies payment to oldest unpaid installment, carrying surplus to next
     */
    public function allocatePaymentToInstallments(Payment $payment, FeePaymentPlan $paymentPlan): Payment
    {
        return DB::transaction(function () use ($payment, $paymentPlan) {
            $remainingAmount = $payment->amount;
            
            // Get unpaid installments ordered by due date (oldest first)
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
                    continue; // Skip fully paid installments
                }
                
                $amountToApply = min($remainingAmount, $outstanding);
                $newPaidAmount = $installment->paid_amount + $amountToApply;
                
                // Update installment
                $installment->update([
                    'paid_amount' => $newPaidAmount,
                    'paid_date' => $installment->paid_date ?? now()->toDateString(),
                    'status' => $newPaidAmount >= $installment->amount ? 'paid' : 'partial',
                    'payment_id' => $payment->id,
                ]);
                
                $remainingAmount -= $amountToApply;
                
                // If installment is now paid, cancel any pending reminders for it
                if ($installment->status === 'paid') {
                    FeeReminder::where('payment_plan_installment_id', $installment->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);
                }
            }
            
            // Refresh payment plan to check if it should be closed
            $paymentPlan->refresh();
            $totalPaid = $paymentPlan->installments()->sum('paid_amount');
            
            if ($totalPaid >= $paymentPlan->total_amount) {
                // All installments are paid - close the plan
                $paymentPlan->update(['status' => 'completed']);
                
                // Cancel all pending reminders for this plan
                FeeReminder::where('payment_plan_id', $paymentPlan->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }
            
            return $payment->fresh();
        });
    }

    /**
     * Auto-allocate payment (try installments first, then invoice items)
     */
    public function autoAllocateWithInstallments(Payment $payment, ?int $studentId = null): Payment
    {
        $studentId = $studentId ?? $payment->student_id;
        
        // First, try to allocate to payment plan installments
        $activePaymentPlans = FeePaymentPlan::where('student_id', $studentId)
            ->whereIn('status', ['active', 'compliant', 'overdue'])
            ->with('installments')
            ->get();
        
        $remainingAmount = $payment->amount;
        
        foreach ($activePaymentPlans as $plan) {
            if ($remainingAmount <= 0) {
                break;
            }
            
            // Create a temporary payment record for allocation tracking
            // In practice, you might want to track installment allocations separately
            $this->allocatePaymentToInstallments($payment, $plan);
            
            // Recalculate remaining (in a real implementation, track allocations)
            $plan->refresh();
            $totalPaid = $plan->installments()->sum('paid_amount');
            $planBalance = $plan->total_amount - $totalPaid;
            $remainingAmount = max(0, $remainingAmount - $planBalance);
        }
        
        // If payment is fully allocated to installments, return
        if ($remainingAmount <= 0) {
            return $payment->fresh();
        }
        
        // Otherwise, allocate remaining to invoice items
        // Create a new payment record for remaining amount or adjust allocation logic
        // For now, fall back to standard auto-allocation
        return $this->autoAllocate($payment, $studentId);
    }
}


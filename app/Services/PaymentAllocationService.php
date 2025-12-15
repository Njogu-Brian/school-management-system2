<?php

namespace App\Services;

use App\Models\{
    Payment, InvoiceItem, PaymentAllocation, Student, Family, User
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
                
                // Create sibling payment or use existing
                $siblingPayment = Payment::firstOrCreate(
                    [
                        'transaction_code' => $payment->transaction_code . '-S' . $studentId,
                        'student_id' => $studentId,
                    ],
                    [
                        'receipt_number' => $payment->receipt_number . '-S' . $studentId,
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
}


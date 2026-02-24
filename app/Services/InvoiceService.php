<?php

namespace App\Services;

use App\Models\{
    Invoice, InvoiceItem, CreditNote, DebitNote, Votehead, Student, Payment
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Ensure invoice exists for student/year/term
     * Handles race conditions by catching unique constraint violations
     */
    public static function ensure(int $studentId, int $year, int $term): Invoice
    {
        $student = Student::findOrFail($studentId);
        
        // Get academic year and term IDs
        $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
        $termModel = \App\Models\Term::whereHas('academicYear', fn($q) => $q->where('year', $year))
            ->where('name', 'like', "%Term {$term}%")
            ->first();
        
        // First, try to find existing invoice (including soft deleted ones)
        $invoice = Invoice::withTrashed()
            ->where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();
        
        // If found and soft deleted, restore it
        if ($invoice && $invoice->trashed()) {
            $invoice->restore();
            // Update the invoice with current data
            $invoice->update([
                'family_id' => $student->family_id,
                'academic_year_id' => $academicYear->id ?? $invoice->academic_year_id,
                'term_id' => $termModel->id ?? $invoice->term_id,
            ]);
            return $invoice;
        }
        
        // If found and not deleted, return it
        if ($invoice && !$invoice->trashed()) {
            // Update academic_year_id and term_id if they're null (for legacy invoices)
            if (!$invoice->academic_year_id && $academicYear) {
                $invoice->update(['academic_year_id' => $academicYear->id]);
            }
            if (!$invoice->term_id && $termModel) {
                $invoice->update(['term_id' => $termModel->id]);
            }
            return $invoice;
        }
        
        // Try to create new invoice, handling race conditions
        try {
            $invoice = Invoice::create([
                'student_id' => $studentId,
                'year' => $year, // Keep for backward compatibility
                'term' => $term, // Keep for backward compatibility
                'family_id' => $student->family_id,
                'academic_year_id' => $academicYear->id ?? null,
                'term_id' => $termModel->id ?? null,
                'invoice_number' => NumberSeries::invoice(),
                'total' => 0,
                'paid_amount' => 0,
                'balance' => 0,
                'status' => 'unpaid',
                'issued_date' => now(),
            ]);
            
            // Add balance brought forward for first term of 2026 (only if invoice was just created)
            if ($year >= 2026 && $term == 1) {
                self::addBalanceBroughtForward($invoice, $student);
            }
            
            return $invoice;
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (race condition)
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                // Another request created the invoice, fetch it
                $invoice = Invoice::where('student_id', $studentId)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->first();
                
                if ($invoice) {
                    // Update academic_year_id and term_id if they're null
                    if (!$invoice->academic_year_id && $academicYear) {
                        $invoice->update(['academic_year_id' => $academicYear->id]);
                    }
                    if (!$invoice->term_id && $termModel) {
                        $invoice->update(['term_id' => $termModel->id]);
                    }
                    return $invoice;
                }
            }
            
            // Re-throw if it's not a unique constraint violation
            throw $e;
        }
    }
    
    /**
     * Add balance brought forward as an invoice item for the first term of 2026
     */
    public static function addBalanceBroughtForward(Invoice $invoice, Student $student): void
    {
        $balanceBroughtForward = \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
        
        if ($balanceBroughtForward <= 0) {
            return; // No balance to bring forward
        }
        
        // Find or create a votehead for "Balance Brought Forward"
        $votehead = Votehead::firstOrCreate(
            [
                'code' => 'BAL_BF',
            ],
            [
                'name' => 'Balance Brought Forward',
                'is_active' => true,
            ]
        );
        
        // Check if balance brought forward item already exists
        $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('votehead_id', $votehead->id)
            ->where('source', 'balance_brought_forward')
            ->first();
        
            if (!$existingItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $votehead->id,
                'amount' => $balanceBroughtForward,
                'discount_amount' => 0,
                'status' => 'active',
                'source' => 'balance_brought_forward',
                'effective_date' => $invoice->issued_date ?? now(),
            ]);

            // Recalculate invoice to include the new item
            self::recalc($invoice);
            self::allocateUnallocatedPaymentsForStudent($invoice->student_id);
        }
    }

    /**
     * Recalculate invoice totals
     */
    public static function recalc(Invoice $invoice): void
    {
        $invoice->refresh();
        $invoice->recalculate(); // Use model method
    }

    /**
     * Auto-allocate any unallocated payments for a student
     */
    public static function allocateUnallocatedPaymentsForStudent(int $studentId): void
    {
        $payments = Payment::where('student_id', $studentId)
            ->where('reversed', false)
            ->where('receipt_number', 'not like', 'SWIM-%')
            ->where(function ($q) {
                $q->where('unallocated_amount', '>', 0)
                  ->orWhereRaw('amount > COALESCE(allocated_amount, 0)');
            })
            ->get();
        
        if ($payments->isEmpty()) {
            return;
        }
        
        $allocationService = app(\App\Services\PaymentAllocationService::class);
        
        foreach ($payments as $payment) {
            try {
                $allocationService->autoAllocate($payment, $studentId);
            } catch (\Exception $e) {
                Log::warning('Auto-allocation failed after invoice update', [
                    'payment_id' => $payment->id,
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    /**
     * Update invoice item amount with automatic credit/debit note creation
     */
    public static function updateItemAmount(
        InvoiceItem $item,
        float $newAmount,
        string $reason,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($item, $newAmount, $reason, $notes) {
            $oldAmount = $item->amount;
            $difference = $newAmount - $oldAmount;
            
            // Store original amount if not set
            if (!$item->original_amount) {
                $item->original_amount = $oldAmount;
            }
            
            // Update item
            $item->update(['amount' => $newAmount]);
            
            $creditNote = null;
            $debitNote = null;
            
            // Create credit note if amount decreased
            if ($difference < 0) {
                $creditNote = CreditNote::create([
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'amount' => abs($difference),
                    'reason' => $reason,
                    'notes' => $notes ?? "Amount reduced from {$oldAmount} to {$newAmount}",
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);
                
                // Log audit (if AuditLog model exists)
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log('updated', $item, ['amount' => $oldAmount], ['amount' => $newAmount], ['invoice_item_edit']);
                }
            }
            
            // Create debit note if amount increased
            if ($difference > 0) {
                $debitNote = DebitNote::create([
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'amount' => $difference,
                    'reason' => $reason,
                    'notes' => $notes ?? "Amount increased from {$oldAmount} to {$newAmount}",
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);
                
                // Log audit (if AuditLog model exists)
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log('updated', $item, ['amount' => $oldAmount], ['amount' => $newAmount], ['invoice_item_edit']);
                }
            }
            
            // Recalculate invoice
            self::recalc($item->invoice);
            self::allocateUnallocatedPaymentsForStudent($item->invoice->student_id);
            
            return [
                'item' => $item->fresh(),
                'credit_note' => $creditNote,
                'debit_note' => $debitNote,
            ];
        });
    }
    
    /**
     * Apply discount to invoice
     */
    public static function applyDiscount(
        Invoice $invoice,
        float $discountAmount,
        ?int $voteheadId = null,
        string $reason = 'Discount applied'
    ): Invoice {
        return DB::transaction(function () use ($invoice, $discountAmount, $voteheadId, $reason) {
            if ($voteheadId) {
                // Apply to specific invoice item
                $item = $invoice->items()->where('votehead_id', $voteheadId)->first();
                if ($item) {
                    $item->update([
                        'discount_amount' => ($item->discount_amount ?? 0) + $discountAmount,
                    ]);
                }
            } else {
                // Apply to invoice level
                $invoice->increment('discount_amount', $discountAmount);
            }
            
            self::recalc($invoice);
            self::allocateUnallocatedPaymentsForStudent($invoice->student_id);
            
                // Log audit (if AuditLog model exists)
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log('updated', $invoice, 
                        ['discount_amount' => $invoice->discount_amount - $discountAmount],
                        ['discount_amount' => $invoice->discount_amount],
                        ['discount_applied']
                    );
                }
            
            return $invoice->fresh();
        });
    }
}

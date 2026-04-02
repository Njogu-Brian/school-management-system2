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
        
        // Resolve due_date from term's opening_date (academic days) when available
        $dueDate = $termModel && $termModel->opening_date ? $termModel->opening_date->toDateString() : null;

        // If found and soft deleted, restore it
        if ($invoice && $invoice->trashed()) {
            $invoice->restore();
            // Update the invoice with current data
            $updateData = [
                'family_id' => $student->family_id,
                'academic_year_id' => $academicYear->id ?? $invoice->academic_year_id,
                'term_id' => $termModel->id ?? $invoice->term_id,
            ];
            if ($dueDate) {
                $updateData['due_date'] = $dueDate;
            }
            $invoice->update($updateData);
            self::finalizeInvoiceAfterEnsure($invoice, $student, $year, $term);
            return $invoice;
        }
        
        // If found and not deleted, return it
        if ($invoice && !$invoice->trashed()) {
            // Update academic_year_id, term_id, and due_date if needed (for legacy invoices)
            $updateData = [];
            if (!$invoice->academic_year_id && $academicYear) {
                $updateData['academic_year_id'] = $academicYear->id;
            }
            if (!$invoice->term_id && $termModel) {
                $updateData['term_id'] = $termModel->id;
            }
            if ($dueDate) {
                $updateData['due_date'] = $dueDate;
            }
            if (!empty($updateData)) {
                $invoice->update($updateData);
            }
            self::finalizeInvoiceAfterEnsure($invoice, $student, $year, $term);
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
                'due_date' => $dueDate,
            ]);

            self::finalizeInvoiceAfterEnsure($invoice, $student, $year, $term);

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
                    self::finalizeInvoiceAfterEnsure($invoice, $student, $year, $term);
                    return $invoice;
                }
            }
            
            // Re-throw if it's not a unique constraint violation
            throw $e;
        }
    }

    /**
     * After an invoice is resolved in ensure(): legacy BBF on Term 1; prior-term arrears on Term 2+ (2026+).
     */
    private static function finalizeInvoiceAfterEnsure(Invoice $invoice, Student $student, int $year, int $term): void
    {
        if ($year < 2026) {
            return;
        }
        if ($term === 1) {
            self::addBalanceBroughtForward($invoice, $student);
        }
        if ($term >= 2) {
            self::carryForwardPriorTermBalancesIfNeeded($invoice, $student);
        }
    }

    /**
     * Move outstanding balance from earlier term invoices (same year) onto this invoice without double-counting.
     * Uses an internal payment allocated to prior items, then adds a single line on the current invoice.
     */
    private static function carryForwardPriorTermBalancesIfNeeded(Invoice $invoice, Student $student): void
    {
        if (($invoice->term ?? 0) < 2) {
            return;
        }

        if (InvoiceItem::where('invoice_id', $invoice->id)->where('source', 'prior_term_carryforward')->exists()) {
            return;
        }

        $year = (int) $invoice->year;
        $txCode = 'TERM-CF-' . $year . '-T' . (int) $invoice->term . '-S' . $student->id;
        if (Payment::where('transaction_code', $txCode)->exists()) {
            return;
        }

        $priorInvoices = Invoice::where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', '<', $invoice->term)
            ->where('status', '!=', 'reversed')
            ->orderBy('term')
            ->get();

        foreach ($priorInvoices as $inv) {
            $inv->recalculate();
        }

        $priorInvoices = $priorInvoices->filter(fn ($inv) => ($inv->balance ?? 0) > 0.01);
        if ($priorInvoices->isEmpty()) {
            return;
        }

        $total = round((float) $priorInvoices->sum(fn ($inv) => (float) $inv->balance), 2);
        if ($total <= 0) {
            return;
        }

        DB::transaction(function () use ($invoice, $student, $priorInvoices, $total, $txCode) {
            $allocations = [];
            $remaining = $total;
            foreach ($priorInvoices as $inv) {
                foreach ($inv->items()->where('status', 'active')->get() as $item) {
                    $bal = round((float) $item->getBalance(), 2);
                    if ($bal <= 0) {
                        continue;
                    }
                    $amt = round(min($remaining, $bal), 2);
                    if ($amt <= 0) {
                        continue;
                    }
                    $allocations[] = ['invoice_item_id' => $item->id, 'amount' => $amt];
                    $remaining -= $amt;
                    if ($remaining <= 0.001) {
                        break 2;
                    }
                }
            }

            $allocSum = round(array_sum(array_column($allocations, 'amount')), 2);
            if ($allocations === [] || abs($allocSum - $total) > 0.05) {
                Log::warning('Prior term carry-forward skipped: allocation mismatch', [
                    'invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                    'total' => $total,
                    'alloc_sum' => $allocSum,
                ]);

                return;
            }

            $payment = Payment::create([
                'transaction_code' => $txCode,
                'student_id' => $student->id,
                'family_id' => $student->family_id,
                'invoice_id' => null,
                'amount' => $total,
                'allocated_amount' => 0,
                'unallocated_amount' => $total,
                'payment_method' => 'Internal transfer',
                'payment_channel' => 'term_balance_transfer',
                'narration' => 'Prior term balance(s) cleared and moved to invoice ' . ($invoice->invoice_number ?? '#' . $invoice->id),
                'payment_date' => now(),
            ]);

            app(PaymentAllocationService::class)->allocatePayment($payment, $allocations);

            $votehead = Votehead::firstOrCreate(
                ['code' => 'PRIOR_TERM_ARREARS'],
                ['name' => 'Balance from prior term(s)', 'is_active' => true]
            );

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $votehead->id,
                'amount' => $total,
                'discount_amount' => 0,
                'status' => 'active',
                'source' => 'prior_term_carryforward',
                'effective_date' => $invoice->issued_date ?? now(),
            ]);

            $invoice->refresh();
            self::recalc($invoice);
            self::allocateUnallocatedPaymentsForStudent($student->id);
        });
    }

    /**
     * When a Term 1 (2026+) invoice is edited, rebuild the carry-forward artifacts
     * so Term 2/3 balances correlate with the corrected Term 1 charges.
     *
     * This updates:
     * - the `prior_term_carryforward` invoice item lines on Term 2/3
     * - the internal carry-forward transfer payment (TERM-CF-...)
     * - then re-allocates unallocated real payments for consistency
     */
    public static function syncCarryForwardFromTerm1(Invoice $term1Invoice): void
    {
        $year = (int) ($term1Invoice->year ?? 0);
        $term = (int) ($term1Invoice->term ?? 0);

        if ($year < 2026 || $term !== 1) {
            return;
        }

        $term1Invoice->loadMissing('student');
        $student = $term1Invoice->student;
        if (!$student) {
            return;
        }

        $studentId = (int) $student->id;

        foreach ([2, 3] as $t) {
            $termInvoice = Invoice::query()
                ->where('student_id', $studentId)
                ->where('year', $year)
                ->where('term', (int) $t)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'reversed');
                })
                ->first();

            if ($termInvoice) {
                self::rebuildPriorTermCarryForwardForInvoice($termInvoice, $student);
            }
        }

        // Re-allocate any real payment portions that were attached to old carry-forward lines
        // (we remove/recreate those lines during rebuild).
        self::allocateUnallocatedPaymentsForStudent($studentId);
    }

    /**
     * Force rebuild the `prior_term_carryforward` line(s) + internal transfer payment
     * for the provided Term 2/3 invoice.
     */
    private static function rebuildPriorTermCarryForwardForInvoice(Invoice $invoice, Student $student): void
    {
        if ((int) ($invoice->term ?? 0) < 2) {
            return;
        }

        $year = (int) $invoice->year;
        $studentId = (int) $student->id;
        $termNumber = (int) $invoice->term;
        $txCode = 'TERM-CF-' . $year . '-T' . $termNumber . '-S' . $studentId;

        DB::transaction(function () use ($invoice, $student, $year, $studentId, $termNumber, $txCode) {
            // 1) Remove carry-forward line(s) on this invoice and any payment allocations to them.
            $carryItems = InvoiceItem::query()
                ->where('invoice_id', $invoice->id)
                ->where('source', 'prior_term_carryforward')
                ->get(['id']);

            if ($carryItems->isNotEmpty()) {
                $carryItemIds = $carryItems->pluck('id')->all();

                // Remove allocations attached to the old carried-forward invoice item
                // so the rebuilt carry-forward line can be allocated correctly.
                \App\Models\PaymentAllocation::query()
                    ->whereIn('invoice_item_id', $carryItemIds)
                    ->delete();

                InvoiceItem::query()
                    ->whereIn('id', $carryItemIds)
                    ->delete();
            }

            // 2) Clear the internal carry-forward payment's allocations (so prior-term balances return).
            $internalPayment = Payment::query()
                ->where('transaction_code', $txCode)
                ->where('student_id', $studentId)
                ->first();

            if ($internalPayment) {
                $internalPayment->allocations()->delete();
            }

            // 3) Recalculate prior term invoices and compute current carry-forward total.
            $priorInvoices = Invoice::query()
                ->where('student_id', $studentId)
                ->where('year', $year)
                ->where('term', '<', $termNumber)
                ->where('status', '!=', 'reversed')
                ->orderBy('term')
                ->get();

            foreach ($priorInvoices as $inv) {
                $inv->recalculate();
            }

            $priorInvoices = $priorInvoices->filter(fn ($inv) => (float) ($inv->balance ?? 0) > 0.01);

            if ($priorInvoices->isEmpty()) {
                if ($internalPayment) {
                    $internalPayment->update([
                        'amount' => 0,
                        'allocated_amount' => 0,
                        'unallocated_amount' => 0,
                        'reversed' => false,
                        'reversed_by' => null,
                        'reversed_at' => null,
                        'reversal_reason' => null,
                    ]);
                }

                self::recalc($invoice);
                return;
            }

            $total = round((float) $priorInvoices->sum(fn ($inv) => (float) $inv->balance), 2);

            if ($total <= 0) {
                if ($internalPayment) {
                    $internalPayment->update([
                        'amount' => 0,
                        'allocated_amount' => 0,
                        'unallocated_amount' => 0,
                        'reversed' => false,
                        'reversed_by' => null,
                        'reversed_at' => null,
                        'reversal_reason' => null,
                    ]);
                }

                self::recalc($invoice);
                return;
            }

            // 4) Build allocations from prior invoice items (based on their current unpaid balances).
            $allocations = [];
            $remaining = $total;

            foreach ($priorInvoices as $inv) {
                foreach ($inv->items()->where('status', 'active')->get() as $item) {
                    $bal = round((float) $item->getBalance(), 2);
                    if ($bal <= 0) {
                        continue;
                    }

                    $amt = round(min($remaining, $bal), 2);
                    if ($amt <= 0) {
                        continue;
                    }

                    $allocations[] = ['invoice_item_id' => $item->id, 'amount' => $amt];
                    $remaining -= $amt;

                    if ($remaining <= 0.001) {
                        break 2;
                    }
                }
            }

            $allocSum = round(array_sum(array_column($allocations, 'amount')), 2);
            if ($allocations === [] || abs($allocSum - $total) > 0.05) {
                Log::warning('Prior term carry-forward sync skipped: allocation mismatch', [
                    'invoice_id' => $invoice->id,
                    'student_id' => $studentId,
                    'total' => $total,
                    'alloc_sum' => $allocSum,
                ]);

                self::recalc($invoice);
                return;
            }

            // 5) Ensure the internal payment exists, is correctly sized, and then allocate it.
            if (!$internalPayment) {
                $internalPayment = Payment::create([
                    'transaction_code' => $txCode,
                    'student_id' => $studentId,
                    'family_id' => $student->family_id,
                    'invoice_id' => null,
                    'amount' => $total,
                    'allocated_amount' => 0,
                    'unallocated_amount' => $total,
                    'payment_method' => 'Internal transfer',
                    'payment_channel' => 'term_balance_transfer',
                    'narration' => 'Prior term balance(s) cleared and moved to invoice ' . ($invoice->invoice_number ?? '#' . $invoice->id),
                    'payment_date' => now(),
                    'reversed' => false,
                ]);
            } else {
                $internalPayment->update([
                    'amount' => $total,
                    'allocated_amount' => 0,
                    'unallocated_amount' => $total,
                    'reversed' => false,
                    'reversed_by' => null,
                    'reversed_at' => null,
                    'reversal_reason' => null,
                    'payment_method' => 'Internal transfer',
                    'payment_channel' => 'term_balance_transfer',
                    'family_id' => $student->family_id,
                    'payment_date' => now(),
                ]);
            }

            app(\App\Services\PaymentAllocationService::class)
                ->allocatePayment($internalPayment, $allocations);

            // 6) Recreate the carry-forward invoice item line with the corrected total.
            $votehead = Votehead::firstOrCreate(
                ['code' => 'PRIOR_TERM_ARREARS'],
                ['name' => 'Balance from prior term(s)', 'is_active' => true]
            );

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $votehead->id,
                'amount' => $total,
                'discount_amount' => 0,
                'status' => 'active',
                'source' => 'prior_term_carryforward',
                'effective_date' => $invoice->issued_date ?? now(),
            ]);

            $invoice->refresh();
            self::recalc($invoice);
        });
    }

    /**
     * Run prior-term carry-forward for an existing invoice (backfill / manual). Idempotent.
     *
     * @return bool True if a new prior-term line was added on this run
     */
    public static function applyPriorTermCarryForwardIfNeeded(Invoice $invoice): bool
    {
        $invoice->loadMissing('student');
        if (!$invoice->student) {
            return false;
        }
        if (($invoice->year ?? 0) < 2026 || ($invoice->term ?? 0) < 2) {
            return false;
        }
        $hadLine = InvoiceItem::where('invoice_id', $invoice->id)->where('source', 'prior_term_carryforward')->exists();
        self::carryForwardPriorTermBalancesIfNeeded($invoice, $invoice->student);
        $invoice->refresh();

        return InvoiceItem::where('invoice_id', $invoice->id)->where('source', 'prior_term_carryforward')->exists() && !$hadLine;
    }
    
    /**
     * Add balance brought forward as an invoice item for the first term of 2026
     */
    public static function addBalanceBroughtForward(Invoice $invoice, Student $student): void
    {
        $balanceBroughtForward = \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
        
        if (abs((float) $balanceBroughtForward) < 0.01) {
            return; // No balance to bring forward (debit or credit)
        }

        // Legacy overpayment (negative) is materialized as a Payment credit so it can auto-allocate to future invoice items.
        if ((float) $balanceBroughtForward < 0) {
            $invoiceYear = (int) ($invoice->year ?? ($invoice->academicYear?->year ?? now()->year));
            $sourceYear = $invoiceYear - 1;
            $creditAmount = abs((float) $balanceBroughtForward);

            $transactionCode = "BBF-{$invoiceYear}-{$student->id}";
            $receiptNumber = $transactionCode;

            $payment = Payment::firstOrCreate(
                ['transaction_code' => $transactionCode],
                [
                    'receipt_number' => $receiptNumber,
                    'student_id' => $student->id,
                    'family_id' => $student->family_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $creditAmount,
                    'allocated_amount' => 0,
                    'unallocated_amount' => $creditAmount,
                    'payment_method' => 'Balance B/F',
                    'payment_channel' => 'balance_brought_forward',
                    'narration' => "Overpayment brought forward from {$sourceYear}",
                    'payment_date' => ($invoice->issued_date ?? now()),
                ]
            );

            $payment->updateAllocationTotals();
            self::allocateUnallocatedPaymentsForStudent($student->id);
            self::recalc($invoice);
            return;
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

            // Keep Term 2/3 carry-forward correlated with corrected Term 1.
            if ((int) ($item->invoice->year ?? 0) >= 2026 && (int) ($item->invoice->term ?? 0) === 1) {
                // $item is an InvoiceItem; sync needs the parent Invoice (Term 1).
                self::syncCarryForwardFromTerm1($item->invoice);
            }
            
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

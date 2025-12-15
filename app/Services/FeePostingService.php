<?php

namespace App\Services;

use App\Models\{
    Student, Votehead, FeeStructure, FeeCharge, OptionalFee, InvoiceItem,
    Invoice, FeePostingRun, PostingDiff, AcademicYear, Term, FeeConcession, User
};
use App\Services\{DiscountService, InvoiceService};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Enhanced Fee Posting Service
 * Handles posting pending fees with diff calculation, idempotency, and reversal
 */
class FeePostingService
{
    /**
     * Build preview with diff calculation
     */
    public function previewWithDiffs(array $filters): array
    {
        $year = (int)$filters['year'];
        $term = (int)$filters['term'];
        
        $students = $this->getFilteredStudents($filters);
        $diffs = collect();
        
        foreach ($students as $student) {
            // Get existing invoice items for this student/year/term
            $existingItems = $this->getExistingInvoiceItems($student->id, $year, $term);
            
            // Get proposed items from structures/optional fees
            $proposedItems = $this->getProposedItems($student, $year, $term, $filters);
            
            // If no proposed items, log why
            if ($proposedItems->isEmpty()) {
                \Log::warning('No proposed items generated for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'classroom_id' => $student->classroom_id,
                    'year' => $year,
                    'term' => $term,
                ]);
            }
            
            // Debug logging
            \Log::info('Posting preview for student', [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'year' => $year,
                'term' => $term,
                'existing_items_count' => $existingItems->count(),
                'proposed_items_count' => $proposedItems->count(),
                'existing_items' => $existingItems->toArray(),
                'proposed_items' => $proposedItems->toArray(),
            ]);
            
            // Track which voteheads have been processed (including unchanged ones)
            $processedVoteheads = collect();
            
            // Calculate diffs - only include items that have changed
            foreach ($proposedItems as $proposed) {
                $voteheadId = $proposed['votehead_id'];
                $existing = $existingItems->firstWhere('votehead_id', $voteheadId);
                
                $diff = $this->calculateDiff($student, $voteheadId, $existing, $proposed);
                
                // Track this votehead as processed (even if unchanged/null)
                // This prevents it from being marked as removed later
                $processedVoteheads->push($voteheadId);
                
                // Only add to diffs if there's an actual change
                // Unchanged items return null and are completely skipped - they don't appear in the preview
                if ($diff !== null && ($diff['action'] ?? '') !== 'unchanged') {
                    $diffs->push($diff);
                    \Log::info('Diff generated', [
                        'student_id' => $student->id,
                        'votehead_id' => $voteheadId,
                        'action' => $diff['action'] ?? 'unknown',
                        'old_amount' => $diff['old_amount'] ?? null,
                        'new_amount' => $diff['new_amount'] ?? null,
                    ]);
                } else {
                    \Log::info('Diff skipped (unchanged)', [
                        'student_id' => $student->id,
                        'votehead_id' => $voteheadId,
                        'existing_amount' => $existing['amount'] ?? null,
                        'proposed_amount' => $proposed['amount'] ?? null,
                    ]);
                }
            }
            
            // Check for removed items (existing but not in proposed AND not already processed)
            // IMPORTANT: Only mark as removed if the item truly shouldn't exist
            // If an item exists and is unchanged, it will be in proposed items and already processed
            foreach ($existingItems as $existing) {
                $voteheadId = $existing['votehead_id'];
                
                // Skip if already processed (unchanged items are tracked but not added to diffs)
                if ($processedVoteheads->contains($voteheadId)) {
                    continue;
                }
                
                // Check if this votehead is in proposed items (even if not processed yet)
                $proposedMatch = $proposedItems->firstWhere('votehead_id', $voteheadId);
                
                // If it's in proposed items, it means it should exist - don't mark as removed
                // This handles edge cases where the item exists but wasn't processed in the loop above
                if ($proposedMatch) {
                    // Double-check: if amounts match, it's unchanged - skip it
                    if (abs(($existing['amount'] ?? 0) - ($proposedMatch['amount'] ?? 0)) < 0.01) {
                        continue; // Unchanged - skip
                    }
                    // If amounts don't match, it will be handled as increased/decreased in the main loop
                    continue;
                }
                
                // CRITICAL: Only mark as removed if we're absolutely certain the item shouldn't exist
                // If an item exists but is not in proposed items, it could be:
                // 1. Manually added (should be removed if not in structure) - OK to remove
                // 2. From fee structure but not included due to filtering - DON'T remove
                // 3. From optional fees but not selected - DON'T remove if it's active
                // 4. Unchanged but not matched correctly - DON'T remove
                
                // Be VERY conservative: Only mark as removed if:
                // 1. It's an active item (pending items shouldn't be marked as removed)
                // 2. It has a non-zero amount
                // 3. It's not from 'optional' source (optional fees might not be in proposed if not selected)
                // 4. It's truly not in the fee structure anymore
                
                $existingAmount = (float)($existing['amount'] ?? 0);
                $existingSource = $existing['source'] ?? 'structure';
                
                // Skip if it's a pending item - pending items should not be marked as removed
                if (($existing['status'] ?? 'active') !== 'active') {
                    continue;
                }
                
                // Skip if amount is zero or negative - nothing to remove
                if ($existingAmount <= 0) {
                    continue;
                }
                
                // Skip optional fees that are not in proposed - they might just not be selected
                // Only remove optional fees if they're explicitly not supposed to be there
                if ($existingSource === 'optional') {
                    // Don't mark optional fees as removed unless explicitly confirmed
                    // They might not be in proposed items if not selected, but that doesn't mean they should be removed
                    continue;
                }
                
                // For structure items: only mark as removed if we're sure they're not in the structure
                // Since we can't be 100% sure without checking the structure directly, we'll be conservative
                // and NOT mark them as removed unless we're absolutely certain
                // For now, skip marking as removed to prevent accidental deletions
                // TODO: Add a flag or confirmation mechanism for removing items
                continue; // Skip for now - be conservative
            }
        }
        
        // Debug: Log summary
        \Log::info('Posting preview summary', [
            'total_students' => $students->count(),
            'total_diffs' => $diffs->count(),
            'filters' => $filters,
        ]);
        
        return [
            'diffs' => $diffs,
            'summary' => $this->calculateSummary($diffs),
        ];
    }
    
    /**
     * Create posting run and commit with idempotency
     */
    public function commitWithTracking(
        Collection $diffs,
        int $year,
        int $term,
        bool $activateNow,
        ?string $effectiveDate = null,
        array $filters = []
    ): FeePostingRun {
        return DB::transaction(function () use ($diffs, $year, $term, $activateNow, $effectiveDate, $filters) {
            // Create posting run
            // Use helper functions to get current academic year and term
            // First try to find by year
            $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
            
            // If not found by year, try to find by is_active
            if (!$academicYear) {
                $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
            }
            
            // If still not found, try to find any academic year that contains this year
            if (!$academicYear) {
                $academicYear = \App\Models\AcademicYear::where('year', '<=', $year)
                    ->orderBy('year', 'desc')
                    ->first();
            }
            
            $termModel = null;
            
            // Always use existing terms from the database
            // Strategy 1: Find term by number within the academic year
            if ($academicYear) {
                $terms = \App\Models\Term::where('academic_year_id', $academicYear->id)->get();
                foreach ($terms as $t) {
                    // Extract number from term name (e.g., "Term 1", "Term 2", "Term 3")
                    if (preg_match('/\b(\d+)\b/', $t->name, $matches)) {
                        if ((int)$matches[1] == $term) {
                            $termModel = $t;
                            \Log::info('Found term by number in academic year', [
                                'requested_term' => $term,
                                'found_term_id' => $termModel->id,
                                'found_term_name' => $termModel->name,
                                'academic_year_id' => $academicYear->id,
                            ]);
                            break;
                        }
                    }
                }
            }
            
            // Strategy 2: If not found in academic year, try to find by is_current flag
            // (Useful if the active term matches the requested term number)
            if (!$termModel) {
                $currentTerm = \App\Models\Term::where('is_current', true)->first();
                if ($currentTerm) {
                    // Extract number from term name
                    if (preg_match('/\b(\d+)\b/', $currentTerm->name, $matches)) {
                        if ((int)$matches[1] == $term) {
                            $termModel = $currentTerm;
                            \Log::info('Found term by is_current flag', [
                                'requested_term' => $term,
                                'found_term_id' => $termModel->id,
                                'found_term_name' => $termModel->name,
                            ]);
                        }
                    }
                }
            }
            
            // If we still don't have a term, we cannot proceed
            // The system must use existing terms from the database
            if (!$termModel) {
                $availableTerms = \App\Models\Term::select('id', 'name', 'academic_year_id', 'is_current')
                    ->orderBy('academic_year_id')
                    ->orderBy('name')
                    ->get();
                
                $termsList = $availableTerms->map(function($t) {
                    return "{$t->name} (Year ID: {$t->academic_year_id}" . ($t->is_current ? ', Active' : '') . ")";
                })->join(', ');
                
                throw new \Exception(
                    "Could not find Term {$term} for academic year {$year}. " .
                    "The system must use existing terms from the database. " .
                    "Available terms: {$termsList}. " .
                    "Please ensure Term {$term} exists in Settings > Academic Config for the selected academic year."
                );
            }
            
            $run = FeePostingRun::create([
                'academic_year_id' => $academicYear->id ?? null,
                'term_id' => $termModel->id,
                'run_type' => 'commit',
                'status' => 'pending',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'filters_applied' => $filters,
                'items_posted_count' => 0,
            ]);
            
            $count = 0;
            
            foreach ($diffs as $diff) {
                $action = $diff['action'] ?? 'added';
                
                // Skip "removed" items - they shouldn't be processed if they're unchanged
                // Only process "removed" if the item truly shouldn't exist (manually added, no longer in structure)
                if ($action === 'removed') {
                    // Check if this item should actually be removed
                    // If old_amount is 0 or null, it means the item didn't exist, so skip
                    $oldAmount = (float)($diff['old_amount'] ?? 0);
                    if ($oldAmount <= 0) {
                        // Item didn't exist, so nothing to remove - skip
                        continue;
                    }
                    
                    // Item exists but is being removed - delete it
                    if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                        $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                        if ($existingItem) {
                            // Only delete if it's truly not in the fee structure anymore
                            // For now, we'll delete it but log it
                            $existingItem->delete();
                            
                            // Create diff record for tracking
                            PostingDiff::create([
                                'posting_run_id' => $run->id,
                                'student_id' => $diff['student_id'],
                                'votehead_id' => $diff['votehead_id'],
                                'action' => 'removed',
                                'old_amount' => $oldAmount,
                                'new_amount' => 0,
                                'invoice_item_id' => $existingItem->id,
                                'source' => $diff['origin'] ?? 'structure',
                            ]);
                            
                            // Recalculate invoice
                            $invoice = $existingItem->invoice;
                            \App\Services\InvoiceService::recalc($invoice);
                            $count++;
                        }
                    }
                    continue; // Skip to next diff
                }
                
                // Handle "unchanged" action for optional fees (they need to be committed from pending to active)
                if (($diff['action'] ?? '') === 'unchanged') {
                    $invoice = InvoiceService::ensure($diff['student_id'], $year, $term);
                    
                    // Check if item already exists
                    $existingItem = null;
                    if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                        $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                    }
                    
                    // If item exists and is pending, activate it
                    if ($existingItem && $existingItem->status === 'pending') {
                        $existingItem->update([
                            'status' => 'active',
                            'posting_run_id' => $run->id,
                            'posted_at' => now(),
                        ]);
                        
                        // Create diff record
                        PostingDiff::create([
                            'posting_run_id' => $run->id,
                            'student_id' => $diff['student_id'],
                            'votehead_id' => $diff['votehead_id'],
                            'action' => 'unchanged', // Shows as unchanged but was committed
                            'old_amount' => $diff['old_amount'] ?? null,
                            'new_amount' => $diff['new_amount'] ?? 0,
                            'invoice_item_id' => $existingItem->id,
                            'source' => $diff['origin'] ?? 'optional',
                        ]);
                        
                        // Recalculate invoice
                        \App\Services\InvoiceService::recalc($invoice);
                        $count++;
                        continue; // Skip to next diff
                    } else if ($existingItem && $existingItem->status === 'active') {
                        // Already active, skip
                        continue;
                    }
                    // If item doesn't exist, fall through to create it
                }
                
                // Idempotency check: skip if item already exists and is active with same amount
                if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                    $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                    if ($existingItem && $existingItem->status === 'active' && abs($existingItem->amount - ($diff['new_amount'] ?? 0)) < 0.01) {
                        continue; // Already exists and unchanged
                    }
                }
                
                $invoice = InvoiceService::ensure($diff['student_id'], $year, $term);
                
                // Check if item already exists
                $existingItem = null;
                if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                    $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                }
                
                // If item exists and amount changed, create credit/debit note
                if ($existingItem && isset($diff['old_amount']) && abs($diff['old_amount'] - ($diff['new_amount'] ?? 0)) > 0.01) {
                    $oldAmount = (float)$diff['old_amount'];
                    $newAmount = (float)($diff['new_amount'] ?? 0);
                    $difference = $newAmount - $oldAmount;
                    
                    // Create credit/debit note using InvoiceService
                    if ($difference < 0) {
                        // Amount decreased - create credit note
                        \App\Models\CreditNote::create([
                            'invoice_id' => $invoice->id,
                            'invoice_item_id' => $existingItem->id,
                            'amount' => abs($difference),
                            'reason' => 'Fee structure amount changed',
                            'notes' => "Amount reduced from {$oldAmount} to {$newAmount} via fee posting",
                            'issued_by' => auth()->id(),
                            'issued_at' => now(),
                        ]);
                    } elseif ($difference > 0) {
                        // Amount increased - create debit note
                        \App\Models\DebitNote::create([
                            'invoice_id' => $invoice->id,
                            'invoice_item_id' => $existingItem->id,
                            'amount' => $difference,
                            'reason' => 'Fee structure amount changed',
                            'notes' => "Amount increased from {$oldAmount} to {$newAmount} via fee posting",
                            'issued_by' => auth()->id(),
                            'issued_at' => now(),
                        ]);
                    }
                }
                
                $item = InvoiceItem::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'votehead_id' => $diff['votehead_id']],
                    [
                        'amount' => (float)($diff['new_amount'] ?? 0),
                        'original_amount' => $diff['old_amount'] ?? null,
                        'status' => $activateNow ? 'active' : 'pending',
                        'effective_date' => $activateNow ? null : ($effectiveDate ?? null),
                        'source' => $diff['origin'] ?? 'structure',
                        'posting_run_id' => $run->id,
                        'posted_at' => now(),
                    ]
                );
                
                // Create diff record
                PostingDiff::create([
                    'posting_run_id' => $run->id,
                    'student_id' => $diff['student_id'],
                    'votehead_id' => $diff['votehead_id'],
                    'action' => $diff['action'] ?? 'added',
                    'old_amount' => $diff['old_amount'] ?? null,
                    'new_amount' => $diff['new_amount'] ?? 0,
                    'invoice_item_id' => $item->id,
                    'source' => $diff['origin'] ?? 'structure',
                ]);
                
                // Apply discounts
                $discountService = new DiscountService();
                $discountService->applyDiscountsToInvoice($invoice);
                
                \App\Services\InvoiceService::recalc($invoice);
                $count++;
            }
            
            $run->update([
                'status' => 'completed',
                'items_posted_count' => $count,
                'total_amount_posted' => $diffs->sum('new_amount'),
                'total_students_affected' => $diffs->pluck('student_id')->unique()->count(),
            ]);
            
            // Activate this run (deactivates all others)
            $run->activate();
            
            // Log audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'posted',
                    $run,
                    null,
                    [
                        'run_id' => $run->id,
                        'year' => $year,
                        'term' => $term,
                        'items_posted' => $count,
                        'total_amount' => $run->total_amount_posted,
                    ],
                    ['fee_posting', 'commit']
                );
            }
            
            return $run;
        });
    }
    
    /**
     * Reverse a posting run
     */
    public function reversePostingRun(FeePostingRun $run): bool
    {
        if (!$run->canBeReversed()) {
            throw new \Exception('This posting run cannot be reversed.');
        }
        
        return DB::transaction(function () use ($run) {
            // Mark items from this run as reversed
            $items = InvoiceItem::where('posting_run_id', $run->id)->get();
            
            // Collect unique invoices that will be affected
            $affectedInvoices = collect();
            
            foreach ($items as $item) {
                // Create reversal diff
                PostingDiff::create([
                    'posting_run_id' => $run->id,
                    'student_id' => $item->invoice->student_id,
                    'votehead_id' => $item->votehead_id,
                    'action' => 'removed',
                    'old_amount' => $item->amount,
                    'new_amount' => 0,
                    'invoice_item_id' => $item->id,
                    'source' => $item->source,
                ]);
                
                // Track the invoice
                if (!$affectedInvoices->contains('id', $item->invoice_id)) {
                    $affectedInvoices->push($item->invoice);
                }
                
                // Remove or deactivate item
                $item->delete(); // Or set status to 'reversed' if soft deletes
            }
            
            // After deleting all items, check each invoice
            foreach ($affectedInvoices as $invoice) {
                $invoice->refresh(); // Refresh to get latest state
                
                // Check if invoice has any remaining items (including pending items)
                $remainingItems = $invoice->items()->count();
                
                // Check if invoice has any payments
                $hasPayments = $invoice->payments()->count() > 0;
                
                // Check if invoice has any payment allocations
                $hasAllocations = \App\Models\PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id);
                })->count() > 0;
                
                // Check if invoice has any credit notes or debit notes
                $hasCreditNotes = $invoice->creditNotes()->count() > 0;
                $hasDebitNotes = $invoice->debitNotes()->count() > 0;
                
                \Log::info('Checking invoice for deletion after reversal', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $invoice->student_id,
                    'remaining_items' => $remainingItems,
                    'has_payments' => $hasPayments,
                    'has_allocations' => $hasAllocations,
                    'has_credit_notes' => $hasCreditNotes,
                    'has_debit_notes' => $hasDebitNotes,
                    'posting_run_id' => $run->id,
                ]);
                
                // If invoice has no items, no payments, no allocations, and no notes, delete it completely
                if ($remainingItems === 0 && !$hasPayments && !$hasAllocations && !$hasCreditNotes && !$hasDebitNotes) {
                    \Log::info('Deleting invoice after reversal - no items, payments, allocations, or notes', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'student_id' => $invoice->student_id,
                        'posting_run_id' => $run->id,
                    ]);
                    $invoice->delete();
                } else {
                    // Recalculate invoice if it still has items or payments
                    InvoiceService::recalc($invoice);
                }
            }
            
            // Create reversal run
            $reversalRun = FeePostingRun::create([
                'academic_year_id' => $run->academic_year_id,
                'term_id' => $run->term_id,
                'run_type' => 'reversal',
                'status' => 'completed',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'filters_applied' => $run->filters_applied,
            ]);
            
            $run->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'total_amount_posted' => 0,
            ]);
            
            // Deactivate this run and activate the previous latest
            $run->deactivateAndActivatePrevious();
            
            // Log audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'reversed',
                    $run,
                    ['status' => 'completed'],
                    ['status' => 'reversed', 'reversed_by' => auth()->id()],
                    ['fee_posting', 'reversal']
                );
            }
            
            return true;
        });
    }
    
    /**
     * Reverse posting for a specific student
     */
    public function reversePostingForStudent(FeePostingRun $run, int $studentId): bool
    {
        if (!$run->canBeReversed()) {
            throw new \Exception('This posting run cannot be reversed.');
        }
        
        return DB::transaction(function () use ($run, $studentId) {
            // Get items from this run for this specific student
            $items = InvoiceItem::where('posting_run_id', $run->id)
                ->whereHas('invoice', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })
                ->get();
            
            if ($items->isEmpty()) {
                throw new \Exception('No items found for this student in this posting run.');
            }
            
            // Collect unique invoices that will be affected
            $affectedInvoices = collect();
            
            foreach ($items as $item) {
                // Create reversal diff
                PostingDiff::create([
                    'posting_run_id' => $run->id,
                    'student_id' => $item->invoice->student_id,
                    'votehead_id' => $item->votehead_id,
                    'action' => 'removed',
                    'old_amount' => $item->amount,
                    'new_amount' => 0,
                    'invoice_item_id' => $item->id,
                    'source' => $item->source,
                ]);
                
                // Track the invoice
                if (!$affectedInvoices->contains('id', $item->invoice_id)) {
                    $affectedInvoices->push($item->invoice);
                }
                
                // Remove or deactivate item
                $item->delete();
            }
            
            // After deleting all items, check each invoice
            foreach ($affectedInvoices as $invoice) {
                $invoice->refresh(); // Refresh to get latest state
                
                // Check if invoice has any remaining items (including pending items)
                $remainingItems = $invoice->items()->count();
                
                // Check if invoice has any payments
                $hasPayments = $invoice->payments()->count() > 0;
                
                // Check if invoice has any payment allocations
                $hasAllocations = \App\Models\PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id);
                })->count() > 0;
                
                // Check if invoice has any credit notes or debit notes
                $hasCreditNotes = $invoice->creditNotes()->count() > 0;
                $hasDebitNotes = $invoice->debitNotes()->count() > 0;
                
                \Log::info('Checking invoice for deletion after student reversal', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $invoice->student_id,
                    'remaining_items' => $remainingItems,
                    'has_payments' => $hasPayments,
                    'has_allocations' => $hasAllocations,
                    'has_credit_notes' => $hasCreditNotes,
                    'has_debit_notes' => $hasDebitNotes,
                    'posting_run_id' => $run->id,
                ]);
                
                // If invoice has no items, no payments, no allocations, and no notes, delete it completely
                if ($remainingItems === 0 && !$hasPayments && !$hasAllocations && !$hasCreditNotes && !$hasDebitNotes) {
                    \Log::info('Deleting invoice after student reversal - no items, payments, allocations, or notes', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'student_id' => $invoice->student_id,
                        'posting_run_id' => $run->id,
                    ]);
                    $invoice->delete();
                } else {
                    // Recalculate invoice if it still has items or payments
                    InvoiceService::recalc($invoice);
                }
            }
            
            // Log audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'reversed',
                    $run,
                    ['status' => 'completed'],
                    ['status' => 'partially_reversed', 'reversed_student_id' => $studentId],
                    ['fee_posting', 'reversal', 'student']
                );
            }
            
            return true;
        });
    }
    
    /**
     * Get filtered students
     */
    private function getFilteredStudents(array $filters): Collection
    {
        return Student::query()
            ->when(!empty($filters['student_id']), fn($q) => $q->where('id', $filters['student_id']))
            ->when(!empty($filters['class_id']), fn($q) => $q->where('classroom_id', $filters['class_id']))
            ->when(!empty($filters['stream_id']), fn($q) => $q->where('stream_id', $filters['stream_id']))
            ->get();
    }
    
    /**
     * Get existing invoice items
     */
    private function getExistingInvoiceItems(int $studentId, int $year, int $term): Collection
    {
        $invoice = Invoice::where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();
        
        if (!$invoice) {
            return collect();
        }
        
        return $invoice->items()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'votehead_id' => $item->votehead_id,
                'amount' => $item->amount,
                'status' => $item->status ?? 'active',
                'source' => $item->source,
            ];
        });
    }
    
    /**
     * Get proposed items from structures/optional fees
     */
    private function getProposedItems(Student $student, int $year, int $term, array $filters): Collection
    {
        $items = collect();
        
        // From fee structure - match by classroom, academic_year, term, stream, and student category
        $structureQuery = FeeStructure::with('charges.votehead')
            ->where('classroom_id', $student->classroom_id)
            ->where('is_active', true);
        
        // Try to match academic_year_id first, fallback to year column
        // If no exact match, try to find any active structure for this class (less restrictive)
        $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
        if ($academicYear) {
            $structureQuery->where(function($q) use ($academicYear, $year) {
                $q->where('academic_year_id', $academicYear->id)
                  ->orWhere('year', $year)
                  ->orWhereNull('academic_year_id') // Include structures without academic_year_id
                  ->orWhereNull('year'); // Include structures without year
            });
        } else {
            // If no academic year found, be less restrictive - find any structure for this class
            $structureQuery->where(function($q) use ($year) {
                $q->where('year', $year)
                  ->orWhereNull('year'); // Include structures without year
            });
        }
        
        // Get structure with year matching first
        $structure = $structureQuery->first();
        
        // If no structure found with year matching, try without year requirement
        if (!$structure) {
            $structureQueryWithoutYear = FeeStructure::with('charges.votehead')
                ->where('classroom_id', $student->classroom_id)
                ->where('is_active', true);
            
            // Match student category if set, otherwise match structures with no category
            if ($student->category_id) {
                $structureQueryWithoutYear->where(function($q) use ($student) {
                    $q->where('student_category_id', $student->category_id)
                      ->orWhereNull('student_category_id');
                });
            } else {
                $structureQueryWithoutYear->whereNull('student_category_id');
            }
            
            // Match stream if set
            if ($student->stream_id) {
                $structureQueryWithoutYear->where(function($q) use ($student) {
                    $q->where('stream_id', $student->stream_id)
                      ->orWhereNull('stream_id');
                });
            } else {
                $structureQueryWithoutYear->whereNull('stream_id');
            }
            
            $structure = $structureQueryWithoutYear->first();
        }
        
        // Match student category if set, otherwise match structures with no category
        if ($student->category_id) {
            $structureQuery->where(function($q) use ($student) {
                $q->where('student_category_id', $student->category_id)
                  ->orWhereNull('student_category_id'); // Also include general structures
            });
        } else {
            $structureQuery->whereNull('student_category_id'); // Only general structures
        }
        
        // Match stream if set
        if ($student->stream_id) {
            $structureQuery->where(function($q) use ($student) {
                $q->where('stream_id', $student->stream_id)
                  ->orWhereNull('stream_id'); // Also include general structures
            });
        } else {
            $structureQuery->whereNull('stream_id'); // Only general structures
        }
        
        // Order by specificity: category-specific > stream-specific > general
        $structureQuery->orderByRaw('CASE WHEN student_category_id IS NOT NULL THEN 0 ELSE 1 END')
                       ->orderByRaw('CASE WHEN stream_id IS NOT NULL THEN 0 ELSE 1 END');
        
        $structure = $structureQuery->first();
        
        // Debug: Log if no structure found
        if (!$structure) {
            \Log::warning('No fee structure found for student', [
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'year' => $year,
                'term' => $term,
                'category_id' => $student->category_id,
                'stream_id' => $student->stream_id,
            ]);
        }
        
        if ($structure) {
            // Check if student is newly admitted in this academic year
            $isNewStudent = $student->isNewlyAdmitted($year);
            
            // For once_annually fees with preferred_term, check if we should include them
            // Make sure voteheads are loaded
            $charges = $structure->charges()->with('votehead')->get();
            
            \Log::info('Charges loaded with voteheads', [
                'structure_id' => $structure->id,
                'charges_count' => $charges->count(),
                'charges_with_votehead' => $charges->filter(fn($c) => $c->votehead)->count(),
                'charges_without_votehead' => $charges->filter(fn($c) => !$c->votehead)->count(),
                'charges_detail' => $charges->map(function($c) {
                    return [
                        'charge_id' => $c->id,
                        'votehead_id' => $c->votehead_id,
                        'term' => $c->term,
                        'amount' => $c->amount,
                        'has_votehead' => $c->votehead !== null,
                        'votehead_name' => $c->votehead ? $c->votehead->name : null,
                    ];
                })->toArray(),
            ]);
            
            \Log::info('Fee structure charges before filtering', [
                'student_id' => $student->id,
                'structure_id' => $structure->id,
                'total_charges' => $charges->count(),
                'charges_by_term' => $charges->groupBy('term')->map->count()->toArray(),
                'term' => $term,
                'is_new_student' => $isNewStudent,
            ]);
            
            // Filter by term, but handle preferred_term for once_annually
            $charges = $charges->filter(function($charge) use ($term, $isNewStudent) {
                $votehead = $charge->votehead;
                if (!$votehead) {
                    \Log::warning('Charge has no votehead in filter', ['charge_id' => $charge->id]);
                    return false;
                }
                
                // For once_annually fees with preferred_term
                if ($votehead->charge_type === 'once_annually' && 
                    $votehead->preferred_term !== null) {
                    if ($isNewStudent) {
                        // New student: include yearly fees from any term (we'll charge in admission term)
                        // The canChargeForStudent method will allow charging in admission term
                        return true;
                    } else {
                        // Existing student: charge only in preferred_term
                        // For yearly fees, only include the charge if:
                        // 1. Current term matches preferred_term AND
                        // 2. The charge is stored in the preferred_term
                        // This prevents duplicates when yearly fees are stored in multiple terms
                        $include = ($votehead->preferred_term == $term) && ($charge->term == $votehead->preferred_term);
                        \Log::info('Yearly fee with preferred_term check', [
                            'votehead_id' => $votehead->id,
                            'votehead_name' => $votehead->name,
                            'charge_type' => $votehead->charge_type,
                            'preferred_term' => $votehead->preferred_term,
                            'current_term' => $term,
                            'charge_term' => $charge->term,
                            'include' => $include,
                        ]);
                        return $include;
                    }
                }
                
                // For other charge types (per_term, once, etc.), match by term
                // Include if the charge's term matches the current term
                $include = ($charge->term == $term);
                if (!$include) {
                    \Log::info('Charge filtered out by term mismatch', [
                        'votehead_id' => $votehead->id,
                        'votehead_name' => $votehead->name,
                        'charge_type' => $votehead->charge_type,
                        'charge_term' => $charge->term,
                        'current_term' => $term,
                    ]);
                } else {
                    \Log::info('Charge included (term matches)', [
                        'votehead_id' => $votehead->id,
                        'votehead_name' => $votehead->name,
                        'charge_type' => $votehead->charge_type,
                        'charge_term' => $charge->term,
                        'current_term' => $term,
                    ]);
                }
                
                return $include;
            });
            
            \Log::info('Fee structure charges after term filtering', [
                'student_id' => $student->id,
                'filtered_charges_count' => $charges->count(),
            ]);
            
            // For new students: if yearly fees are stored in preferred_term but student is admitted in different term,
            // we need to include them. Use the amount from any term (yearly fees have same amount regardless of term).
            if ($isNewStudent) {
                // Get all yearly charges (may be in preferred_term)
                $allYearlyCharges = $structure->charges->filter(function($charge) {
                    $votehead = $charge->votehead;
                    return $votehead && $votehead->charge_type === 'once_annually' && 
                           $votehead->preferred_term !== null;
                });
                
                // For each yearly charge, if not already included for current term, add it
                foreach ($allYearlyCharges as $yearlyCharge) {
                    $alreadyIncluded = $charges->contains(function($c) use ($yearlyCharge, $term) {
                        return $c->votehead_id == $yearlyCharge->votehead_id && $c->term == $term;
                    });
                    
                    if (!$alreadyIncluded) {
                        // Use the yearly charge but treat it as if it's for current term
                        // The amount is the same regardless of term for yearly fees
                        $charges->push($yearlyCharge);
                    }
                }
            }
            
            if (!empty($filters['votehead_id'])) {
                $charges = $charges->where('votehead_id', (int)$filters['votehead_id']);
            }
            
            foreach ($charges as $charge) {
                $votehead = $charge->votehead;
                if (!$votehead) {
                    \Log::warning('Charge has no votehead', ['charge_id' => $charge->id]);
                    continue;
                }
                
                // Check charge type constraints (handles preferred_term and once-only for new students)
                $canCharge = $votehead->canChargeForStudent($student, $year, $term);
                if (!$canCharge) {
                    \Log::info('Votehead cannot be charged for student', [
                        'votehead_id' => $votehead->id,
                        'votehead_name' => $votehead->name,
                        'charge_type' => $votehead->charge_type,
                        'student_id' => $student->id,
                        'year' => $year,
                        'term' => $term,
                        'is_new_student' => $student->isNewlyAdmitted($year),
                    ]);
                    continue;
                }
                
                // Only include mandatory voteheads from fee structure
                // Optional fees should only come from OptionalFee table where status='billed' or 'pending'
                if ($votehead->is_mandatory) {
                    $items->push([
                        'votehead_id' => $votehead->id,
                        'amount' => (float)$charge->amount,
                        'origin' => 'structure',
                    ]);
                    \Log::info('Added item from structure', [
                        'votehead_id' => $votehead->id,
                        'votehead_name' => $votehead->name,
                        'amount' => $charge->amount,
                        'student_id' => $student->id,
                    ]);
                } else {
                    \Log::info('Skipped optional votehead from structure', [
                        'votehead_id' => $votehead->id,
                        'votehead_name' => $votehead->name,
                        'is_mandatory' => $votehead->is_mandatory,
                    ]);
                }
            }
        }
        
        // From optional fees (only 'billed' status - 'pending' doesn't exist in the enum)
        $optional = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed') // Only 'billed' status (enum only allows 'billed' or 'exempt')
            ->when(!empty($filters['votehead_id']), fn($q) => $q->where('votehead_id', (int)$filters['votehead_id']))
            ->get();
        
        \Log::info('Optional fees query for student', [
            'student_id' => $student->id,
            'year' => $year,
            'term' => $term,
            'found_count' => $optional->count(),
            'optional_fees' => $optional->map(function($opt) {
                return [
                    'id' => $opt->id,
                    'votehead_id' => $opt->votehead_id,
                    'amount' => $opt->amount,
                    'status' => $opt->status,
                ];
            })->toArray(),
        ]);
        
        foreach ($optional as $opt) {
            $items->push([
                'votehead_id' => $opt->votehead_id,
                'amount' => (float)($opt->amount ?? 0),
                'origin' => 'optional',
            ]);
        }
        
        // Deduplicate and sum
        return $items->groupBy('votehead_id')->map(function ($group) {
            $first = $group->first();
            $first['amount'] = $group->sum('amount');
            $first['origin'] = $group->pluck('origin')->unique()->join('+');
            return $first;
        })->values();
    }
    
    /**
     * Calculate diff between existing and proposed
     */
    private function calculateDiff(Student $student, int $voteheadId, ?array $existing, array $proposed): ?array
    {
        $oldAmount = (float)($existing['amount'] ?? 0);
        $newAmount = (float)($proposed['amount'] ?? 0);
        
        // If unchanged and exists, return null to skip it completely (don't include in diffs)
        // Use small epsilon for float comparison to handle floating point precision
        if ($existing && abs($oldAmount - $newAmount) < 0.01) {
            return null; // Skip unchanged items - they don't need to be processed
        }
        
        if (!$existing) {
            return [
                'action' => 'added',
                'student_id' => $student->id,
                'votehead_id' => $voteheadId,
                'old_amount' => null,
                'new_amount' => $newAmount,
                'invoice_item_id' => null,
                'origin' => $proposed['origin'] ?? 'structure',
            ];
        }
        
        if ($newAmount > $oldAmount) {
            return [
                'action' => 'increased',
                'student_id' => $student->id,
                'votehead_id' => $voteheadId,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'invoice_item_id' => $existing['id'],
                'origin' => $proposed['origin'] ?? 'structure',
            ];
        }
        
        if ($newAmount < $oldAmount) {
            return [
                'action' => 'decreased',
                'student_id' => $student->id,
                'votehead_id' => $voteheadId,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'invoice_item_id' => $existing['id'],
                'origin' => $proposed['origin'] ?? 'structure',
            ];
        }
        
        return null;
    }
    
    /**
     * Calculate summary statistics
     */
    private function calculateSummary(Collection $diffs): array
    {
        return [
            'total' => $diffs->count(),
            'added' => $diffs->where('action', 'added')->count(),
            'increased' => $diffs->where('action', 'increased')->count(),
            'decreased' => $diffs->where('action', 'decreased')->count(),
            'unchanged' => 0, // Unchanged items are not included in diffs
            'removed' => $diffs->where('action', 'removed')->count(),
            'total_amount_change' => $diffs->sum(function ($diff) {
                return ($diff['new_amount'] ?? 0) - ($diff['old_amount'] ?? 0);
            }),
        ];
    }
    
    /**
     * Charge fees for a newly admitted student
     * This should be called when a student is created/admitted
     * 
     * @param Student $student The newly admitted student
     * @param int|null $year Academic year (defaults to current year)
     * @param int|null $term Term (defaults to current term)
     * @return int Number of invoice items created
     */
    public static function chargeFeesForNewStudent(Student $student, ?int $year = null, ?int $term = null): int
    {
        if (!$student->classroom_id) {
            return 0; // No class assigned yet
        }
        
        // Get current year/term if not provided
        if ($year === null) {
            $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
            $year = $academicYear ? $academicYear->year : (int)date('Y');
        }
        
        if ($term === null) {
            $termModel = \App\Models\Term::whereHas('academicYear', function($q) use ($year) {
                $q->where('year', $year)->where('is_active', true);
            })->where('is_active', true)->first();
            $term = $termModel ? (int)substr($termModel->name, -1) : 1;
        }
        
        // Get fee structure for student's class
        $structure = FeeStructure::with('charges.votehead')
            ->where('classroom_id', $student->classroom_id)
            ->where('year', $year)
            ->where('is_active', true)
            ->first();
        
        if (!$structure) {
            return 0; // No fee structure found
        }
        
        $itemsCreated = 0;
        
        DB::transaction(function () use ($student, $structure, $year, $term, &$itemsCreated) {
            $invoice = InvoiceService::ensure($student->id, $year, $term);
            
            // Get all charges that should be applied
            // For new students: charge in admission term (regardless of preferred_term for yearly fees)
            $charges = collect();
            
            // First, get regular charges for current term
            $regularCharges = $structure->charges->where('term', $term);
            $charges = $charges->merge($regularCharges);
            
            // For once_annually fees: new students get charged in admission term
            // Find yearly fees (may be stored in preferred_term or any term)
            $yearlyCharges = $structure->charges->filter(function($charge) {
                $votehead = $charge->votehead;
                return $votehead && $votehead->charge_type === 'once_annually';
            });
            
            // For new students, include yearly fees (we'll charge them in admission term)
            // Use the amount from any term (yearly fees have same amount regardless of term)
            foreach ($yearlyCharges as $yearlyCharge) {
                // Check if we already have this votehead in regular charges
                $alreadyIncluded = $charges->contains(function($c) use ($yearlyCharge) {
                    return $c->votehead_id === $yearlyCharge->votehead_id;
                });
                
                if (!$alreadyIncluded) {
                    // Include the yearly charge (amount is same regardless of term)
                    // canChargeForStudent will allow charging in admission term for new students
                    $charges->push($yearlyCharge);
                }
            }
            
            foreach ($charges as $charge) {
                $votehead = $charge->votehead;
                if (!$votehead || !$votehead->is_mandatory) continue;
                
                // Check if can charge (handles once-only for new students, once_annually rules, etc.)
                if (!$votehead->canChargeForStudent($student, $year, $term)) {
                    continue;
                }
                
                // Apply fee concessions
                $amount = $charge->amount;
                $concession = \App\Models\FeeConcession::where('student_id', $student->id)
                    ->where(function($q) use ($votehead) {
                        $q->whereNull('votehead_id')
                          ->orWhere('votehead_id', $votehead->id);
                    })
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    })
                    ->first();
                
                if ($concession) {
                    $discount = $concession->calculateDiscount($amount);
                    $amount = $amount - $discount;
                }
                
                // Create invoice item
                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $invoice->id, 'votehead_id' => $votehead->id],
                    [
                        'amount' => $amount,
                        'status' => 'active',
                        'source' => 'structure',
                    ]
                );
                
                $itemsCreated++;
            }
            
            // Recalculate invoice
            InvoiceService::recalc($invoice);
        });
        
        return $itemsCreated;
    }
}


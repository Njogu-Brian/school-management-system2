<?php

namespace App\Services;

use App\Models\{
    Student, Votehead, FeeStructure, FeeCharge, OptionalFee, InvoiceItem,
    Invoice, FeePostingRun, PostingDiff, AcademicYear, Term, FeeConcession, User
};
use App\Services\{DiscountService, InvoiceService, TransportFeeService};
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
            
            // Calculate diffs
            foreach ($proposedItems as $proposed) {
                $voteheadId = $proposed['votehead_id'];
                $existing = $existingItems->firstWhere('votehead_id', $voteheadId);
                
                $diff = $this->calculateDiff($student, $voteheadId, $existing, $proposed);
                if ($diff) {
                    $diffs->push($diff);
                }
            }
            
            // Check for removed items (existing but not in proposed)
            // Only mark items as removed if they came from fee structure (source='structure')
            // AND they're not in the proposed items from fee structure
            // Items with other sources (optional, manual, journal) are managed separately
            // BUT if they're also in the fee structure, they should be updated to match, not removed
            // CRITICAL: Respect votehead types - once_annually fees already charged this year should not be removed
            // CRITICAL: For optional fees (source='optional'), check if they still exist in OptionalFee table
            // If an optional fee was previously billed but is no longer in OptionalFee table, mark it for removal
            // CRITICAL: If votehead_id filter is applied, only check removal for that votehead
            foreach ($existingItems as $existing) {
                $source = $existing['source'] ?? 'structure';
                $voteheadId = $existing['votehead_id'];
                
                // If votehead filter is applied, only process this existing item if it matches the filter
                if (!empty($filters['votehead_id']) && $voteheadId != (int)$filters['votehead_id']) {
                    continue;
                }
                
                // Get the votehead to check its charge type
                $votehead = \App\Models\Votehead::find($voteheadId);
                
                // Check if this votehead is in the proposed items (from fee structure)
                $proposedItem = $proposedItems->firstWhere('votehead_id', $voteheadId);
                
                // If the votehead is in proposed items (fee structure), it should be updated, not removed
                // This handles cases where school diary/textbook fees exist with source='optional' or 'manual'
                // but are also in the fee structure - they should match the fee structure
                if ($proposedItem) {
                    // The item exists and is also in fee structure - it will be handled by the diff calculation above
                    // No need to mark as removed
                    continue;
                }
                
                // For optional fees (source='optional'), check if they still exist in OptionalFee table
                // If an optional fee was previously billed but is no longer in OptionalFee table, mark it for removal
                if ($source === 'optional') {
                    $optionalFeeExists = OptionalFee::where('student_id', $student->id)
                        ->where('votehead_id', $voteheadId)
                        ->where('year', $year)
                        ->where('term', $term)
                        ->where('status', 'billed')
                        ->exists();
                    
                    // If optional fee no longer exists, mark the invoice item for removal
                    if (!$optionalFeeExists) {
                        $diff = $this->calculateDiff($student, $voteheadId, $existing, [
                            'votehead_id' => $voteheadId,
                            'amount' => 0,
                            'origin' => 'optional',
                        ]);
                        if ($diff && isset($diff['action'])) {
                            $diff['action'] = 'removed';
                            $diffs->push($diff);
                        }
                        continue;
                    }
                }
                
                // CRITICAL: For once_annually voteheads, check if they should be removed
                // once_annually fees should persist throughout the year if:
                // 1. They're in the current fee structure for the student's class, AND
                // 2. They were already charged this year
                // They should only be removed if they're NOT in the current fee structure (e.g., student changed grade)
                if ($votehead && $votehead->charge_type === 'once_annually') {
                    // Check if this votehead is in the fee structure for the student's CURRENT classroom
                    $structureQuery = FeeStructure::where('classroom_id', $student->classroom_id)
                        ->where('is_active', true);
                    
                    // Try to match academic_year_id first, fallback to year column
                    $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
                    if ($academicYear) {
                        $structureQuery->where(function($q) use ($academicYear, $year) {
                            $q->where('academic_year_id', $academicYear->id)
                              ->orWhere('year', $year);
                        });
                    } else {
                        $structureQuery->where('year', $year);
                    }
                    
                    // Match student category
                    if ($student->category_id === null) {
                        $structureQuery->whereNull('student_category_id');
                    } else {
                        $structureQuery->where('student_category_id', $student->category_id);
                    }
                    
                    // Match stream if set
                    if ($student->stream_id) {
                        $structureQuery->where(function($q) use ($student) {
                            $q->where('stream_id', $student->stream_id)
                              ->orWhereNull('stream_id');
                        });
                    } else {
                        $structureQuery->whereNull('stream_id');
                    }
                    
                    $structure = $structureQuery->first();
                    $inFeeStructure = false;
                    
                    if ($structure) {
                        // Check if votehead exists in this fee structure (any term, since once_annually applies to all terms)
                        $inFeeStructure = FeeCharge::where('fee_structure_id', $structure->id)
                            ->where('votehead_id', $voteheadId)
                            ->exists();
                    }
                    
                    // Check if it was already charged this year
                    $existsThisYear = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student, $year) {
                        $q->where('student_id', $student->id)->where('year', $year);
                    })
                    ->where('votehead_id', $voteheadId)
                    ->where('status', 'active')
                    ->exists();
                    
                    // If it's in the fee structure AND was already charged this year, keep it
                    // (once_annually fees persist throughout the year if still in fee structure)
                    if ($inFeeStructure && $existsThisYear) {
                        continue; // Don't remove once_annually fees that are in fee structure and already charged
                    }
                    
                    // If it's NOT in the fee structure, it should be removed (e.g., student changed grade)
                    // This will proceed to the removal check below
                }
                
                // CRITICAL: For 'once' type voteheads (one-time fees), if already charged, don't remove them
                // They should remain permanently once charged, as they are one-time charges
                // This is because once fees are charged only once (typically at admission) and should persist
                if ($votehead && $votehead->charge_type === 'once') {
                    // Check if this votehead exists in any invoice for this student (ever, not just this year)
                    // If it exists, it means it was already charged, so don't remove it
                    $existsEver = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })
                    ->where('votehead_id', $voteheadId)
                    ->where('status', 'active')
                    ->exists();
                    
                    // If it exists, don't remove it - once fees persist permanently once charged
                    if ($existsEver) {
                        continue; // Don't remove once fees that have been charged
                    }
                }
                
                // Skip items that are not from fee structure - they're managed separately
                // - 'optional': managed via OptionalFee table (unless also in fee structure, handled above)
                // - 'manual': manually added, shouldn't be removed by posting (unless also in fee structure, handled above)
                // - 'journal': added via credit/debit adjustments, shouldn't be removed
                // - 'transport': managed via TransportFeeService
                // - 'balance_brought_forward': managed separately
                if ($source !== 'structure') {
                    continue;
                }
                
                // Only mark as removed if:
                // 1. It came from fee structure (source='structure')
                // 2. It's not in the proposed items (not in current fee structure)
                // 3. It's not a once_annually fee already charged this year (handled above)
                // 4. It's not a once fee already charged (handled above)
                $diffs->push([
                    'action' => 'removed',
                    'student_id' => $student->id,
                    'votehead_id' => $voteheadId,
                    'old_amount' => $existing['amount'],
                    'new_amount' => 0,
                    'invoice_item_id' => $existing['id'],
                    'origin' => $source,
                ]);
            }
        }
        
        // Filter out unchanged items - user only wants to see actual changes
        $diffs = $diffs->filter(function($diff) {
            return isset($diff['action']) && $diff['action'] !== 'unchanged';
        });
        
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
            $run = FeePostingRun::create([
                'academic_year_id' => AcademicYear::where('year', $year)->first()->id ?? null,
                'term_id' => Term::whereHas('academicYear', fn($q) => $q->where('year', $year))
                    ->where('name', 'like', "%Term {$term}%")->first()->id ?? null,
                'run_type' => 'commit',
                'status' => 'pending',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'filters_applied' => $filters,
                'items_posted_count' => 0,
            ]);
            
            $count = 0;
            
            foreach ($diffs as $diff) {
                // Skip unchanged items
                if (isset($diff['action']) && $diff['action'] === 'unchanged') {
                    continue;
                }
                
                // Skip if votehead_id is missing
                if (!isset($diff['votehead_id']) || empty($diff['votehead_id'])) {
                    continue;
                }
                
                // Skip transport, balance brought forward, and swimming daily attendance - managed separately
                $source = $diff['origin'] ?? 'structure';
                if ($source === 'transport' || $source === 'balance_brought_forward' || $source === 'swimming_attendance') {
                    continue;
                }
                
                // Additional safeguard: check votehead code for balance brought forward
                $votehead = \App\Models\Votehead::find($diff['votehead_id']);
                if ($votehead && ($votehead->code === 'BAL_BF' || strtoupper($votehead->code) === 'TRANSPORT')) {
                    continue;
                }
                
                // Handle removals (for both optional fees and structure fees that are no longer in the fee structure)
                if (isset($diff['action']) && $diff['action'] === 'removed') {
                    $invoice = Invoice::where('student_id', $diff['student_id'])
                        ->where('year', $year)
                        ->where('term', $term)
                        ->first();
                    
                    if ($invoice) {
                        // Find the existing item - check by votehead_id and source
                        $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                            ->where('votehead_id', $diff['votehead_id'])
                            ->where('source', $source)
                            ->first();
                        
                        // If not found by source, try finding by votehead_id only (for structure fees that might have been changed)
                        if (!$existingItem && $source === 'structure') {
                            $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                                ->where('votehead_id', $diff['votehead_id'])
                                ->where('source', 'structure')
                                ->first();
                        }
                        
                        if ($existingItem) {
                            // Check if item has payment allocations - if so, we need to handle them
                            $hasAllocations = $existingItem->allocations()->exists();
                            
                            if ($hasAllocations) {
                                // If item has allocations, we can't just delete it
                                // Instead, set amount to 0 and mark as removed
                                // This preserves payment history
                                $existingItem->update([
                                    'amount' => 0,
                                    'original_amount' => 0,
                                    'posting_run_id' => $run->id,
                                    'posted_at' => now(),
                                ]);
                            } else {
                                // No allocations - safe to delete
                                // Create diff record for removal before deleting
                                PostingDiff::create([
                                    'posting_run_id' => $run->id,
                                    'student_id' => $diff['student_id'],
                                    'votehead_id' => $diff['votehead_id'],
                                    'action' => 'removed',
                                    'old_amount' => $existingItem->amount,
                                    'new_amount' => 0,
                                    'invoice_item_id' => $existingItem->id,
                                    'source' => $source,
                                ]);
                                
                                // Delete the invoice item
                                $existingItem->delete();
                            }
                            
                            // Recalculate invoice
                            InvoiceService::recalc($invoice);
                            $count++;
                        }
                    }
                    continue; // Skip to next diff
                }
                
                // Idempotency check: skip if item already exists and is active
                if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                    $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                    if ($existingItem && $existingItem->status === 'active' && $existingItem->amount == ($diff['new_amount'] ?? 0)) {
                        continue;
                    }
                    // Additional safeguard: skip if existing item is transport, BBF, or swimming daily attendance
                    if ($existingItem && in_array($existingItem->source, ['transport', 'balance_brought_forward', 'swimming_attendance'])) {
                        continue;
                    }
                }

                $invoice = InvoiceService::ensure($diff['student_id'], $year, $term);
                // Note: Balance brought forward is automatically added in InvoiceService::ensure() for first term of 2026
                
                // Check if item already exists (check ALL sources first to avoid unique constraint violation)
                // The unique constraint is on invoice_id + votehead_id, so we need to check all items
                $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $diff['votehead_id'])
                    ->first();
                
                // Skip if item exists and is transport, BBF, or swimming daily attendance (managed separately)
                if ($existingItem && in_array($existingItem->source, ['transport', 'balance_brought_forward', 'swimming_attendance'])) {
                    continue;
                }

                // Get existing credit/debit notes if item exists
                $existingCreditNotes = 0;
                $existingDebitNotes = 0;
                if ($existingItem) {
                    $existingCreditNotes = (float)$existingItem->creditNotes()->sum('amount');
                    $existingDebitNotes = (float)$existingItem->debitNotes()->sum('amount');
                }
                
                // Calculate the final amount: new fee structure amount - credit notes + debit notes
                // This preserves existing credit/debit notes when updating the fee structure
                $newFeeStructureAmount = (float)($diff['new_amount'] ?? 0);
                $finalAmount = max(0, $newFeeStructureAmount - $existingCreditNotes + $existingDebitNotes);
                
                // original_amount should always represent the current fee structure amount (before credit notes)
                // This allows proper comparison in future postings
                $originalAmount = $newFeeStructureAmount;
                
                // Use updateOrCreate with proper error handling for race conditions
                try {
                    $item = InvoiceItem::updateOrCreate(
                        ['invoice_id' => $invoice->id, 'votehead_id' => $diff['votehead_id']],
                        [
                            'amount' => $finalAmount, // Set to fee structure amount minus credit notes
                            'original_amount' => $originalAmount, // Store current fee structure amount (before credit notes)
                            'status' => $activateNow ? 'active' : 'pending',
                            'effective_date' => $activateNow ? null : ($effectiveDate ?? null),
                            'source' => $diff['origin'] ?? 'structure',
                            'posting_run_id' => $run->id,
                            'posted_at' => now(),
                        ]
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle unique constraint violation (race condition)
                    if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique_invoice_votehead')) {
                        // Another request created the item, fetch it
                        $item = InvoiceItem::where('invoice_id', $invoice->id)
                            ->where('votehead_id', $diff['votehead_id'])
                            ->first();
                        
                        if (!$item) {
                            // Item still not found, log and skip
                            \Log::warning("Invoice item not found after unique constraint violation", [
                                'invoice_id' => $invoice->id,
                                'votehead_id' => $diff['votehead_id'],
                                'error' => $e->getMessage()
                            ]);
                            continue;
                        }
                        
                        // Update the existing item
                        $item->update([
                            'amount' => $finalAmount,
                            'original_amount' => $originalAmount,
                            'status' => $activateNow ? 'active' : 'pending',
                            'effective_date' => $activateNow ? null : ($effectiveDate ?? null),
                            'source' => $diff['origin'] ?? 'structure',
                            'posting_run_id' => $run->id,
                            'posted_at' => now(),
                        ]);
                    } else {
                        // Re-throw if it's not a unique constraint violation
                        throw $e;
                    }
                }
                
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
                'is_active' => true, // Mark as active when completed
            ]);
            
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
            throw new \Exception('This posting run cannot be reversed. It may already be reversed, not completed, or not a commit run.');
        }
        
        return DB::transaction(function () use ($run) {
            // Load items from this run with their relationships
            // Also check items that might not have posting_run_id set (for backward compatibility)
            $items = InvoiceItem::with(['allocations', 'invoice'])
                ->where('posting_run_id', $run->id)
                ->get();
            
            // If no items found by posting_run_id, try to find by diffs
            if ($items->isEmpty()) {
                $diffs = PostingDiff::where('posting_run_id', $run->id)
                    ->whereNotNull('invoice_item_id')
                    ->get();
                
                if ($diffs->isNotEmpty()) {
                    $itemIds = $diffs->pluck('invoice_item_id')->unique();
                    $items = InvoiceItem::with(['allocations', 'invoice'])
                        ->whereIn('id', $itemIds)
                        ->get();
                }
            }
            
            if ($items->isEmpty()) {
                throw new \Exception(
                    "No invoice items found for posting run #{$run->id}. " .
                    "The items may have already been reversed, manually deleted, or the posting run may not have created any items."
                );
            }
            
            // Collect invoice IDs and payment IDs for recalculation
            $invoiceIds = collect();
            $paymentIds = collect();
            
            // First, handle payment allocations - delete them to free up payments for carry forward
            foreach ($items as $item) {
                // Store invoice ID
                if ($item->invoice) {
                    $invoiceIds->push($item->invoice_id);
                }
                
                // If item has allocations, delete them and collect payment IDs
                if ($item->allocations && $item->allocations->isNotEmpty()) {
                    foreach ($item->allocations as $allocation) {
                        // Collect payment ID before deleting allocation
                        if ($allocation->payment_id) {
                            $paymentIds->push($allocation->payment_id);
                        }
                        // Delete the allocation - this frees up the payment amount
                        $allocation->delete();
                    }
                }
                
                // Create reversal diff
                PostingDiff::create([
                    'posting_run_id' => $run->id,
                    'student_id' => $item->invoice ? $item->invoice->student_id : null,
                    'votehead_id' => $item->votehead_id,
                    'action' => 'removed',
                    'old_amount' => $item->amount,
                    'new_amount' => 0,
                    'invoice_item_id' => $item->id,
                    'source' => $item->source ?? 'structure',
                ]);
                
                // Delete the invoice item
                $item->delete();
            }
            
            // Update payment allocation totals for affected payments
            // This ensures unallocated_amount is correctly calculated for carry forward
            $affectedPaymentCount = 0;
            if ($paymentIds->isNotEmpty()) {
                $payments = \App\Models\Payment::whereIn('id', $paymentIds->unique())->get();
                $affectedPaymentCount = $payments->count();
                foreach ($payments as $payment) {
                    $payment->updateAllocationTotals();
                }
            }
            
            // Delete all invoices affected by this posting run
            // Since items are already deleted above, we check remaining items on each invoice
            $invoices = Invoice::whereIn('id', $invoiceIds->unique())->get();
            $deletedInvoiceCount = 0;
            
            foreach ($invoices as $invoice) {
                // Refresh to get updated item count after deletions
                $invoice->refresh();
                
                // Check remaining items (items that weren't deleted - from other posting runs or manual)
                $remainingItems = $invoice->items()->count();
                
                // Delete invoice if:
                // 1. It was created by this posting run, OR
                // 2. It has no remaining items after deleting items from this posting run
                if ($invoice->posting_run_id === $run->id || $remainingItems === 0) {
                    // Delete related records first
                    $invoice->creditNotes()->delete();
                    $invoice->debitNotes()->delete();
                    $invoice->feeConcessions()->delete();
                    
                    // Delete the invoice itself
                    $invoice->delete();
                    $deletedInvoiceCount++;
                } else {
                    // Recalculate invoice if it still has other items from other posting runs
                InvoiceService::recalc($invoice);
                }
            }
            
            // Also find and delete any invoices directly linked to this posting run
            // (in case they weren't picked up in the items loop above)
            $directInvoices = Invoice::where('posting_run_id', $run->id)
                ->whereNotIn('id', $invoiceIds->unique())
                ->get();
                
            foreach ($directInvoices as $invoice) {
                $invoice->creditNotes()->delete();
                $invoice->debitNotes()->delete();
                $invoice->feeConcessions()->delete();
                $invoice->delete();
                $deletedInvoiceCount++;
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
                'items_posted_count' => $items->count(),
            ]);
            
            // Prepare notes with payment count and invoice deletion info
            $notes = $run->notes;
            if ($affectedPaymentCount > 0) {
                $notes = ($notes ? $notes . "\n" : '') . 
                        "Reversal freed {$affectedPaymentCount} payment(s) for carry forward.";
            }
            if ($deletedInvoiceCount > 0) {
                $notes = ($notes ? $notes . "\n" : '') . 
                        "Deleted {$deletedInvoiceCount} invoice(s) that were created by this posting run.";
            }
            
            $run->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'total_amount_posted' => 0,
                'is_active' => false, // Mark as inactive when reversed
                'notes' => $notes,
            ]);
            
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
     * Reverse posting for a specific student within a posting run
     * This allows reversing individual student postings without affecting other students
     */
    public function reverseStudentPosting(FeePostingRun $run, int $studentId): bool
    {
        if (!$run->canBeReversed()) {
            throw new \Exception('This posting run cannot be reversed. It may already be reversed, not completed, or not a commit run.');
        }
        
        // Ensure relationships are loaded
        $run->load(['academicYear', 'term']);
        
        // Get the student
        $student = Student::findOrFail($studentId);
        
        return DB::transaction(function () use ($run, $student) {
            // Get all diffs for this student in this posting run (excluding reversal diffs)
            $studentDiffs = PostingDiff::where('posting_run_id', $run->id)
                ->where('student_id', $student->id)
                ->where('action', '!=', 'reversed') // Exclude reversal records
                ->get();
            
            if ($studentDiffs->isEmpty()) {
                throw new \Exception("No posting changes found for student {$student->first_name} {$student->last_name} in posting run #{$run->id}, or they may have already been reversed.");
            }
            
            // Check if student has already been reversed (items no longer linked to this run)
            // Use a more flexible check - look for items that exist and are linked to this run
            $existingItems = InvoiceItem::whereHas('invoice', function($q) use ($student, $run) {
                $q->where('student_id', $student->id);
                // Match by academic year and term
                if ($run->academicYear) {
                    $q->where(function($query) use ($run) {
                        $query->where('academic_year_id', $run->academic_year_id)
                              ->orWhere('year', $run->academicYear->year);
                    });
                }
                if ($run->term) {
                    $q->where(function($query) use ($run) {
                        $query->where('term_id', $run->term_id)
                              ->orWhere('term', $run->term->term);
                    });
                }
            })
            ->where('posting_run_id', $run->id)
            ->count();
            
            if ($existingItems === 0) {
                throw new \Exception("Student {$student->first_name} {$student->last_name} has already been reversed for this posting run, or no items were found linked to this run.");
            }
            
            // Collect invoice IDs and payment IDs for recalculation
            $invoiceIds = collect();
            $paymentIds = collect();
            $itemsProcessed = 0;
            $itemsDeleted = 0;
            $itemsRestored = 0;
            
            // First, get all invoice items for this student that are linked to this posting run
            // This is more reliable than relying on diffs
            $studentInvoice = Invoice::where('student_id', $student->id)
                ->where(function($q) use ($run) {
                    if ($run->academicYear) {
                        $q->where(function($query) use ($run) {
                            $query->where('academic_year_id', $run->academic_year_id)
                                  ->orWhere('year', $run->academicYear->year);
                        });
                    }
                    if ($run->term) {
                        $q->where(function($query) use ($run) {
                            $query->where('term_id', $run->term_id)
                                  ->orWhere('term', $run->term->term);
                        });
                    }
                })
                ->first();
            
            if (!$studentInvoice) {
                throw new \Exception("No invoice found for student {$student->first_name} {$student->last_name} for the academic year and term of this posting run.");
            }
            
            // Get all items linked to this posting run for this student
            $studentItems = InvoiceItem::with(['allocations', 'invoice'])
                ->where('invoice_id', $studentInvoice->id)
                ->where('posting_run_id', $run->id)
                ->get()
                ->keyBy('votehead_id'); // Key by votehead_id for easy lookup
            
            // Process each diff
            foreach ($studentDiffs as $diff) {
                $invoiceItem = null;
                
                // First, try to find by votehead_id from our pre-loaded items
                if ($diff->votehead_id && isset($studentItems[$diff->votehead_id])) {
                    $invoiceItem = $studentItems[$diff->votehead_id];
                }
                
                // If not found, try by invoice_item_id from diff
                if (!$invoiceItem && $diff->invoice_item_id) {
                    $invoiceItem = InvoiceItem::with(['allocations', 'invoice'])
                        ->where('id', $diff->invoice_item_id)
                        ->where('posting_run_id', $run->id)
                        ->first();
                }
                
                // If still not found, try without posting_run_id check (in case it wasn't set properly)
                if (!$invoiceItem && $diff->votehead_id) {
                    $invoiceItem = InvoiceItem::with(['allocations', 'invoice'])
                        ->where('invoice_id', $studentInvoice->id)
                        ->where('votehead_id', $diff->votehead_id)
                        ->first();
                }
                
                if (!$invoiceItem) {
                    // Item doesn't exist (might have been deleted already or was a "removed" action)
                    // For "removed" actions, we skip restoration (too complex without full context)
                    // For other actions, log a warning but continue
                    if ($diff->action !== 'removed') {
                        \Log::warning("Invoice item not found for diff #{$diff->id} (votehead_id: {$diff->votehead_id}) in posting run #{$run->id} for student {$student->id}");
                    }
                    continue;
                }
                
                // Double-check that this item belongs to this posting run
                // If it doesn't have posting_run_id set, set it now (for consistency)
                if ($invoiceItem->posting_run_id !== $run->id) {
                    // Only process if the action was "added" (new item) or if we're sure it's from this run
                    $shouldProcess = false;
                    if ($diff->action === 'added') {
                        $shouldProcess = true;
                    } elseif ($invoiceItem->posted_at && $run->posted_at) {
                        // Check if posted on the same day (within reasonable time window)
                        $shouldProcess = $invoiceItem->posted_at->isSameDay($run->posted_at);
                    } elseif ($diff->action !== 'removed') {
                        // For other actions, if no posting_run_id is set, assume it's from this run
                        // This handles legacy items or items that weren't properly linked
                        $shouldProcess = true;
                    }
                    
                    if ($shouldProcess) {
                        $invoiceItem->update(['posting_run_id' => $run->id]);
                    } else {
                        // Skip this item - it might be from a different posting run
                        \Log::info("Skipping item #{$invoiceItem->id} for diff #{$diff->id} - doesn't match posting run");
                        continue;
                    }
                }
                
                // Store invoice ID
                if ($invoiceItem->invoice) {
                    $invoiceIds->push($invoiceItem->invoice_id);
                }
                
                // Handle payment allocations - delete them to free up payments
                if ($invoiceItem->allocations && $invoiceItem->allocations->isNotEmpty()) {
                    foreach ($invoiceItem->allocations as $allocation) {
                        if ($allocation->payment_id) {
                            $paymentIds->push($allocation->payment_id);
                        }
                        $allocation->delete();
                    }
                }
                
                // Handle different action types
                switch ($diff->action) {
                    case 'added':
                        // Item was added by this posting - delete it
                        $invoiceItem->delete();
                        $itemsDeleted++;
                        break;
                        
                    case 'increased':
                    case 'decreased':
                        // Item amount was changed - restore to old amount
                        $oldAmount = (float)($diff->old_amount ?? 0);
                        $oldOriginalAmount = $oldAmount; // Try to preserve original_amount if possible
                        
                        // If we have the old original_amount from before posting, use it
                        // Otherwise, use old_amount as original_amount
                        $invoiceItem->update([
                            'amount' => $oldAmount,
                            'original_amount' => $oldOriginalAmount,
                            'posting_run_id' => null, // Remove link to this posting run
                        ]);
                        $itemsRestored++;
                        break;
                        
                    case 'unchanged':
                        // Item was unchanged - just remove the posting_run_id link
                        // But if the amount actually changed (edge case), restore it
                        $oldAmount = (float)($diff->old_amount ?? 0);
                        if (abs($invoiceItem->amount - $oldAmount) > 0.01) {
                            $invoiceItem->update([
                                'amount' => $oldAmount,
                                'original_amount' => $oldAmount,
                                'posting_run_id' => null,
                            ]);
                            $itemsRestored++;
                        } else {
                            $invoiceItem->update([
                                'posting_run_id' => null,
                            ]);
                        }
                        break;
                        
                    case 'removed':
                        // Item was removed - we could restore it, but that's complex
                        // For now, if the item still exists, just remove the posting_run_id link
                        // If it doesn't exist, we skip it (would need full context to restore)
                        if ($invoiceItem) {
                            $invoiceItem->update([
                                'posting_run_id' => null,
                            ]);
                        }
                        break;
                }
                
                $itemsProcessed++;
                
                // Create reversal diff record
                PostingDiff::create([
                    'posting_run_id' => $run->id,
                    'student_id' => $student->id,
                    'votehead_id' => $diff->votehead_id,
                    'action' => 'reversed',
                    'old_amount' => $diff->new_amount, // Current amount (after posting)
                    'new_amount' => $diff->old_amount, // Restored to old amount
                    'invoice_item_id' => $invoiceItem->id, // Use the actual item ID we found
                    'source' => $diff->source ?? 'structure',
                ]);
            }
            
            // If no items were processed, throw an error
            if ($itemsProcessed === 0) {
                throw new \Exception("No invoice items were found to reverse for student {$student->first_name} {$student->last_name}. The items may have already been reversed or deleted.");
            }
            
            // Update payment allocation totals for affected payments
            $affectedPaymentCount = 0;
            if ($paymentIds->isNotEmpty()) {
                $payments = \App\Models\Payment::whereIn('id', $paymentIds->unique())->get();
                $affectedPaymentCount = $payments->count();
                foreach ($payments as $payment) {
                    $payment->updateAllocationTotals();
                }
            }
            
            // Recalculate affected invoices
            $invoices = Invoice::whereIn('id', $invoiceIds->unique())->get();
            foreach ($invoices as $invoice) {
                $invoice->refresh();
                
                // Check if invoice has any remaining items
                $remainingItems = $invoice->items()->count();
                
                // If invoice was created by this posting run and has no items, delete it
                if ($invoice->posting_run_id === $run->id && $remainingItems === 0) {
                    $invoice->creditNotes()->delete();
                    $invoice->debitNotes()->delete();
                    $invoice->feeConcessions()->delete();
                    $invoice->delete();
                } else {
                    // Recalculate invoice totals
                    InvoiceService::recalc($invoice);
                }
            }
            
            // Update run notes to track partial reversal
            $notes = $run->notes ?? '';
            $reversalNote = "Student reversal: {$student->first_name} {$student->last_name} ({$student->admission_number}) - {$itemsProcessed} item(s) processed ({$itemsDeleted} deleted, {$itemsRestored} restored)";
            if ($affectedPaymentCount > 0) {
                $reversalNote .= ", {$affectedPaymentCount} payment(s) freed";
            }
            $notes = ($notes ? $notes . "\n" : '') . $reversalNote;
            
            $run->update([
                'notes' => $notes,
            ]);
            
            // Log audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'reversed_student',
                    $run,
                    ['student_id' => $student->id],
                    ['items_processed' => $itemsProcessed, 'items_deleted' => $itemsDeleted, 'items_restored' => $itemsRestored],
                    ['fee_posting', 'reversal', 'student']
                );
            }
            
            return true;
        });
    }
    
    /**
     * Get filtered students
     * IMPORTANT: If student_id is provided, ONLY return that student (ignore class/stream filters)
     */
    private function getFilteredStudents(array $filters): Collection
    {
        // If student_id is explicitly provided, ONLY return that student
        // This ensures optional fees are only applied to the selected student
        // But still exclude alumni/archived students
        if (!empty($filters['student_id'])) {
            return Student::where('id', $filters['student_id'])
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get();
        }
        
        // Otherwise, apply class/stream filters (exclude alumni/archived)
        return Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when(!empty($filters['class_id']), fn($q) => $q->where('classroom_id', $filters['class_id']))
            ->when(!empty($filters['stream_id']), fn($q) => $q->where('stream_id', $filters['stream_id']))
            ->when(!empty($filters['student_category_id']), fn($q) => $q->where('category_id', $filters['student_category_id']))
            ->get();
    }
    
    /**
     * Get existing invoice items
     * Returns the original amount before credit/debit notes for proper diff calculation
     * Excludes transport, balance brought forward, and swimming daily attendance (managed separately)
     */
    private function getExistingInvoiceItems(int $studentId, int $year, int $term): Collection
    {
        $excludeSources = function ($q) {
            $q->where('source', '!=', 'transport')
              ->where('source', '!=', 'balance_brought_forward')
              ->where('source', '!=', 'swimming_attendance');
        };

        $invoice = Invoice::where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();

        if (!$invoice) {
            // If no invoice for this specific term, check if items exist in any invoice for this student/year
            $allInvoices = Invoice::where('student_id', $studentId)
                ->where('year', $year)
                ->get();

            $allItems = collect();
            foreach ($allInvoices as $inv) {
                $allItems = $allItems->merge(
                    $inv->items()
                        ->where('status', 'active')
                        ->where($excludeSources)
                        ->with(['creditNotes', 'debitNotes'])
                        ->get()
                );
            }

            return $allItems->map(function ($item) {
                $originalAmount = $this->getOriginalAmountBeforeNotes($item);
                return [
                    'id' => $item->id,
                    'votehead_id' => $item->votehead_id,
                    'amount' => $originalAmount,
                    'source' => $item->source,
                ];
            });
        }

        // Only include ACTIVE items; exclude transport, BBF, and swimming daily attendance
        return $invoice->items()
            ->where('status', 'active')
            ->where($excludeSources)
            ->with(['creditNotes', 'debitNotes'])
            ->get()
            ->map(function ($item) {
                $originalAmount = $this->getOriginalAmountBeforeNotes($item);
                return [
                    'id' => $item->id,
                    'votehead_id' => $item->votehead_id,
                    'amount' => $originalAmount,
                    'source' => $item->source,
                ];
            });
    }
    
    /**
     * Get the original amount before credit/debit notes and discounts were applied
     * This is used to properly compare with new fee structure amounts
     * We ignore discounts and credit notes when comparing with fee structure
     */
    private function getOriginalAmountBeforeNotes(InvoiceItem $item): float
    {
        // If original_amount is set, use it (it should represent the amount before credit notes)
        if ($item->original_amount !== null && $item->original_amount > 0) {
            return (float)$item->original_amount;
        }
        
        // Otherwise, calculate by adding back credit notes and subtracting debit notes
        // Also add back discount_amount since we want to compare base fee structure amounts
        $creditNotesTotal = $item->creditNotes()->sum('amount');
        $debitNotesTotal = $item->debitNotes()->sum('amount');
        $discountAmount = (float)($item->discount_amount ?? 0);
        $originalAmount = (float)$item->amount + (float)$creditNotesTotal - (float)$debitNotesTotal + $discountAmount;
        
        return max(0, $originalAmount); // Ensure non-negative
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
        $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
        if ($academicYear) {
            $structureQuery->where(function($q) use ($academicYear, $year) {
                $q->where('academic_year_id', $academicYear->id)
                  ->orWhere('year', $year);
            });
        } else {
            $structureQuery->where('year', $year);
        }
        
        // Match student category strictly (no general structures)
        // Handle NULL category_id properly - if student has no category, match structures with NULL category_id
        // If student has a category, match structures with that exact category_id
        if ($student->category_id === null) {
            $structureQuery->whereNull('student_category_id');
        } else {
            $structureQuery->where('student_category_id', $student->category_id);
        }
        
        // Match stream if set; if none, require structures without stream
        if ($student->stream_id) {
            $structureQuery->where(function($q) use ($student) {
                $q->where('stream_id', $student->stream_id)
                  ->orWhereNull('stream_id'); // allow class-wide structures still
            });
        } else {
            $structureQuery->whereNull('stream_id');
        }
        
        // Order by specificity: stream-specific > general within the category
        $structureQuery->orderByRaw('CASE WHEN stream_id IS NOT NULL THEN 0 ELSE 1 END');
        
        $structure = $structureQuery->first();
        
        if ($structure) {
            // For once_annually fees with preferred_term, check if we should include them
            $charges = $structure->charges;
            
            // Filter by term, but handle preferred_term for once_annually
            // IMPORTANT: For 'once' type fees, only include if charge->term matches the posting term
            // For 'once_annually', check preferred_term if set
            $charges = $charges->filter(function($charge) use ($term) {
                $votehead = $charge->votehead;
                if (!$votehead) return false;
                
                // For 'once' type fees, only charge in the term specified in the charge
                if ($votehead->charge_type === 'once') {
                    return $charge->term == $term;
                }
                
                // If charge is for a different term, skip unless it's once_annually with preferred_term
                if ($charge->term != $term) {
                    // Check if it's once_annually and preferred_term matches current term
                    if ($votehead->charge_type === 'once_annually' && 
                        $votehead->preferred_term !== null && 
                        $votehead->preferred_term == $term) {
                        return true; // Include it even though charge->term doesn't match
                    }
                    return false;
                }
                
                return true;
            });
            
            if (!empty($filters['votehead_id'])) {
                $charges = $charges->where('votehead_id', (int)$filters['votehead_id']);
            }
            
            foreach ($charges as $charge) {
                $votehead = $charge->votehead;
                if (!$votehead) continue;
                
                // Check charge type constraints (handles preferred_term and once-only for new students)
                if (!$votehead->canChargeForStudent($student, $year, $term)) {
                    continue;
                }
                
                // CRITICAL: Only add MANDATORY voteheads from fee structure
                // Optional voteheads should ONLY come from the OptionalFee table (where status = 'billed')
                // This ensures optional fees are only charged when explicitly billed via Optional Fees module
                if ($votehead->is_mandatory) {
                    $items->push([
                        'votehead_id' => $votehead->id,
                        'amount' => (float)$charge->amount,
                        'origin' => 'structure',
                    ]);
                }
                // If votehead is optional (not mandatory), skip it here - it will only be added if
                // it exists in the OptionalFee table with status='billed' for this student
            }
        }
        
        // From optional fees - ONLY for this specific student
        // CRITICAL: Only query optional fees for the current student being processed
        // This ensures optional fees are not applied to all students
        $optional = OptionalFee::query()
            ->where('student_id', $student->id) // Explicitly filter by current student
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->when(!empty($filters['votehead_id']), fn($q) => $q->where('votehead_id', (int)$filters['votehead_id']))
            ->get();
        
        foreach ($optional as $opt) {
            $amount = (float)($opt->amount ?? 0);
            
            // If amount is 0 or null, try to get it from the fee structure
            if ($amount <= 0) {
                // Try to get amount from fee structure for this student's class
                $structureQuery = FeeStructure::with('charges')
                    ->where('classroom_id', $student->classroom_id)
                    ->where('is_active', true);
                
                // Try to match academic_year_id first, fallback to year column
                $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
                if ($academicYear) {
                    $structureQuery->where(function($q) use ($academicYear, $year) {
                        $q->where('academic_year_id', $academicYear->id)
                          ->orWhere('year', $year);
                    });
                } else {
                    $structureQuery->where('year', $year);
                }
                
                // Match student category
                if ($student->category_id === null) {
                    $structureQuery->whereNull('student_category_id');
                } else {
                    $structureQuery->where('student_category_id', $student->category_id);
                }
                
                // Match stream if set
                if ($student->stream_id) {
                    $structureQuery->where(function($q) use ($student) {
                        $q->where('stream_id', $student->stream_id)
                          ->orWhereNull('stream_id');
                    });
                } else {
                    $structureQuery->whereNull('stream_id');
                }
                
                $structure = $structureQuery->first();
                
                if ($structure) {
                    $charge = $structure->charges()
                        ->where('votehead_id', $opt->votehead_id)
                        ->where('term', $term)
                        ->first();
                    
                    if ($charge) {
                        $amount = (float)$charge->amount;
                    }
                }
                
                // If still 0, skip with warning
                if ($amount <= 0) {
                    \Log::warning('Skipping optional fee with zero amount (not found in fee structure)', [
                        'optional_fee_id' => $opt->id,
                        'student_id' => $opt->student_id,
                        'votehead_id' => $opt->votehead_id,
                        'term' => $term,
                        'year' => $year,
                    ]);
                    continue;
                }
            }
            
            // Check if this votehead is already in structure items to avoid double billing
            $alreadyInStructure = $items->contains('votehead_id', $opt->votehead_id);
            
            if (!$alreadyInStructure) {
                // Only add if not already in structure charges
                $items->push([
                    'votehead_id' => $opt->votehead_id,
                    'amount' => $amount,
                    'origin' => 'optional',
                ]);
            } else {
                // If already in structure, don't add as optional to avoid double billing
                // The structure charge will be used instead
            }
        }
        
        // Deduplicate and sum (this handles cases where same votehead appears multiple times)
        return $items->groupBy('votehead_id')->map(function ($group) {
            $first = $group->first();
            // Use the maximum amount (structure takes precedence over optional if both exist)
            $first['amount'] = $group->max('amount');
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
        
        // Use bccomp for decimal comparison to avoid floating point precision issues
        // Consider amounts equal if difference is less than 0.01
        $amountDifference = abs($oldAmount - $newAmount);
        
        if ($amountDifference < 0.01 && $existing) {
            // Amounts are effectively the same - mark as unchanged
            return [
                'action' => 'unchanged',
                'student_id' => $student->id,
                'votehead_id' => $voteheadId,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'invoice_item_id' => $existing['id'] ?? null,
                'origin' => $proposed['origin'] ?? 'structure',
            ];
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
            'unchanged' => $diffs->where('action', 'unchanged')->count(),
            'removed' => $diffs->where('action', 'removed')->count(),
            'total_amount_change' => $diffs->sum(function ($diff) {
                return ($diff['new_amount'] ?? 0) - ($diff['old_amount'] ?? 0);
            }),
        ];
    }

    /**
     * Charge mandatory fees for a newly admitted student immediately.
     */
    public static function chargeFeesForNewStudent(\App\Models\Student $student, ?int $year = null, ?int $term = null): void
    {
        [$resolvedYear, $resolvedTerm] = TransportFeeService::resolveYearAndTerm($year, $term);

        $service = new self();
        $proposed = $service->getProposedItems($student, $resolvedYear, $resolvedTerm, [
            'year' => $resolvedYear,
            'term' => $resolvedTerm,
            'student_id' => $student->id,
        ]);

        DB::transaction(function () use ($student, $resolvedYear, $resolvedTerm, $proposed) {
            $invoice = InvoiceService::ensure($student->id, $resolvedYear, $resolvedTerm);

            foreach ($proposed as $item) {
                $invoiceItem = InvoiceItem::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'votehead_id' => $item['votehead_id']],
                    [
                        'amount' => $item['amount'],
                        'status' => 'active',
                        'effective_date' => null,
                        'source' => $item['origin'] ?? 'structure',
                        'original_amount' => $item['amount'],
                        'posted_at' => now(),
                    ]
                );

                if ($invoiceItem->wasRecentlyCreated === false && $invoiceItem->original_amount === null) {
                    $invoiceItem->update(['original_amount' => $invoiceItem->amount]);
                }
            }

            // If a transport fee already exists for this term, make sure it is on the invoice
            $transportFee = \App\Models\TransportFee::where('student_id', $student->id)
                ->where('year', $resolvedYear)
                ->where('term', $resolvedTerm)
                ->first();

            if ($transportFee) {
                TransportFeeService::syncInvoice($transportFee);
            }

            InvoiceService::recalc($invoice);
        });
    }
}



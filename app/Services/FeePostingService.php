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
            // Optional fees, manual entries, journal entries, transport, and balance brought forward are managed separately
            foreach ($existingItems as $existing) {
                $source = $existing['source'] ?? 'structure';
                
                // Skip items that are not from fee structure - they're managed separately
                // - 'optional': managed via OptionalFee table
                // - 'manual': manually added, shouldn't be removed by posting
                // - 'journal': added via credit/debit adjustments, shouldn't be removed
                // - 'transport': managed via TransportFeeService
                // - 'balance_brought_forward': managed separately
                if ($source !== 'structure') {
                    continue;
                }
                
                // Only mark as removed if not in proposed items
                if (!$proposedItems->contains('votehead_id', $existing['votehead_id'])) {
                    $diffs->push([
                        'action' => 'removed',
                        'student_id' => $student->id,
                        'votehead_id' => $existing['votehead_id'],
                        'old_amount' => $existing['amount'],
                        'new_amount' => 0,
                        'invoice_item_id' => $existing['id'],
                        'origin' => $source,
                    ]);
                }
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
                
                // Skip transport fees and balance brought forward - they are managed separately
                $source = $diff['origin'] ?? 'structure';
                if ($source === 'transport' || $source === 'balance_brought_forward') {
                    continue;
                }
                
                // Additional safeguard: check votehead code for balance brought forward
                $votehead = \App\Models\Votehead::find($diff['votehead_id']);
                if ($votehead && ($votehead->code === 'BAL_BF' || strtoupper($votehead->code) === 'TRANSPORT')) {
                    continue;
                }
                
                // Idempotency check: skip if item already exists and is active
                if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                    $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                    if ($existingItem && $existingItem->status === 'active' && $existingItem->amount == ($diff['new_amount'] ?? 0)) {
                        continue;
                    }
                    // Additional safeguard: skip if existing item is transport or balance brought forward
                    if ($existingItem && ($existingItem->source === 'transport' || $existingItem->source === 'balance_brought_forward')) {
                        continue;
                    }
                }
                
                $invoice = InvoiceService::ensure($diff['student_id'], $year, $term);
                // Note: Balance brought forward is automatically added in InvoiceService::ensure() for first term of 2026
                
                // Check if item already exists to preserve credit notes
                // Exclude transport and balance brought forward items
                $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $diff['votehead_id'])
                    ->where('source', '!=', 'transport')
                    ->where('source', '!=', 'balance_brought_forward')
                    ->first();
                
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
     * Excludes transport fees and balance brought forward as they are managed separately
     */
    private function getExistingInvoiceItems(int $studentId, int $year, int $term): Collection
    {
        $invoice = Invoice::where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();
        
        if (!$invoice) {
            // If no invoice for this specific term, check if items exist in any invoice for this student/year
            // This handles cases where fees might have been posted to a different term but same year
            $allInvoices = Invoice::where('student_id', $studentId)
                ->where('year', $year)
                ->get();
            
            $allItems = collect();
            foreach ($allInvoices as $inv) {
                // Only include ACTIVE items - exclude pending items from optional fees
                // Exclude transport fees and balance brought forward (managed separately)
                // Pending items from optional fees should be treated as "new" in preview
                $allItems = $allItems->merge(
                    $inv->items()
                        ->where('status', 'active')
                        ->where('source', '!=', 'transport')
                        ->where('source', '!=', 'balance_brought_forward')
                        ->with(['creditNotes', 'debitNotes'])
                        ->get()
                );
            }
            
            return $allItems->map(function ($item) {
                // Calculate original amount before credit/debit notes
                $originalAmount = $this->getOriginalAmountBeforeNotes($item);
                return [
                    'id' => $item->id,
                    'votehead_id' => $item->votehead_id,
                    'amount' => $originalAmount, // Use original amount (before credit notes) for comparison
                    'source' => $item->source,
                ];
            });
        }
        
        // Only include ACTIVE items - exclude pending items from optional fees
        // Exclude transport fees and balance brought forward (managed separately)
        // Pending items from optional fees should be treated as "new" in preview
        // This ensures optional fees appear in preview even if they were previously billed
        return $invoice->items()
            ->where('status', 'active') // Only active items - pending optional fees will show as "added"
            ->where('source', '!=', 'transport') // Exclude transport fees (managed separately)
            ->where('source', '!=', 'balance_brought_forward') // Exclude balance brought forward (managed separately)
            ->with(['creditNotes', 'debitNotes']) // Eager load credit/debit notes
            ->get()
            ->map(function ($item) {
                // Calculate original amount before credit/debit notes
                $originalAmount = $this->getOriginalAmountBeforeNotes($item);
                return [
                    'id' => $item->id,
                    'votehead_id' => $item->votehead_id,
                    'amount' => $originalAmount, // Use original amount (before credit notes) for comparison
                    'source' => $item->source,
                ];
            });
    }
    
    /**
     * Get the original amount before credit/debit notes were applied
     * This is used to properly compare with new fee structure amounts
     */
    private function getOriginalAmountBeforeNotes(InvoiceItem $item): float
    {
        // If original_amount is set, use it (it should represent the amount before credit notes)
        if ($item->original_amount !== null && $item->original_amount > 0) {
            return (float)$item->original_amount;
        }
        
        // Otherwise, calculate by adding back credit notes and subtracting debit notes
        $creditNotesTotal = $item->creditNotes()->sum('amount');
        $debitNotesTotal = $item->debitNotes()->sum('amount');
        $originalAmount = (float)$item->amount + (float)$creditNotesTotal - (float)$debitNotesTotal;
        
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
        $structureQuery->where('student_category_id', $student->category_id);
        
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
            // Check if this votehead is already in structure items to avoid double billing
            $alreadyInStructure = $items->contains('votehead_id', $opt->votehead_id);
            
            if (!$alreadyInStructure) {
                // Only add if not already in structure charges
                $items->push([
                    'votehead_id' => $opt->votehead_id,
                    'amount' => (float)($opt->amount ?? 0),
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


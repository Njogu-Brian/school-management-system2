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
            foreach ($existingItems as $existing) {
                if (!$proposedItems->contains('votehead_id', $existing['votehead_id'])) {
                    $diffs->push([
                        'action' => 'removed',
                        'student_id' => $student->id,
                        'votehead_id' => $existing['votehead_id'],
                        'old_amount' => $existing['amount'],
                        'new_amount' => 0,
                        'invoice_item_id' => $existing['id'],
                        'origin' => $existing['source'] ?? 'structure',
                    ]);
                }
            }
        }
        
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
                
                // Idempotency check: skip if item already exists and is active
                if (isset($diff['invoice_item_id']) && $diff['invoice_item_id']) {
                    $existingItem = InvoiceItem::find($diff['invoice_item_id']);
                    if ($existingItem && $existingItem->status === 'active' && $existingItem->amount == ($diff['new_amount'] ?? 0)) {
                        continue;
                    }
                }
                
                $invoice = InvoiceService::ensure($diff['student_id'], $year, $term);
                
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
                
                // Remove or deactivate item
                $item->delete(); // Or set status to 'reversed' if soft deletes
                
                // Recalculate invoice
                InvoiceService::recalc($item->invoice);
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
        $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
        if ($academicYear) {
            $structureQuery->where(function($q) use ($academicYear, $year) {
                $q->where('academic_year_id', $academicYear->id)
                  ->orWhere('year', $year);
            });
        } else {
            $structureQuery->where('year', $year);
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
        
        if ($structure) {
            // For once_annually fees with preferred_term, check if we should include them
            $charges = $structure->charges;
            
            // Filter by term, but handle preferred_term for once_annually
            $charges = $charges->filter(function($charge) use ($term) {
                $votehead = $charge->votehead;
                if (!$votehead) return false;
                
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
                
                if ($votehead->is_mandatory || empty($filters['votehead_id'])) {
                    $items->push([
                        'votehead_id' => $votehead->id,
                        'amount' => (float)$charge->amount,
                        'origin' => 'structure',
                    ]);
                }
            }
        }
        
        // From optional fees
        $optional = OptionalFee::query()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->when(!empty($filters['votehead_id']), fn($q) => $q->where('votehead_id', (int)$filters['votehead_id']))
            ->get();
        
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
        $oldAmount = $existing['amount'] ?? 0;
        $newAmount = $proposed['amount'] ?? 0;
        
        if ($oldAmount == $newAmount && $existing) {
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
}


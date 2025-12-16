<?php

namespace App\Services;

use App\Models\{
    FeeConcession, Student, Invoice, InvoiceItem, Votehead, Family, AuditLog
};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Discount Service
 * Handles discount application and management
 */
class DiscountService
{
    /**
     * Apply discounts to invoice during posting
     */
    public function applyDiscountsToInvoice(Invoice $invoice, bool $forceReapply = false): Invoice
    {
        return DB::transaction(function () use ($invoice, $forceReapply) {
            $student = $invoice->student;
            
            // Get applicable concessions for this student
            $concessions = FeeConcession::where(function ($q) use ($student) {
                $q->where('student_id', $student->id)
                  ->orWhere(function ($sq) use ($student) {
                      if ($student->family_id) {
                          $sq->where('family_id', $student->family_id)
                            ->where('scope', 'family');
                      }
                  });
            })
            ->where('is_active', true)
            ->where('approval_status', 'approved') // Only approved discounts
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            // Match term and year if specified
            ->when($invoice->term, fn($q) => $q->where(function($q) use ($invoice) {
                $q->whereNull('term')->orWhere('term', $invoice->term);
            }))
            ->when($invoice->year, fn($q) => $q->where(function($q) use ($invoice) {
                $q->whereNull('year')->orWhere('year', $invoice->year);
            }))
            ->get();
            
            $totalDiscount = 0;
            
            foreach ($concessions as $concession) {
                $discountAmount = 0;
                
                // Skip if discount already applied and not forcing reapply
                if (!$forceReapply) {
                    // Check if this discount was already applied to this invoice
                    // by checking if invoice items have discount matching this concession
                    $alreadyApplied = false;
                    if ($concession->scope === 'votehead' && $concession->votehead_id) {
                        $item = $invoice->items()->where('votehead_id', $concession->votehead_id)->first();
                        if ($item && $item->discount_amount > 0) {
                            // Discount might already be applied, but we'll reapply to ensure it's correct
                            // Remove this check for now to allow recalculation
                        }
                    }
                }
                
                switch ($concession->scope) {
                    case 'votehead':
                        if ($concession->votehead_id) {
                            $item = $invoice->items()->where('votehead_id', $concession->votehead_id)->first();
                            if ($item) {
                                $discountAmount = $concession->calculateDiscount($item->amount);
                                $item->increment('discount_amount', $discountAmount);
                            }
                        }
                        break;
                        
                    case 'invoice':
                        if ($concession->invoice_id == $invoice->id) {
                            $discountAmount = $concession->calculateDiscount($invoice->total);
                            $invoice->increment('discount_amount', $discountAmount);
                        }
                        break;
                        
                    case 'student':
                        // Apply to all items in invoice
                        foreach ($invoice->items as $item) {
                            $itemDiscount = $concession->calculateDiscount($item->amount);
                            $item->increment('discount_amount', $itemDiscount);
                            $discountAmount += $itemDiscount;
                        }
                        break;
                        
                    case 'family':
                        // Apply to all items
                        foreach ($invoice->items as $item) {
                            $itemDiscount = $concession->calculateDiscount($item->amount);
                            $item->increment('discount_amount', $itemDiscount);
                            $discountAmount += $itemDiscount;
                        }
                        break;
                }
                
                $totalDiscount += $discountAmount;
            }
            
            if ($totalDiscount > 0) {
                InvoiceService::recalc($invoice);
            }
            
            return $invoice->fresh();
        });
    }
    
    /**
     * Create discount for student/votehead/invoice
     */
    public function createDiscount(array $data): FeeConcession
    {
        return DB::transaction(function () use ($data) {
            $concession = FeeConcession::create([
                'student_id' => $data['student_id'] ?? null,
                'family_id' => $data['family_id'] ?? null,
                'votehead_id' => $data['votehead_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'type' => $data['type'], // percentage or fixed_amount
                'discount_type' => $data['discount_type'] ?? 'manual',
                'frequency' => $data['frequency'] ?? 'manual',
                'scope' => $data['scope'] ?? 'votehead',
                'value' => $data['value'],
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? now(),
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
                'approved_by' => $data['auto_approve'] ? auth()->id() : null,
            ]);
            
            // Log audit (if AuditLog model exists)
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log('created', $concession, null, $concession->toArray(), ['discount_created']);
            }
            
            return $concession;
        });
    }
    
    /**
     * Apply sibling discount (if multiple siblings enrolled)
     */
    public function applySiblingDiscount(Student $student): ?FeeConcession
    {
        if (!$student->family_id) {
            return null;
        }
        
        $siblingCount = Student::where('family_id', $student->family_id)
            ->where('status', 'active')
            ->count();
        
        if ($siblingCount < 2) {
            return null; // No sibling discount if only one child
        }
        
        // Check if discount already exists
        $existing = FeeConcession::where('family_id', $student->family_id)
            ->where('discount_type', 'sibling')
            ->where('is_active', true)
            ->first();
        
        if ($existing) {
            return $existing;
        }
        
        // Create sibling discount (e.g., 5% per additional sibling)
        $discountPercent = min(20, ($siblingCount - 1) * 5); // Max 20%
        
        return $this->createDiscount([
            'family_id' => $student->family_id,
            'type' => 'percentage',
            'discount_type' => 'sibling',
            'frequency' => 'yearly',
            'scope' => 'family',
            'value' => $discountPercent,
            'reason' => "Sibling discount - {$siblingCount} siblings enrolled",
            'auto_approve' => true,
        ]);
    }
}


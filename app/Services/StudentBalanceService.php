<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\LegacyStatementTerm;

class StudentBalanceService
{
    /**
     * Get total outstanding balance for a student including balance brought forward from legacy data.
     * 
     * @param Student|int $student Student model or ID
     * @return float Total outstanding balance (invoices balance + balance brought forward)
     */
    public static function getTotalOutstandingBalance($student): float
    {
        $studentModel = $student instanceof Student ? $student : Student::find($student);
        if (!$studentModel) {
            return 0.0;
        }
        
        // Get balance brought forward from legacy data (ending_balance from last term before 2026)
        $balanceBroughtForward = self::getBalanceBroughtForward($studentModel);
        
        // Check if balance brought forward is already included in invoices as an invoice item
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardInInvoice = 0;
        
        if ($balanceBroughtForwardVotehead && $balanceBroughtForward > 0) {
            // Get all unpaid balance brought forward items from invoices
            $balanceBroughtForwardInInvoice = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($studentModel) {
                $q->where('student_id', $studentModel->id)
                  ->where('status', '!=', 'reversed');
            })
            ->where('votehead_id', $balanceBroughtForwardVotehead->id)
            ->where('source', 'balance_brought_forward')
            ->get()
            ->sum(function($item) {
                // Calculate unpaid portion of balance brought forward item
                $paid = $item->allocations()->sum('amount');
                return max(0, $item->amount - $paid);
            });
        }
        
        // Get balance from all invoices
        $invoiceBalance = Invoice::where('student_id', $studentModel->id)
            ->where('status', '!=', 'reversed')
            ->sum('balance');
        
        // If balance brought forward is already in invoices, don't add it again
        // Otherwise, add the balance brought forward from legacy data
        if ($balanceBroughtForwardInInvoice > 0) {
            // Balance brought forward is already included in invoice balance
            return max(0, $invoiceBalance);
        } else {
            // Balance brought forward is not in invoices, add it separately
            return max(0, $invoiceBalance + $balanceBroughtForward);
        }
    }

    /**
     * Get balance brought forward from legacy data for a student.
     * This is the ending_balance from the last term before 2026.
     * 
     * @param Student|int $student Student model or ID
     * @return float Balance brought forward (0 if none or student doesn't exist)
     */
    public static function getBalanceBroughtForward($student): float
    {
        $studentModel = $student instanceof Student ? $student : Student::find($student);
        if (!$studentModel) {
            return 0.0;
        }
        
        $broughtForward = LegacyStatementTerm::getBalanceBroughtForward($studentModel);
        return $broughtForward !== null && $broughtForward > 0 ? $broughtForward : 0;
    }

    /**
     * Get balance from invoices only (excluding balance brought forward).
     * 
     * @param Student|int $student Student model or ID
     * @return float Invoice balance
     */
    public static function getInvoiceBalance($student): float
    {
        $studentModel = $student instanceof Student ? $student : Student::find($student);
        if (!$studentModel) {
            return 0.0;
        }
        
        return (float) Invoice::where('student_id', $studentModel->id)
            ->where('status', '!=', 'reversed')
            ->sum('balance');
    }

    /**
     * Calculate total outstanding fees for all students (for dashboard).
     * This includes:
     * - All invoice balances (which already account for payments, discounts, credit/debit notes)
     * - Balance brought forward from legacy imports (only if not already in invoices)
     * - All voteheads including transport
     * 
     * @return float Total outstanding fees
     */
    public static function getTotalOutstandingFees(): float
    {
        // Sum all invoice balances (unpaid and partial only)
        // invoice.balance already accounts for:
        // - Payments (via paid_amount)
        // - Discounts (item-level and invoice-level, already subtracted in total)
        // - Credit/debit notes (they modify item amounts directly, included in total)
        // - All voteheads including transport
        $invoiceOutstanding = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('status', '!=', 'reversed')
            ->sum('balance') ?? 0;

        // Check if balance brought forward is already included in invoices
        // Balance brought forward is stored as BAL_BF votehead items
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardInInvoices = 0;
        
        if ($balanceBroughtForwardVotehead) {
            // Calculate unpaid balance brought forward from invoice items
            $balanceBroughtForwardInInvoices = \App\Models\InvoiceItem::whereHas('invoice', function($q) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('status', '!=', 'reversed');
            })
            ->where('votehead_id', $balanceBroughtForwardVotehead->id)
            ->where('source', 'balance_brought_forward')
            ->where('status', 'active')
            ->get()
            ->sum(function($item) {
                // Calculate unpaid portion: amount - discount - allocations
                $allocated = $item->allocations()->sum('amount');
                return max(0, $item->amount - ($item->discount_amount ?? 0) - $allocated);
            });
        }

        // Get balance brought forward from legacy data (only for students where it's NOT in invoices)
        $balanceBroughtForwardFromLegacy = 0;
        $studentsWithLegacy = \App\Models\LegacyStatementTerm::where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->whereNotNull('student_id')
            ->select('student_id')
            ->distinct()
            ->pluck('student_id')
            ->filter(function($studentId) {
                return Student::where('id', $studentId)->exists();
            });

        foreach ($studentsWithLegacy as $studentId) {
            try {
                $bf = self::getBalanceBroughtForward($studentId);
                if ($bf > 0) {
                    // Check if this student has BAL_BF items in their invoices
                    $studentHasBfInInvoice = false;
                    if ($balanceBroughtForwardVotehead) {
                        $studentHasBfInInvoice = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($studentId) {
                            $q->where('student_id', $studentId)
                              ->where('status', '!=', 'reversed');
                        })
                        ->where('votehead_id', $balanceBroughtForwardVotehead->id)
                        ->where('source', 'balance_brought_forward')
                        ->exists();
                    }
                    
                    // Only add if not already in invoices
                    if (!$studentHasBfInInvoice) {
                        $balanceBroughtForwardFromLegacy += $bf;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to get balance brought forward for student {$studentId}: " . $e->getMessage());
                continue;
            }
        }

        // Total outstanding = invoice balances + balance brought forward (only if not already in invoices)
        return max(0, $invoiceOutstanding + $balanceBroughtForwardFromLegacy);
    }

    /**
     * Total outstanding fees excluding swimming (for dashboard).
     * Swimming is managed separately in the Swimming module and must not be included in main fee totals.
     *
     * @return float Total outstanding fees (invoice items only, excluding swimming votehead) + balance brought forward
     */
    public static function getTotalOutstandingFeesExcludingSwimming(): float
    {
        $swimmingVoteheadIds = \App\Models\Votehead::where(function ($q) {
            $q->where('name', 'like', '%swim%')->orWhere('code', 'like', '%SWIM%');
        })->pluck('id')->toArray();

        // Sum unpaid portion of invoice items (excluding swimming) for unpaid/partial invoices
        $invoiceOutstanding = \App\Models\InvoiceItem::whereHas('invoice', function ($q) {
            $q->whereIn('status', ['unpaid', 'partial'])
                ->where('status', '!=', 'reversed');
        })
            ->where('status', 'active')
            ->when(!empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
            ->get()
            ->sum(function ($item) {
                $allocated = $item->allocations()->sum('amount');
                return max(0, (float) $item->amount - (float) ($item->discount_amount ?? 0) - (float) $allocated);
            });

        // Balance brought forward from legacy (same logic as getTotalOutstandingFees)
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardFromLegacy = 0;
        $studentsWithLegacy = \App\Models\LegacyStatementTerm::where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->whereNotNull('student_id')
            ->select('student_id')
            ->distinct()
            ->pluck('student_id')
            ->filter(fn ($studentId) => Student::where('id', $studentId)->exists());

        foreach ($studentsWithLegacy as $studentId) {
            try {
                $bf = self::getBalanceBroughtForward($studentId);
                if ($bf > 0) {
                    $studentHasBfInInvoice = false;
                    if ($balanceBroughtForwardVotehead) {
                        $studentHasBfInInvoice = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($studentId) {
                            $q->where('student_id', $studentId)->where('status', '!=', 'reversed');
                        })
                            ->where('votehead_id', $balanceBroughtForwardVotehead->id)
                            ->where('source', 'balance_brought_forward')
                            ->exists();
                    }
                    if (!$studentHasBfInInvoice) {
                        $balanceBroughtForwardFromLegacy += $bf;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to get balance brought forward for student {$studentId}: " . $e->getMessage());
            }
        }

        return max(0, $invoiceOutstanding + $balanceBroughtForwardFromLegacy);
    }
}


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
}


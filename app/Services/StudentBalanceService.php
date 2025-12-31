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
        $studentModel = $student instanceof Student ? $student : Student::findOrFail($student);
        
        // Get balance from all invoices
        $invoiceBalance = Invoice::where('student_id', $studentModel->id)
            ->where('status', '!=', 'reversed')
            ->sum('balance');
        
        // Get balance brought forward from legacy data (ending_balance from last term before 2026)
        $balanceBroughtForward = self::getBalanceBroughtForward($studentModel);
        
        return max(0, $invoiceBalance + $balanceBroughtForward);
    }

    /**
     * Get balance brought forward from legacy data for a student.
     * This is the ending_balance from the last term before 2026.
     * 
     * @param Student|int $student Student model or ID
     * @return float Balance brought forward (0 if none)
     */
    public static function getBalanceBroughtForward($student): float
    {
        $studentModel = $student instanceof Student ? $student : Student::findOrFail($student);
        
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
        $studentModel = $student instanceof Student ? $student : Student::findOrFail($student);
        
        return (float) Invoice::where('student_id', $studentModel->id)
            ->where('status', '!=', 'reversed')
            ->sum('balance');
    }
}


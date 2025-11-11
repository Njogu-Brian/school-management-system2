<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    /**
     * Generate payslip for a payroll record
     */
    public function generate($id)
    {
        $record = PayrollRecord::with(['staff', 'payrollPeriod', 'salaryStructure'])->findOrFail($id);

        // Generate payslip number if not exists
        if (!$record->payslip_number) {
            $record->generatePayslipNumber();
            $record->payslip_generated_at = now();
            $record->save();
        }

        return view('hr.payroll.payslips.show', compact('record'));
    }

    /**
     * Download payslip as PDF
     */
    public function download($id)
    {
        $record = PayrollRecord::with(['staff', 'payrollPeriod', 'salaryStructure'])->findOrFail($id);

        // Generate payslip number if not exists
        if (!$record->payslip_number) {
            $record->generatePayslipNumber();
            $record->payslip_generated_at = now();
            $record->save();
        }

        $pdf = Pdf::loadView('hr.payroll.payslips.pdf', compact('record'));
        
        return $pdf->download('payslip-' . $record->payslip_number . '.pdf');
    }

    /**
     * View staff's payslips
     */
    public function staffPayslips($staffId)
    {
        $records = PayrollRecord::where('staff_id', $staffId)
            ->with('payrollPeriod')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('hr.payroll.payslips.staff', compact('records'));
    }
}

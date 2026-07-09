<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    /**
     * View payslip for a payroll record.
     */
    public function show($id)
    {
        return $this->generate($id);
    }

    /**
     * Generate payslip for a payroll record.
     */
    public function generate($id)
    {
        $record = $this->loadRecord($id);

        return view('hr.payroll.payslips.show', compact('record'));
    }

    /**
     * Download payslip as PDF.
     */
    public function download($id)
    {
        $record = $this->loadRecord($id);

        $pdf = Pdf::loadView('hr.payroll.payslips.pdf', compact('record'));

        return $pdf->download('payslip-' . ($record->payslip_number ?: $record->id) . '.pdf');
    }

    /**
     * View staff's payslips.
     */
    public function staffPayslips($staffId)
    {
        $records = PayrollRecord::where('staff_id', $staffId)
            ->with('payrollPeriod')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('hr.payroll.payslips.staff', compact('records'));
    }

    protected function loadRecord($id): PayrollRecord
    {
        $record = PayrollRecord::with(['staff', 'payrollPeriod', 'salaryStructure'])->findOrFail($id);

        if (!$record->payslip_number) {
            $record->generatePayslipNumber();
            $record->payslip_generated_at = now();
            $record->save();
        }

        return $record;
    }
}

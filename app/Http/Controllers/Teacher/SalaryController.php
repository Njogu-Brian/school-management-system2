<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    /**
     * Display teacher's salary information
     */
    public function index()
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        // Get active salary structure
        $salaryStructure = $staff->activeSalaryStructure;
        
        // Get payroll records
        $payrollRecords = PayrollRecord::where('staff_id', $staff->id)
            ->with('payrollPeriod')
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        // Get salary history
        $salaryHistory = SalaryStructure::where('staff_id', $staff->id)
            ->orderBy('effective_from', 'desc')
            ->get();
        
        return view('teacher.salary.index', compact('staff', 'salaryStructure', 'payrollRecords', 'salaryHistory'));
    }
    
    /**
     * View payslip
     */
    public function payslip($recordId)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        $record = PayrollRecord::where('id', $recordId)
            ->where('staff_id', $staff->id)
            ->with(['staff', 'payrollPeriod', 'salaryStructure'])
            ->firstOrFail();
        
        // Generate payslip number if not exists
        if (!$record->payslip_number) {
            $record->generatePayslipNumber();
            $record->payslip_generated_at = now();
            $record->save();
        }
        
        return view('teacher.salary.payslip', compact('record'));
    }
    
    /**
     * Download payslip as PDF
     */
    public function downloadPayslip($recordId)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        $record = PayrollRecord::where('id', $recordId)
            ->where('staff_id', $staff->id)
            ->with(['staff', 'payrollPeriod', 'salaryStructure'])
            ->firstOrFail();
        
        // Generate payslip number if not exists
        if (!$record->payslip_number) {
            $record->generatePayslipNumber();
            $record->payslip_generated_at = now();
            $record->save();
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('hr.payroll.payslips.pdf', compact('record'));
        
        return $pdf->download('payslip-' . $record->payslip_number . '.pdf');
    }
}

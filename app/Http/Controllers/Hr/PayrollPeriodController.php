<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Staff;
use App\Models\SalaryStructure;
use App\Models\StaffAdvance;
use App\Models\CustomDeduction;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollPeriodController extends Controller
{
    protected $payrollCalc;

    public function __construct(PayrollCalculationService $payrollCalc)
    {
        $this->payrollCalc = $payrollCalc;
    }

    /**
     * Display a listing of payroll periods
     */
    public function index()
    {
        $periods = PayrollPeriod::with('processedBy')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(20);

        return view('hr.payroll.periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new payroll period
     */
    public function create()
    {
        return view('hr.payroll.periods.create');
    }

    /**
     * Store a newly created payroll period
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pay_date' => 'required|date|after_or_equal:end_date',
        ]);

        // Check if period already exists
        $exists = PayrollPeriod::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A payroll period for this month already exists.');
        }

        $validated['period_name'] = Carbon::create($validated['year'], $validated['month'], 1)->format('F Y');

        $period = PayrollPeriod::create($validated);

        return redirect()->route('hr.payroll.periods.show', $period->id)
            ->with('success', 'Payroll period created successfully.');
    }

    /**
     * Display the specified payroll period
     */
    public function show($id)
    {
        $period = PayrollPeriod::with(['payrollRecords.staff', 'processedBy'])->findOrFail($id);
        $period->calculateTotals();

        return view('hr.payroll.periods.show', compact('period'));
    }

    /**
     * Process payroll for the period
     */
    public function process($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        if (!$period->canProcess()) {
            return back()->with('error', 'This payroll period cannot be processed.');
        }

        DB::beginTransaction();
        try {
            $period->status = 'processing';
            $period->save();

            // Get all active staff
            $staff = Staff::where('status', 'active')->get();

            foreach ($staff as $member) {
                // Get active salary structure or use staff basic_salary
                $salaryStructure = $member->activeSalaryStructure;
                
                // Skip if no salary structure AND no basic_salary on staff
                if (!$salaryStructure && !$member->basic_salary) {
                    continue;
                }

                // Check if record already exists
                $existingRecord = PayrollRecord::where('payroll_period_id', $period->id)
                    ->where('staff_id', $member->id)
                    ->first();

                if ($existingRecord) {
                    continue; // Skip if already processed
                }

                // Create payroll record
                $record = new PayrollRecord();
                $record->payroll_period_id = $period->id;
                $record->staff_id = $member->id;
                $record->salary_structure_id = $salaryStructure->id ?? null;
                $record->created_by = auth()->id();

                // Copy salary components from structure or use staff basic_salary
                if ($salaryStructure) {
                    $record->basic_salary = $salaryStructure->basic_salary;
                    $record->housing_allowance = $salaryStructure->housing_allowance;
                    $record->transport_allowance = $salaryStructure->transport_allowance;
                    $record->medical_allowance = $salaryStructure->medical_allowance;
                    $record->other_allowances = $salaryStructure->other_allowances;
                    $record->allowances_breakdown = $salaryStructure->allowances_breakdown;
                } else {
                    // Use staff basic_salary if no salary structure
                    $record->basic_salary = $member->basic_salary ?? 0;
                    $record->housing_allowance = 0;
                    $record->transport_allowance = 0;
                    $record->medical_allowance = 0;
                    $record->other_allowances = 0;
                    $record->allowances_breakdown = null;
                }

                // Calculate deductions
                $record->calculateTotals(); // Calculate gross first
                $statutoryExemptions = $member->statutoryExemptionCodes();
                $deductions = $this->payrollCalc->calculateAllDeductions($record->gross_salary, $statutoryExemptions);
                $record->nssf_deduction = $deductions['nssf'];
                $record->nhif_deduction = $deductions['nhif'];
                $record->paye_deduction = $deductions['paye'];
                $record->other_deductions = $salaryStructure->other_deductions ?? 0;
                $record->deductions_breakdown = $salaryStructure->deductions_breakdown ?? null;

                // Process advance deductions
                $advanceDeduction = 0;
                $activeAdvances = $member->activeAdvances()
                    ->where('repayment_method', 'monthly_deduction')
                    ->get();
                
                foreach ($activeAdvances as $advance) {
                    if ($advance->monthly_deduction_amount && $advance->balance > 0) {
                        $deductionAmount = min($advance->monthly_deduction_amount, $advance->balance);
                        $advanceDeduction += $deductionAmount;
                        
                        // Record repayment
                        $advance->recordRepayment($deductionAmount);
                    }
                }
                $record->advance_deduction = $advanceDeduction;

                // Process custom deductions
                $customDeductionsTotal = 0;
                $customDeductionsBreakdown = [];
                $activeCustomDeductions = $member->activeCustomDeductions()
                    ->where('effective_from', '<=', $period->end_date)
                    ->where(function($q) use ($period) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $period->start_date);
                    })
                    ->get();

                foreach ($activeCustomDeductions as $customDeduction) {
                    if ($customDeduction->shouldDeductThisMonth($period->year, $period->month)) {
                        $deductionAmount = $customDeduction->amount;
                        
                        // Check if installment-based and not completed
                        if ($customDeduction->total_amount && $customDeduction->amount_deducted >= $customDeduction->total_amount) {
                            continue; // Skip if already fully deducted
                        }
                        
                        // Don't exceed total amount if specified
                        if ($customDeduction->total_amount) {
                            $remaining = $customDeduction->total_amount - $customDeduction->amount_deducted;
                            $deductionAmount = min($deductionAmount, $remaining);
                        }
                        
                        $customDeductionsTotal += $deductionAmount;
                        $customDeductionsBreakdown[$customDeduction->deduction_type_id] = 
                            ($customDeductionsBreakdown[$customDeduction->deduction_type_id] ?? 0) + $deductionAmount;
                        
                        // Record deduction
                        $customDeduction->recordDeduction($deductionAmount);
                    }
                }
                $record->custom_deductions_total = $customDeductionsTotal;
                $record->custom_deductions_breakdown = $customDeductionsBreakdown;

                // Recalculate totals with all deductions
                $record->calculateTotals();

                $record->status = 'approved';
                $record->save();

                // Create salary history entry
                \App\Models\SalaryHistory::create([
                    'staff_id' => $member->id,
                    'payroll_record_id' => $record->id,
                    'basic_salary' => $record->basic_salary,
                    'gross_salary' => $record->gross_salary,
                    'total_deductions' => $record->total_deductions,
                    'net_salary' => $record->net_salary,
                    'year' => $period->year,
                    'month' => $period->month,
                    'pay_date' => $period->pay_date,
                    'change_type' => 'payroll',
                    'created_by' => auth()->id(),
                ]);
            }

            // Update period totals and status
            $period->calculateTotals();
            $period->status = 'completed';
            $period->processed_at = now();
            $period->processed_by = auth()->id();
            $period->save();

            DB::commit();

            return redirect()->route('hr.payroll.periods.show', $period->id)
                ->with('success', 'Payroll processed successfully for ' . $period->staff_count . ' staff members.');
        } catch (\Exception $e) {
            DB::rollBack();
            $period->status = 'draft';
            $period->save();
            return back()->with('error', 'Failed to process payroll: ' . $e->getMessage());
        }
    }

    /**
     * Lock the payroll period
     */
    public function lock($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        if ($period->status !== 'completed') {
            return back()->with('error', 'Only completed payroll periods can be locked.');
        }

        $period->status = 'locked';
        $period->save();

        return back()->with('success', 'Payroll period locked successfully.');
    }
}

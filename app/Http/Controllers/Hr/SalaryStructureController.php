<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends Controller
{
    protected $payrollCalc;

    public function __construct(PayrollCalculationService $payrollCalc)
    {
        $this->payrollCalc = $payrollCalc;
    }

    /**
     * Display a listing of salary structures
     */
    public function index(Request $request)
    {
        $query = SalaryStructure::with(['staff.department', 'staff.jobTitle', 'createdBy']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $structures = $query->orderBy('effective_from', 'desc')->paginate(20);

        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('hr.payroll.salary-structures.index', compact('structures', 'staff'));
    }

    /**
     * Show the form for creating a new salary structure
     */
    public function create(Request $request)
    {
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();
        $selectedStaff = $request->filled('staff_id') ? Staff::find($request->staff_id) : null;

        return view('hr.payroll.salary-structures.create', compact('staff', 'selectedStaff'));
    }

    /**
     * Store a newly created salary structure
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'nssf_deduction' => 'nullable|numeric|min:0',
            'nhif_deduction' => 'nullable|numeric|min:0',
            'paye_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Deactivate previous active structures for this staff
            if ($request->filled('is_active') && $request->is_active) {
                SalaryStructure::where('staff_id', $validated['staff_id'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $structure = new SalaryStructure($validated);
            $structure->created_by = auth()->id();
            
            // Calculate totals
            $structure->calculateTotals();
            
            // Auto-calculate deductions if not provided
            if (!$request->filled('nssf_deduction') || !$request->filled('nhif_deduction') || !$request->filled('paye_deduction')) {
                $deductions = $this->payrollCalc->calculateAllDeductions($structure->gross_salary);
                $structure->nssf_deduction = $deductions['nssf'];
                $structure->nhif_deduction = $deductions['nhif'];
                $structure->paye_deduction = $deductions['paye'];
                $structure->calculateTotals(); // Recalculate with deductions
            }

            $structure->save();

            DB::commit();

            return redirect()->route('hr.payroll.salary-structures.index')
                ->with('success', 'Salary structure created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create salary structure: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified salary structure
     */
    public function show($id)
    {
        $structure = SalaryStructure::with(['staff', 'createdBy'])->findOrFail($id);
        return view('hr.payroll.salary-structures.show', compact('structure'));
    }

    /**
     * Show the form for editing the specified salary structure
     */
    public function edit($id)
    {
        $structure = SalaryStructure::with('staff')->findOrFail($id);
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('hr.payroll.salary-structures.edit', compact('structure', 'staff'));
    }

    /**
     * Update the specified salary structure
     */
    public function update(Request $request, $id)
    {
        $structure = SalaryStructure::findOrFail($id);

        $validated = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'nssf_deduction' => 'nullable|numeric|min:0',
            'nhif_deduction' => 'nullable|numeric|min:0',
            'paye_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // If activating this structure, deactivate others
            if ($request->filled('is_active') && $request->is_active) {
                SalaryStructure::where('staff_id', $structure->staff_id)
                    ->where('id', '!=', $structure->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $structure->fill($validated);
            $structure->calculateTotals();
            $structure->save();

            DB::commit();

            return redirect()->route('hr.payroll.salary-structures.index')
                ->with('success', 'Salary structure updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update salary structure: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified salary structure
     */
    public function destroy($id)
    {
        $structure = SalaryStructure::findOrFail($id);

        // Check if used in payroll records
        if ($structure->payrollRecords()->exists()) {
            return back()->with('error', 'Cannot delete salary structure that has been used in payroll records.');
        }

        $structure->delete();

        return redirect()->route('hr.payroll.salary-structures.index')
            ->with('success', 'Salary structure deleted successfully.');
    }
}

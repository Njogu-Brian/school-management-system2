<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Staff;
use App\Services\Hr\PayrollImports\BudgetPdfParser;
use App\Services\Hr\PayrollImports\StaffMatcher;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayrollImportsController extends Controller
{
    public function budgetForm()
    {
        return view('hr.payroll.imports.budget');
    }

    public function budgetParse(Request $request)
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:51200',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'pay_date' => 'required|date',
        ]);

        // Drop any previous upload left in session before storing a new one.
        $this->deleteBudgetImportFile(session('payroll_budget_import.file_path'));

        $stored = $request->file('pdf')->store('payroll/imports', 'local');
        $abs = Storage::disk('local')->path($stored);

        try {
            $rows = app(BudgetPdfParser::class)->parse($abs);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($stored);
            throw $e;
        }

        $matcher = app(StaffMatcher::class);

        $preview = [];
        foreach ($rows as $r) {
            $match = $matcher->matchByBestKey($r);
            $preview[] = array_merge($r, ['match' => $match]);
        }

        session([
            'payroll_budget_import' => [
                'file_path' => $stored,
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
                'pay_date' => $validated['pay_date'],
                'rows' => $preview,
            ],
        ]);

        return view('hr.payroll.imports.budget_verify', [
            'import' => session('payroll_budget_import'),
        ]);
    }

    public function budgetCommit(Request $request)
    {
        $import = session('payroll_budget_import');
        if (!is_array($import) || empty($import['rows'])) {
            return redirect()->route('hr.payroll.imports.budget.form')->with('error', 'Import session expired. Please upload again.');
        }

        $request->validate([
            'resolve' => 'array',
        ]);

        $rows = $import['rows'];
        $resolved = (array) $request->input('resolve', []);

        // Apply manual resolutions for ambiguous/unmatched rows.
        foreach ($rows as $i => $row) {
            $picked = $resolved[$i] ?? null;
            if ($picked) {
                $rows[$i]['match']['status'] = 'matched';
                $rows[$i]['match']['staff_id'] = (int) $picked;
            }
        }

        // Hard stop if any row is still ambiguous with candidates (or unmatched)
        $blocking = collect($rows)->filter(function ($r) {
            $status = $r['match']['status'] ?? null;
            return $status !== 'matched';
        });
        if ($blocking->isNotEmpty()) {
            return back()->with('error', 'Resolve all unmatched/ambiguous staff before committing.')->withInput();
        }

        $rulesetId = \App\Models\StatutoryRuleset::default()->value('id')
            ?? \App\Models\StatutoryRuleset::query()->value('id');

        DB::transaction(function () use ($import, $rows, $rulesetId) {
            $period = PayrollPeriod::firstOrCreate(
                ['year' => $import['year'], 'month' => $import['month']],
                [
                    'period_name' => now()->setDate($import['year'], $import['month'], 1)->format('F Y'),
                    'start_date' => now()->setDate($import['year'], $import['month'], 1)->startOfMonth()->toDateString(),
                    'end_date' => now()->setDate($import['year'], $import['month'], 1)->endOfMonth()->toDateString(),
                    'pay_date' => $import['pay_date'],
                    'status' => 'draft',
                    'statutory_ruleset_id' => $rulesetId,
                ],
            );

            if (! $period->statutory_ruleset_id && $rulesetId) {
                $period->statutory_ruleset_id = $rulesetId;
                $period->saveQuietly();
            }

            // Create/update records deterministically from budget numbers (no recalculation).
            foreach ($rows as $r) {
                $staffId = (int) $r['match']['staff_id'];
                $staff = Staff::findOrFail($staffId);

                // Keep staff basic salary aligned to gross if provided.
                if (!empty($r['gross'])) {
                    $staff->basic_salary = (float) $r['gross'];
                    $staff->saveQuietly();
                }

                $record = PayrollRecord::firstOrNew([
                    'payroll_period_id' => $period->id,
                    'staff_id' => $staff->id,
                ]);

                // Budget uses a single gross figure; store as basic_salary for now.
                $record->basic_salary = (float) ($r['gross'] ?? 0);
                $record->housing_allowance = 0;
                $record->transport_allowance = 0;
                $record->medical_allowance = 0;
                $record->other_allowances = 0;

                $record->calculateTotals();

                $record->nssf_deduction = (float) ($r['nssf'] ?? 0);
                $record->shif_deduction = (float) ($r['shif'] ?? 0);
                $record->paye_deduction = (float) ($r['paye'] ?? 0);
                $record->housing_levy_deduction = (float) ($r['housing'] ?? 0);

                // Internal (post-tax) deductions go to breakdown so payroll matches budget.
                $internal = [
                    'kids_fees' => (float) ($r['kids_fees'] ?? 0),
                    'uniform' => (float) ($r['uniform'] ?? 0),
                    'loan' => (float) ($r['loan'] ?? 0),
                ];
                $record->deductions_breakdown = array_filter($internal, fn ($v) => (float)$v > 0);
                $record->advance_deduction = (float) ($r['advance'] ?? 0);

                $record->calculateTotals();
                $record->status = 'approved';
                $record->created_by = auth()->id();
                $record->save();
            }

            $period->refresh()->loadMissing('payrollRecords');
            $period->calculateTotals();
            $period->saveQuietly();
        });

        $this->deleteBudgetImportFile($import['file_path'] ?? null);
        session()->forget('payroll_budget_import');

        return redirect()->route('hr.payroll.periods.index')->with('success', 'Budget import committed successfully.');
    }

    private function deleteBudgetImportFile(mixed $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        try {
            Storage::disk('local')->delete($path);
        } catch (\Throwable) {
            // Best-effort cleanup; do not block the import flow.
        }
    }
}


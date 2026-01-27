<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentAllocation;
use App\Models\Term;
use App\Exports\ArrayExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class FeesComparisonImportController extends Controller
{
    /**
     * Display upload form for fees comparison import.
     * Comparison only – no actions are taken on import.
     */
    public function index(Request $request)
    {
        $currentTerm = Term::where('is_current', true)->first();
        $year = $request->input('year', now()->year);
        $termNumber = $currentTerm ? $this->extractTermNumber($currentTerm->name) : 1;
        $termNumber = $request->input('term', $termNumber);

        return view('finance.fees_comparison_import.index', [
            'year' => $year,
            'term' => $termNumber,
            'terms' => Term::orderBy('name')->get(),
        ]);
    }

    /**
     * Preview import: parse Excel, compare with system totals (incl. BBF), handle siblings.
     * Read-only – no commit or updates.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'year' => 'required|integer|min:2020|max:2030',
            'term' => 'required|integer|min:1|max:3',
        ]);

        $year = (int) $request->input('year');
        $termNumber = (int) $request->input('term');

        try {
            $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];
        } catch (\Exception $e) {
            Log::error('Fees comparison Excel import failed', ['error' => $e->getMessage()]);
            if (str_contains($e->getMessage(), 'ZipArchive') || str_contains($e->getMessage(), 'zip')) {
                return back()->withErrors([
                    'file' => 'The PHP Zip extension is not enabled. Enable "zip" in php.ini to read .xlsx files.',
                ]);
            }
            return back()->withErrors(['file' => 'Failed to read the Excel file: ' . $e->getMessage()]);
        }

        if (empty($sheet)) {
            return back()->withErrors(['file' => 'The uploaded file is empty or could not be read.']);
        }

        $headerRow = array_shift($sheet);
        if (empty($headerRow)) {
            return back()->withErrors(['file' => 'The file must contain a header row with column names.']);
        }

        $headers = [];
        foreach ($headerRow as $i => $h) {
            $headers[$i] = Str::slug(Str::lower(trim((string) $h)), '_');
        }

        $importRows = [];
        foreach ($sheet as $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }
            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $name = trim((string) ($assoc['student_name'] ?? $assoc['name'] ?? $assoc['full_name'] ?? ''));
            $paid = $assoc['total_fees_paid'] ?? $assoc['fees_paid'] ?? $assoc['total_paid'] ?? $assoc['amount'] ?? null;
            if ($admission === '') {
                continue;
            }
            if (!is_numeric($paid)) {
                $paid = 0;
            }
            $importRows[] = [
                'admission_number' => $admission,
                'student_name' => $name ?: $admission,
                'total_fees_paid' => (float) $paid,
            ];
        }

        if (empty($importRows)) {
            return back()->withErrors([
                'file' => 'No valid rows found. Expected columns: admission_number (or admission_no, adm_no), student_name (or name), total_fees_paid (or fees_paid, amount).',
            ]);
        }

        $systemByAdmission = $this->buildSystemData($year, $termNumber);
        $preview = [];
        $importByAdmission = [];

        foreach ($importRows as $row) {
            $adm = $row['admission_number'];
            $importByAdmission[$adm] = $row;
        }

        $allAdmissions = collect(array_keys($systemByAdmission))->merge(array_keys($importByAdmission))->unique();
        $familyGroups = [];

        foreach ($allAdmissions as $admission) {
            $sys = $systemByAdmission[$admission] ?? null;
            $imp = $importByAdmission[$admission] ?? null;
            $student = $sys ? Student::withArchived()->find($sys['student_id']) : null;
            if (!$student && $imp) {
                $student = Student::withArchived()->where('admission_number', $admission)->first();
            }

            $systemPaid = $sys['total_paid'] ?? 0;
            $systemInvoiced = $sys['total_invoiced'] ?? 0;
            $importPaid = $imp ? (float) ($imp['total_fees_paid'] ?? 0) : 0;
            $importName = $imp ? ($imp['student_name'] ?? $admission) : $admission;

            $status = 'ok';
            $message = null;
            $difference = null;
            $familyNote = null;

            if (!$student) {
                $status = 'missing_student';
                $message = 'Student not found in system';
                $preview[] = [
                    'admission_number' => $admission,
                    'student_name' => $importName,
                    'student_id' => null,
                    'classroom' => null,
                    'family_id' => null,
                    'system_total_invoiced' => null,
                    'system_total_paid' => null,
                    'import_total_paid' => $importPaid,
                    'difference' => null,
                    'status' => $status,
                    'message' => $message,
                    'family_note' => null,
                ];
                continue;
            }

            if ($imp === null) {
                $status = 'in_system_only';
                $message = 'In system but not in import';
            } else {
                $diff = $importPaid - $systemPaid;
                if (abs($diff) > 0.01) {
                    $difference = $diff;
                    $status = 'amount_differs';
                    $message = 'System KES ' . number_format($systemPaid, 2) . ' vs Import KES ' . number_format($importPaid, 2);
                }
            }

            $familyId = $student->family_id;
            if ($familyId && $imp !== null) {
                $familyGroups[$familyId] = $familyGroups[$familyId] ?? [
                    'admissions' => [],
                    'system_total' => 0,
                    'import_total' => 0,
                ];
                $familyGroups[$familyId]['admissions'][] = $admission;
                $familyGroups[$familyId]['system_total'] += $systemPaid;
                $familyGroups[$familyId]['import_total'] += $importPaid;
            }

            $preview[] = [
                'admission_number' => $admission,
                'student_name' => $student->full_name,
                'student_id' => $student->id,
                'classroom' => $student->classroom?->name ?? '—',
                'family_id' => $familyId,
                'system_total_invoiced' => $systemInvoiced,
                'system_total_paid' => $systemPaid,
                'import_total_paid' => $importPaid,
                'difference' => $difference,
                'status' => $status,
                'message' => $message,
                'family_note' => $familyNote,
            ];
        }

        foreach ($familyGroups as $fid => $g) {
            if (count($g['admissions']) < 2) {
                continue;
            }
            $sysTot = $g['system_total'];
            $impTot = $g['import_total'];
            $familyMatch = abs($sysTot - $impTot) < 0.01;
            $hasIndividualDiff = false;
            foreach ($g['admissions'] as $adm) {
                $row = collect($preview)->firstWhere('admission_number', $adm);
                if ($row && ($row['status'] ?? '') === 'amount_differs') {
                    $hasIndividualDiff = true;
                    break;
                }
            }
            foreach ($preview as &$r) {
                if (!in_array($r['admission_number'], $g['admissions'], true)) {
                    continue;
                }
                if (!$familyMatch) {
                    $r['family_note'] = 'Family total mismatch: System KES ' . number_format($sysTot, 2) . ' vs Import KES ' . number_format($impTot, 2);
                    if ($r['status'] === 'ok') {
                        $r['status'] = 'family_total_mismatch';
                        $r['message'] = $r['family_note'];
                    }
                } elseif ($hasIndividualDiff) {
                    $r['family_note'] = 'Family total matches; individual allocations differ.';
                }
            }
            unset($r);
        }

        usort($preview, function ($a, $b) {
            $order = [
                'missing_student' => 1,
                'family_total_mismatch' => 2,
                'amount_differs' => 3,
                'in_system_only' => 4,
                'ok' => 5,
            ];
            $ao = $order[$a['status']] ?? 5;
            $bo = $order[$b['status']] ?? 5;
            if ($ao !== $bo) {
                return $ao <=> $bo;
            }
            return strcmp($a['admission_number'], $b['admission_number']);
        });

        $missingCount = collect($preview)->where('status', 'missing_student')->count();
        $amountDiffCount = collect($preview)->where('status', 'amount_differs')->count();
        $familyMismatchCount = collect($preview)->where('status', 'family_total_mismatch')->count();
        $inSystemOnlyCount = collect($preview)->where('status', 'in_system_only')->count();
        $okCount = collect($preview)->where('status', 'ok')->count();
        $allocationDiffFamilies = 0;
        foreach ($familyGroups as $g) {
            if (count($g['admissions']) < 2) {
                continue;
            }
            if (abs($g['system_total'] - $g['import_total']) < 0.01) {
                foreach ($g['admissions'] as $adm) {
                    $row = collect($preview)->firstWhere('admission_number', $adm);
                    if ($row && $row['status'] === 'amount_differs') {
                        $allocationDiffFamilies++;
                        break;
                    }
                }
            }
        }

        $hasIssues = $missingCount > 0 || $amountDiffCount > 0 || $familyMismatchCount > 0;

        return view('finance.fees_comparison_import.preview', [
            'preview' => $preview,
            'hasIssues' => $hasIssues,
            'year' => $year,
            'term' => $termNumber,
            'summary' => [
                'total' => count($preview),
                'ok' => $okCount,
                'missing_student' => $missingCount,
                'amount_differs' => $amountDiffCount,
                'family_total_mismatch' => $familyMismatchCount,
                'in_system_only' => $inSystemOnlyCount,
                'allocation_diff_families' => $allocationDiffFamilies,
            ],
        ]);
    }

    /**
     * Download Excel template for fees comparison import.
     */
    public function template()
    {
        $headers = ['Student Name', 'Admission Number', 'Total Fees Paid'];
        $sample = [
            ['John Doe', 'ADM001', 50000],
            ['Jane Doe', 'ADM002', 45000],
        ];
        return Excel::download(new ArrayExport($sample, $headers), 'fees_comparison_import_template.xlsx');
    }

    private function buildSystemData(int $year, int $termNumber): array
    {
        $out = [];
        $students = Student::withArchived()
            ->with(['classroom', 'family'])
            ->get();

        foreach ($students as $student) {
            $invoice = Invoice::where('student_id', $student->id)
                ->where('year', $year)
                ->where('term', $termNumber)
                ->where('status', '!=', 'reversed')
                ->first();

            $totalInvoiced = $invoice ? (float) $invoice->total : 0;
            
            // Calculate paid_amount explicitly excluding reversed payments
            $totalPaid = 0;
            if ($invoice) {
                // Sum allocations from non-reversed payments only
                $totalPaid = (float) PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoice) {
                        $q->where('invoice_id', $invoice->id);
                    })
                    ->whereHas('payment', function($q) {
                        $q->where('reversed', false);
                    })
                    ->sum('amount');
            }

            $out[$student->admission_number] = [
                'student_id' => $student->id,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
            ];
        }

        return $out;
    }

    private function extractTermNumber(string $termName): int
    {
        preg_match('/\d+/', $termName, $m);
        return isset($m[0]) ? (int) $m[0] : 1;
    }
}

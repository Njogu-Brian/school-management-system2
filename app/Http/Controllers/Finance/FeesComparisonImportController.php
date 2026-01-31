<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentAllocation;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Models\FeesComparisonPreview;
use App\Models\SwimmingWallet;
use App\Exports\ArrayExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        } catch (\Throwable $e) {
            Log::error('Fees comparison Excel import failed', ['error' => $e->getMessage()]);
            $msg = $e->getMessage();
            if (str_contains($msg, 'ZipArchive') || str_contains($msg, 'zip') || str_contains(strtolower($msg), 'zip')) {
                return back()->withErrors([
                    'file' => 'The PHP Zip extension is not enabled. Enable the "zip" extension in php.ini to read .xlsx files. On Windows, add extension=zip (or extension=php_zip.dll) and restart the web server.',
                ]);
            }
            return back()->withErrors(['file' => 'Failed to read the Excel file: ' . $msg]);
        }

        if (empty($sheet)) {
            return back()->withErrors(['file' => 'The uploaded file is empty or could not be read.']);
        }

        // Detect header row (some files include an extra title row before headers)
        $headerRowIndex = null;
        $scanRows = array_slice($sheet, 0, 3);
        foreach ($scanRows as $idx => $row) {
            $cells = array_map(fn($c) => strtolower(trim((string) $c)), $row ?? []);
            $hasAdmission = collect($cells)->contains(fn($c) => str_contains($c, 'admission') || str_contains($c, 'adm'));
            $hasName = collect($cells)->contains(fn($c) => str_contains($c, 'student') || str_contains($c, 'name'));
            if ($hasAdmission && $hasName) {
                $headerRowIndex = $idx;
                break;
            }
        }
        if ($headerRowIndex === null) {
            $headerRowIndex = 0;
        }
        $headerRow = $sheet[$headerRowIndex] ?? [];
        $sheet = array_slice($sheet, $headerRowIndex + 1);
        if (empty($headerRow)) {
            return back()->withErrors(['file' => 'The file must contain a header row with column names.']);
        }

        $headers = [];
        foreach ($headerRow as $i => $h) {
            $headers[$i] = Str::slug(Str::lower(trim((string) $h)), '_');
        }

        // Import columns: support separate invoiced and paid for like-with-like comparison.
        // Paid: only total_fees_paid / fees_paid / total_paid (do NOT use amount/total_fees as paid).
        // Invoiced: total_fees / total_invoiced / invoice_total / amount (for invoice-vs-invoice match).
        $toNumber = function ($value): ?float {
            if ($value === null) {
                return null;
            }
            $str = trim((string) $value);
            if ($str === '') {
                return null;
            }
            // Remove currency and thousand separators (e.g. "KES 40,000.00")
            $normalized = preg_replace('/[^0-9\.\-]/', '', $str);
            if ($normalized === '' || $normalized === '-' || $normalized === '.') {
                return null;
            }
            return is_numeric($normalized) ? (float) $normalized : null;
        };
        $findKey = function (array $assoc, array $needles, array $exclude = []): ?string {
            foreach ($assoc as $key => $_val) {
                $k = (string) $key;
                $hasNeedle = false;
                foreach ($needles as $needle) {
                    if (str_contains($k, $needle)) {
                        $hasNeedle = true;
                        break;
                    }
                }
                if (!$hasNeedle) {
                    continue;
                }
                $isExcluded = false;
                foreach ($exclude as $ex) {
                    if (str_contains($k, $ex)) {
                        $isExcluded = true;
                        break;
                    }
                }
                if ($isExcluded) {
                    continue;
                }
                return $k;
            }
            return null;
        };

        $importRows = [];
        foreach ($sheet as $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }
            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $name = trim((string) ($assoc['student_name'] ?? $assoc['name'] ?? $assoc['full_name'] ?? ''));
            // Some imports use "Expected Total" for paid and "Total Fees Paid" for invoiced
            $paidPrimary = $assoc['expected_total']
                ?? $assoc['expected']
                ?? $assoc['expected_fees_paid']
                ?? $assoc['expected_paid']
                ?? null;
            $hasExpectedPaid = false;
            if ($paidPrimary === null) {
                $expectedKey = $findKey($assoc, ['expected']);
                if ($expectedKey) {
                    $paidPrimary = $assoc[$expectedKey];
                }
            }
            $paidFallback = $assoc['total_fees_paid']
                ?? $assoc['fees_paid']
                ?? $assoc['total_paid']
                ?? $assoc['amount_paid']
                ?? $assoc['paid']
                ?? $assoc['paid_amount']
                ?? null;
            if ($paidFallback === null) {
                $paidKey = $findKey($assoc, ['paid'], ['unpaid']);
                if ($paidKey) {
                    $paidFallback = $assoc[$paidKey];
                }
            }
            $invoiced = $assoc['total_fees']
                ?? $assoc['total_invoiced']
                ?? $assoc['invoice_total']
                ?? $assoc['total_fees_due']
                ?? $assoc['amount']
                ?? $assoc['total']
                ?? null;
            if ($invoiced === null) {
                $invoiceKey = $findKey($assoc, ['total', 'fees'], ['paid', 'expected']);
                if ($invoiceKey) {
                    $invoiced = $assoc[$invoiceKey];
                }
            }
            if ($admission === '') {
                continue;
            }
            $paidValue = $toNumber($paidPrimary);
            $paidFallbackValue = $toNumber($paidFallback);
            if ($paidValue !== null) {
                $hasExpectedPaid = true;
            }
            $invoicedValue = $toNumber($invoiced);
            if ($paidValue === null) {
                $paidValue = $paidFallbackValue;
            }
            // If "Expected Total" used for paid and "Total Fees Paid" holds invoiced, capture both
            if ($invoicedValue === null && $paidPrimary !== null && $paidFallbackValue !== null && $paidValue !== $paidFallbackValue) {
                $invoicedValue = $paidFallbackValue;
            }
            $importRows[] = [
                'admission_number' => $admission,
                'student_name' => $name ?: $admission,
                'total_fees_paid' => $paidValue,
                'total_invoiced' => $invoicedValue,
                'has_expected_paid' => $hasExpectedPaid,
            ];
        }

        if (empty($importRows)) {
            return back()->withErrors([
                'file' => 'No valid rows found. Expected columns: admission_number (or admission_no, adm_no), student_name (or name), and total_fees_paid (or fees_paid, total_paid) and/or total_fees (or total_invoiced, amount) for like-with-like comparison.',
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
        foreach ($allAdmissions as $admission) {
            $sys = $systemByAdmission[$admission] ?? null;
            $imp = $importByAdmission[$admission] ?? null;
            $student = $sys ? Student::withArchived()->find($sys['student_id']) : null;
            if (!$student && $imp) {
                $student = Student::withArchived()->where('admission_number', $admission)->first();
            }

            $systemPaid = $sys['total_paid'] ?? 0;
            $systemInvoiced = $sys['total_invoiced'] ?? 0;
            $importPaid = $imp && isset($imp['total_fees_paid']) && $imp['total_fees_paid'] !== null
                ? (float) $imp['total_fees_paid'] : null;
            $importInvoiced = $imp && isset($imp['total_invoiced']) && $imp['total_invoiced'] !== null
                ? (float) $imp['total_invoiced'] : null;
            $importName = $imp ? ($imp['student_name'] ?? $admission) : $admission;

            // If import has one value in the "paid" column but it matches system INVOICED (not paid), treat it as invoiced-only.
            // Common case: file has "Total Fees" = 33,900 in a column labeled "Total Fees Paid", so Imp inv was blank and Imp paid showed 33,900.
            if ($importInvoiced === null && $importPaid !== null && $systemInvoiced > 0 && empty($imp['has_expected_paid'])) {
                if (abs($importPaid - $systemInvoiced) <= 0.01 && abs($importPaid - $systemPaid) > 0.01) {
                    $importInvoiced = $importPaid;
                    $importPaid = null;
                } else {
                    // File has only one amount column: show it in Imp inv too so the column is never blank.
                    $importInvoiced = $importPaid;
                }
            }

            $status = 'ok';
            $message = null;
            $difference = null;
            if (!$student) {
                $status = 'missing_student';
                $message = 'Student not found or archived/alumni (excluded from import)';
                $preview[] = [
                    'admission_number' => $admission,
                    'student_name' => $importName,
                    'student_id' => null,
                    'classroom' => null,
                    'family_id' => null,
                    'parent_phone' => null,
                    'system_total_invoiced' => null,
                    'system_total_paid' => null,
                    'system_invoice_balance' => null,
                    'system_swimming_balance' => null,
                    'import_total_paid' => $importPaid,
                    'import_total_invoiced' => $importInvoiced,
                    'difference' => null,
                    'status' => $status,
                    'message' => $message,
                    'invoice_diff' => null,
                    'payment_diff' => null,
                ];
                continue;
            }

            if ($imp === null) {
                $status = 'in_system_only';
                $message = 'In system but not in import';
            } else {
                // Like-with-like: compare paid vs paid when import has paid; compare invoiced vs invoiced when import has invoiced.
                $paidDiff = ($importPaid !== null) ? ($importPaid - $systemPaid) : null;
                $invDiff = ($importInvoiced !== null) ? ($importInvoiced - $systemInvoiced) : null;
                $paidMatch = ($importPaid === null) || (abs($importPaid - $systemPaid) <= 0.01);
                $invMatch = ($importInvoiced === null) || (abs($importInvoiced - $systemInvoiced) <= 0.01);

                if (!$paidMatch || !$invMatch) {
                    $status = 'amount_differs';
                    $difference = !$invMatch ? $invDiff : $paidDiff;
                    $messageParts = [];
                    if (!$invMatch) {
                        $messageParts[] = 'Invoiced: Sys KES ' . number_format($systemInvoiced, 2) . ' vs Imp KES ' . number_format($importInvoiced ?? 0, 2);
                    }
                    if (!$paidMatch) {
                        $messageParts[] = 'Paid: Sys KES ' . number_format($systemPaid, 2) . ' vs Imp KES ' . number_format($importPaid ?? 0, 2);
                    }
                    $message = implode('; ', $messageParts);
                }
            }

            $preview[] = [
                'admission_number' => $admission,
                'student_name' => $student->full_name,
                'student_id' => $student->id,
                'classroom' => $student->classroom?->name ?? '—',
                'parent_phone' => $sys['parent_phone'] ?? null,
                'system_total_invoiced' => $systemInvoiced,
                'system_total_paid' => $systemPaid,
                'system_invoice_balance' => $sys['invoice_balance'] ?? null,
                'system_swimming_balance' => $sys['swimming_balance'] ?? null,
                'import_total_paid' => $importPaid,
                'import_total_invoiced' => $importInvoiced,
                'difference' => $difference,
                'status' => $status,
                'message' => $message,
                'invoice_diff' => $invMatch ? null : $invDiff,
                'payment_diff' => $paidMatch ? null : $paidDiff,
            ];
        }

        usort($preview, function ($a, $b) {
            $order = [
                'missing_student' => 1,
                'amount_differs' => 2,
                'in_system_only' => 3,
                'ok' => 4,
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
        $inSystemOnlyCount = collect($preview)->where('status', 'in_system_only')->count();
        $okCount = collect($preview)->where('status', 'ok')->count();
        $hasIssues = $missingCount > 0 || $amountDiffCount > 0;

        $summary = [
            'total' => count($preview),
            'ok' => $okCount,
            'missing_student' => $missingCount,
            'amount_differs' => $amountDiffCount,
            'in_system_only' => $inSystemOnlyCount,
        ];

        $saved = FeesComparisonPreview::create([
            'user_id' => auth()->id(),
            'year' => $year,
            'term' => $termNumber,
            'preview_data' => ['preview' => $preview, 'summary' => $summary],
            'has_issues' => $hasIssues,
        ]);

        return redirect()->route('finance.fees-comparison-import.show', $saved);
    }

    /**
     * Show a saved comparison preview (allows returning from fee statement).
     * View filter: all | match | families | individual (same style as bank statements).
     */
    public function show(Request $request, FeesComparisonPreview $preview)
    {
        $data = $preview->preview_data;
        $previewRows = $data['preview'] ?? [];
        $summary = $data['summary'] ?? [];
        $year = $preview->year;
        $term = $preview->term;
        $view = $request->get('view', 'all');

        // Treat all students individually (no family grouping)
        $previewGrouped = array_map(function ($row) {
            return [
                'family_id' => null,
                'rows' => [$row],
                'system_paid_total' => (float) ($row['system_total_paid'] ?? 0),
                'import_paid_total' => (float) ($row['import_total_paid'] ?? 0),
            ];
        }, $previewRows);

        // Counts for filter tabs (like bank statements)
        $countMatch = (int) ($summary['ok'] ?? 0);
        $countIndividual = count($previewRows);
        $countFamilies = 0;
        $counts = [
            'all' => count($previewRows),
            'match' => $countMatch,
            'families' => $countFamilies,
            'individual' => $countIndividual,
        ];

        // Apply view filter
        $filtered = $previewGrouped;
        if ($view === 'match') {
            $filtered = [];
            foreach ($previewGrouped as $g) {
                $okRows = array_values(array_filter($g['rows'] ?? [], fn ($r) => ($r['status'] ?? '') === 'ok'));
                if (!empty($okRows)) {
                    $filtered[] = [
                        'family_id' => null,
                        'rows' => $okRows,
                        'system_paid_total' => array_sum(array_map(fn ($r) => (float) ($r['system_total_paid'] ?? 0), $okRows)),
                        'import_paid_total' => array_sum(array_map(fn ($r) => (float) ($r['import_total_paid'] ?? 0), $okRows)),
                    ];
                }
            }
        } elseif ($view === 'families') {
            // No family grouping; show empty list for families view
            $filtered = [];
        }

        return view('finance.fees_comparison_import.preview', [
            'preview' => $previewRows,
            'previewGrouped' => $filtered,
            'hasIssues' => $preview->has_issues,
            'year' => $year,
            'term' => $term,
            'summary' => $summary,
            'previewId' => $preview->id,
            'view' => $view,
            'counts' => $counts,
        ]);
    }

    /**
     * Download Excel template for fees comparison import.
     * Include both Total Fees (invoiced) and Total Fees Paid for like-with-like comparison.
     */
    public function template()
    {
        $headers = ['Student Name', 'Admission Number', 'Total Fees', 'Total Fees Paid'];
        $sample = [
            ['John Doe', 'ADM001', 50000, 45000],
            ['Jane Doe', 'ADM002', 48000, 45000],
        ];
        return Excel::download(new ArrayExport($sample, $headers), 'fees_comparison_import_template.xlsx');
    }

    /**
     * Build system totals for comparison: total invoiced and total paid for the selected year and term only.
     * Excludes legacy data and other terms. Uses only the invoice for that student/year/term and allocations to that invoice.
     */
    private function buildSystemData(int $year, int $termNumber): array
    {
        $out = [];
        // Payment IDs that come from "payments marked as swimming" (excluded from fee totals)
        $swimmingPaymentIds = $this->getSwimmingPaymentIds();

        // Resolve the selected year + term to a specific term_id when possible (so totals are strictly for this term)
        $termId = null;
        $academicYear = AcademicYear::where('year', $year)->first();
        if ($academicYear) {
            $term = Term::where('academic_year_id', $academicYear->id)
                ->where(function ($q) use ($termNumber) {
                    $q->where('name', 'like', "Term {$termNumber}%")
                        ->orWhere('name', 'like', "%Term {$termNumber}%");
                })
                ->first();
            $termId = $term?->id;
        }

        // Exclude archived and alumni – they are not included in the import comparison
        $students = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom', 'family', 'parent'])
            ->get();

        foreach ($students as $student) {
            // Invoice for this student and this term only (no legacy, no other terms)
            $invoiceQuery = Invoice::where('student_id', $student->id)
                ->where(function ($q) use ($year, $termNumber, $termId) {
                    if ($termId) {
                        $q->where('term_id', $termId);
                    } else {
                        $q->where('year', $year)->where('term', $termNumber);
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'reversed');
                })
                ->whereNull('reversed_at');
            $invoice = $invoiceQuery->first();

            $totalInvoiced = 0;
            $totalPaid = 0;
            if ($invoice) {
                // Total invoiced: only items on this term's invoice
                $itemsQuery = InvoiceItem::where('invoice_id', $invoice->id)->where('status', 'active');
                $itemsQuery->where(function ($q) {
                    $q->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                });
                $totalInvoiced = (float) $itemsQuery->get()->sum(fn ($i) => (float) ($i->amount ?? 0) - (float) ($i->discount_amount ?? 0));

                // Total paid: only allocations to this term's invoice (no legacy, no other terms)
                $allocationsQuery = PaymentAllocation::whereHas('invoiceItem', function ($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id);
                    $q->where(function ($q2) {
                        $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                    });
                })->whereHas('payment', function ($q) use ($swimmingPaymentIds) {
                    $q->where('reversed', false);
                    if (!empty($swimmingPaymentIds)) {
                        $q->whereNotIn('id', $swimmingPaymentIds);
                    }
                });
                $totalPaid = (float) $allocationsQuery->sum('amount');
            }

            $invoiceBalance = $totalInvoiced - $totalPaid;
            $swimmingBalance = (float) (SwimmingWallet::where('student_id', $student->id)->value('balance') ?? 0);
            $parentPhone = $student->parent
                ? ($student->parent->father_phone ?? $student->parent->mother_phone ?? $student->parent->guardian_phone ?? null)
                : null;

            $out[$student->admission_number] = [
                'student_id' => $student->id,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'invoice_balance' => max(0, $invoiceBalance),
                'swimming_balance' => $swimmingBalance,
                'parent_phone' => $parentPhone,
            ];
        }

        return $out;
    }

    private function extractTermNumber(string $termName): int
    {
        preg_match('/\d+/', $termName, $m);
        return isset($m[0]) ? (int) $m[0] : 1;
    }

    /**
     * Payment IDs that originate from transactions marked as swimming (bank or M-PESA).
     * These must be excluded from fee-statement / comparison totals.
     */
    private function getSwimmingPaymentIds(): array
    {
        $ids = collect();
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\BankStatementTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\MpesaC2BTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        return $ids->unique()->filter()->values()->toArray();
    }

    /**
     * Group preview rows by family_id for display: individual students first, then families.
     * Only rows with a real family_id (non-null, non-zero) are grouped as a family; others get one row per student.
     */
    private function groupPreviewByFamily(array $previewRows): array
    {
        $groups = [];
        $soloIndex = 0;
        foreach ($previewRows as $row) {
            $fid = $row['family_id'] ?? null;
            $hasFamily = $fid !== null && $fid !== 0 && $fid !== '' && (string) $fid !== '0';
            $key = $hasFamily ? ('f_' . $fid) : ('solo_' . $soloIndex++);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'family_id' => $hasFamily ? $fid : null,
                    'rows' => [],
                    'system_paid_total' => 0,
                    'import_paid_total' => 0,
                ];
            }
            $groups[$key]['rows'][] = $row;
            $groups[$key]['system_paid_total'] += (float) ($row['system_total_paid'] ?? 0);
            $groups[$key]['import_paid_total'] += (float) ($row['import_total_paid'] ?? 0);
        }
        // Individuals first, then families (like bank-statement style)
        $solo = [];
        $families = [];
        foreach ($groups as $g) {
            $isFamily = ($g['family_id'] ?? null) && count($g['rows']) > 1;
            if ($isFamily) {
                $families[] = $g;
            } else {
                $solo[] = $g;
            }
        }
        return array_merge($solo, $families);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBulkImport;
use App\Models\JournalBulkImportRow;
use App\Models\Votehead;
use App\Models\Student;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class JournalController extends Controller
{
    private function standardVoteheadsQuery()
    {
        // "Standard voteheads" = voteheads that belong to the school's fee system,
        // not one-off custom invoice lines (which should not be stored as voteheads).
        // Heuristic: include voteheads that are mandatory OR appear in fee structures OR optional fees.
        return Votehead::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_mandatory', true)
                    ->orWhereHas('feeCharges')
                    ->orWhereHas('optionalFees');
            })
            ->orderBy('name');
    }

    public function index(Request $request)
    {
        // Optimize journal query - limit eager loading
        $query = \App\Models\Journal::with([
            'student:id,first_name,last_name,admission_number',
            'votehead:id,name',
            'invoice:id,invoice_number,student_id,year,term',
            'invoiceItem:id,invoice_id,votehead_id'
        ])
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->year))
            ->when($request->filled('term'), fn($q) => $q->where('term', $request->term));

        $journals = $query->latest()->paginate(20)->withQueryString();
        
        // Build invoice filter subquery once for reuse
        $buildInvoiceSubquery = function() use ($request) {
            $query = \App\Models\Invoice::query()->select('id');
            
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }
            if ($request->filled('term')) {
                $query->where('term', $request->term);
            }
            
            return $query;
        };
        
        // Optimize credit notes query - use whereIn with subquery for better performance
        $creditNotesQuery = \App\Models\CreditNote::whereIn('invoice_id', $buildInvoiceSubquery())
            ->with([
                'invoice:id,invoice_number,student_id,year,term',
                'invoice.student:id,first_name,last_name,admission_number',
                'invoiceItem:id,invoice_id,votehead_id',
                'invoiceItem.votehead:id,name',
                'issuedBy:id,name'
            ]);
            
        $creditNotes = $creditNotesQuery->latest('credit_notes.created_at')
            ->paginate(20)
            ->withQueryString();
            
        // Optimize debit notes query - use whereIn with subquery for better performance
        $debitNotesQuery = \App\Models\DebitNote::whereIn('invoice_id', $buildInvoiceSubquery())
            ->with([
                'invoice:id,invoice_number,student_id,year,term',
                'invoice.student:id,first_name,last_name,admission_number',
                'invoiceItem:id,invoice_id,votehead_id',
                'invoiceItem.votehead:id,name',
                'issuedBy:id,name'
            ]);
            
        $debitNotes = $debitNotesQuery->latest('debit_notes.created_at')
            ->paginate(20)
            ->withQueryString();
        
        // Load students efficiently - only load a reasonable number for dropdown
        // Use a simple query without complex joins to prevent timeout
        // Exclude alumni and archived students
        $students = \App\Models\Student::select('id', 'first_name', 'last_name', 'admission_number')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('first_name')
            ->limit(500) // Limit to prevent timeout on large datasets
            ->get();
        
        // Load voteheads for import form
        $voteheads = $this->standardVoteheadsQuery()->get();
        
        // Get current year and term for import form defaults
        $currentYear = now()->year;
        $currentTerm = \App\Models\Term::where('is_current', true)->first();
        $currentTermNumber = $currentTerm ? (int) preg_replace('/[^0-9]/', '', $currentTerm->name) : 1;
        
        return view('finance.credit_debit_adjustments.index', compact('journals', 'creditNotes', 'debitNotes', 'students', 'voteheads', 'currentYear', 'currentTermNumber'));
    }

    public function create()
    {
        return view('finance.journals.create', [
            'voteheads'=> $this->standardVoteheadsQuery()->get(),
        ]);
    }
    
    public function getInvoiceVoteheads(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
        ]);
        
        // Find invoice for student, year, term
        $invoice = \App\Models\Invoice::where('student_id', $request->student_id)
            ->where('year', $request->year)
            ->where('term', $request->term)
            ->first();
        
        if (!$invoice) {
            return response()->json([
                'voteheads' => [],
                'message' => 'No invoice found for this student, year, and term.'
            ]);
        }
        
        // Invoice items map (for "current amount" hinting)
        $invoiceVoteheads = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
            ->where('status', 'active')
            ->with('votehead:id,name')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->votehead_id,
                    'name' => $item->votehead->name ?? 'Unknown',
                    'amount' => $item->amount ?? 0,
                ];
            })
            ->unique('id')
            ->values();

        $invoiceAmounts = $invoiceVoteheads->keyBy('id')->map(fn ($v) => (float) ($v['amount'] ?? 0));

        // Return ALL standard voteheads (excluding custom ones) whether or not present on invoice
        $all = $this->standardVoteheadsQuery()->get(['id', 'name'])->map(function ($vh) use ($invoiceAmounts) {
            $amt = (float) ($invoiceAmounts[$vh->id] ?? 0);
            return [
                'id' => $vh->id,
                'name' => $vh->name,
                'amount' => $amt,
                'in_invoice' => $invoiceAmounts->has($vh->id),
            ];
        })->values();
        
        return response()->json([
            'voteheads' => $all,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'votehead_id' => 'required|exists:voteheads,id',
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'effective_date' => 'nullable|date',
        ]);

        try {
            $j = \App\Services\JournalService::createAndApply($validated);

            return redirect()->route('finance.invoices.show', $j->invoice_id)
                ->with('success', "Journal {$j->journal_number} applied successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function bulkForm()
    {
        $preview = null;
        $importId = session('journal_bulk_import_id');
        if ($importId) {
            $import = JournalBulkImport::with('rows')->find($importId);
            if ($import) {
                $preview = $this->previewPayloadFromImport($import);
            } else {
                session()->forget('journal_bulk_import_id');
            }
        }

        return view('finance.credit_debit_adjustments.bulk', [
            'preview' => $preview,
            'recentImports' => JournalBulkImport::with('uploader')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function bulkHistory()
    {
        $imports = JournalBulkImport::with('uploader')
            ->latest()
            ->paginate(20);

        return view('finance.credit_debit_adjustments.bulk_history', compact('imports'));
    }

    public function bulkHistoryShow(JournalBulkImport $import)
    {
        $import->load(['uploader', 'rows.journal']);

        return view('finance.credit_debit_adjustments.bulk_history_show', compact('import'));
    }

    public function template()
    {
        // CSV template with headings
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="journal_bulk_template.csv"',
        ];

        $columns = [
            'admission_number',
            'votehead_name',
            'effective_date', // YYYY-MM-DD or DD/MM/YYYY (optional)
            'type',           // Cr or Dr
            'year',
            'term',           // 1,2,3
            'reason',
            'amount',         // positive number
        ];

        $callback = function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            // sample row
            fputcsv($out, ['ADM001', 'Tuition', date('Y-m-d'), 'Dr', date('Y'), 1, 'Top up', '5000']);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Parse upload into a preview (does not apply adjustments yet).
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];
        if (empty($sheet)) {
            return back()->with('error', 'The uploaded file appears to be empty.');
        }

        // Get headers from first row
        $headerRow = array_shift($sheet);
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::of((string) $header)->lower()->snake()->toString();
        }

        $previewRows = [];
        $valid = 0;
        $invalid = 0;

        // Track running item balances within this file so sequential rows for the same
        // student+votehead+year+term show a correct projected "new total".
        $running = [];
        $runningBal = [];

        foreach ($sheet as $idx => $row) {
            $rowArr = [];
            foreach ($headers as $colIndex => $headerName) {
                $value = $row[$colIndex] ?? null;
                $rowArr[$headerName] = is_string($value) ? trim($value) : $value;
            }

            // Skip fully empty trailing rows
            if (collect($rowArr)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            $line = $idx + 2;
            $adm = $rowArr['admission_number'] ?? null;
            $vhName = $rowArr['votehead_name'] ?? null;
            $eff = $rowArr['effective_date'] ?? null;
            $type = $rowArr['type'] ?? null;
            $year = (int) ($rowArr['year'] ?? 0);
            $term = (int) ($rowArr['term'] ?? 0);
            $reason = $rowArr['reason'] ?? null;
            $amt = $rowArr['amount'] ?? null;

            if (is_numeric($amt)) {
                $amt = abs((float) $amt);
            } else {
                $amt = null;
            }

            $preview = [
                'uid' => (string) Str::uuid(),
                'line' => $line,
                'status' => 'invalid',
                'message' => '',
                'admission_number' => (string) ($adm ?? ''),
                'student_id' => null,
                'student_name' => '',
                'votehead_id' => null,
                'votehead_name' => (string) ($vhName ?? ''),
                'effective_date' => '',
                'type' => '',
                'type_label' => (string) ($type ?? ''),
                'year' => $year,
                'term' => $term,
                'reason' => (string) ($reason ?? ''),
                'amount' => $amt ?? 0,
                'before_amount' => null,
                'new_amount' => null,
                'invoice_id' => null,
                'invoice_balance_before' => null,
                'invoice_balance_after' => null,
            ];

            $err = [];
            if ($eff !== null && $eff !== '') {
                try {
                    $eff = $this->parseFlexibleDate($eff);
                    $preview['effective_date'] = $eff;
                } catch (\InvalidArgumentException $e) {
                    $err[] = $e->getMessage();
                    $eff = null;
                }
            } else {
                $eff = null;
            }

            if (!$adm) {
                $err[] = 'admission_number missing';
            }
            if (!$vhName) {
                $err[] = 'votehead_name missing';
            }
            if (!$type) {
                $err[] = 'type missing';
            }
            if (!$year) {
                $err[] = 'year missing';
            }
            if (!in_array($term, [1, 2, 3], true)) {
                $err[] = 'term must be 1,2 or 3';
            }
            if (!$reason) {
                $err[] = 'reason missing';
            }
            if ($amt === null || $amt <= 0) {
                $err[] = 'amount must be > 0';
            }

            $student = $adm ? $this->findStudentByAdmission((string) $adm) : null;
            if ($adm && !$student) {
                $err[] = "student not found for admission_number '{$adm}'";
            }

            $votehead = $vhName ? $this->findVoteheadByName((string) $vhName) : null;
            if ($vhName && !$votehead) {
                $err[] = "votehead not found for '{$vhName}'";
            }

            $typeNorm = null;
            if ($type) {
                $t = mb_strtolower((string) $type);
                if (in_array($t, ['dr', 'debit'], true)) {
                    $typeNorm = 'debit';
                }
                if (in_array($t, ['cr', 'credit'], true)) {
                    $typeNorm = 'credit';
                }
            }
            if (!$typeNorm) {
                $err[] = "type '{$type}' must be Cr/Dr or credit/debit";
            }

            if ($err) {
                $preview['status'] = 'invalid';
                $preview['message'] = implode('; ', $err);
                $invalid++;
                $previewRows[] = $preview;
                continue;
            }

            $preview['student_id'] = $student->id;
            $preview['student_name'] = $student->full_name;
            $preview['votehead_id'] = $votehead->id;
            $preview['votehead_name'] = $votehead->name;
            $preview['type'] = $typeNorm;
            $preview['type_label'] = $typeNorm === 'credit' ? 'Cr' : 'Dr';
            $preview['amount'] = (float) $amt;
            $preview['effective_date'] = $eff ?: null;

            $invoice = Invoice::where('student_id', $student->id)
                ->where('year', $year)
                ->where('term', $term)
                ->first();

            if (!$invoice) {
                $preview['status'] = 'invalid';
                $preview['message'] = "No invoice for Year {$year}, Term {$term}";
                $invalid++;
                $previewRows[] = $preview;
                continue;
            }

            $preview['invoice_id'] = $invoice->id;

            $runKey = "{$student->id}:{$votehead->id}:{$year}:{$term}";
            if (!array_key_exists($runKey, $running)) {
                $item = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $votehead->id)
                    ->first();
                $running[$runKey] = (float) ($item?->amount ?? 0);
                $runningBal[$runKey] = (float) ($invoice->balance ?? 0);
            }

            $before = $running[$runKey];
            $delta = $typeNorm === 'debit' ? (float) $amt : -(float) $amt;
            $new = $before + $delta;
            $balBefore = $runningBal[$runKey];
            $balAfter = $balBefore + $delta;

            $running[$runKey] = $new;
            $runningBal[$runKey] = $balAfter;

            $preview['before_amount'] = round($before, 2);
            $preview['new_amount'] = round($new, 2);
            $preview['invoice_balance_before'] = round($balBefore, 2);
            $preview['invoice_balance_after'] = round($balAfter, 2);
            $preview['status'] = 'pending';
            $preview['message'] = 'Ready for approval';
            $valid++;
            $previewRows[] = $preview;
        }

        // Abandon any previous unfinished preview for this user session
        if ($oldId = session('journal_bulk_import_id')) {
            $old = JournalBulkImport::find($oldId);
            if ($old && $old->approved_rows === 0 && in_array($old->status, ['preview', 'abandoned'], true)) {
                $old->update(['status' => 'abandoned']);
            }
        }

        $import = JournalBulkImport::create([
            'batch_code' => JournalBulkImport::nextBatchCode(),
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
            'status' => 'preview',
            'total_rows' => count($previewRows),
            'pending_rows' => $valid,
            'approved_rows' => 0,
            'failed_rows' => 0,
            'invalid_rows' => $invalid,
        ]);

        foreach ($previewRows as $row) {
            JournalBulkImportRow::create([
                'journal_bulk_import_id' => $import->id,
                'uid' => $row['uid'],
                'line' => $row['line'],
                'admission_number' => $row['admission_number'] ?: null,
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name'] ?: null,
                'votehead_id' => $row['votehead_id'],
                'votehead_name' => $row['votehead_name'] ?: null,
                'effective_date' => $row['effective_date'] ?: null,
                'type' => $row['type'] ?: null,
                'type_label' => $row['type_label'] ?: null,
                'year' => $row['year'] ?: null,
                'term' => $row['term'] ?: null,
                'reason' => $row['reason'] ?: null,
                'amount' => $row['amount'] ?? 0,
                'before_amount' => $row['before_amount'],
                'new_amount' => $row['new_amount'],
                'invoice_balance_before' => $row['invoice_balance_before'],
                'invoice_balance_after' => $row['invoice_balance_after'],
                'invoice_id' => $row['invoice_id'],
                'status' => $row['status'],
                'message' => $row['message'],
            ]);
        }

        session(['journal_bulk_import_id' => $import->id]);
        session()->forget('journal_bulk_preview');

        return redirect()
            ->route('finance.journals.bulk.form')
            ->with('success', "Preview {$import->batch_code} ready: {$valid} valid, {$invalid} invalid. Review and approve below.");
    }

    /**
     * Approve selected (or all pending) preview rows.
     */
    public function bulkApprove(Request $request)
    {
        $import = JournalBulkImport::with('rows')->find(session('journal_bulk_import_id'));
        if (!$import || $import->rows->isEmpty()) {
            return redirect()
                ->route('finance.journals.bulk.form')
                ->with('error', 'No preview to approve. Upload a file first.');
        }

        $mode = $request->input('mode', 'selected'); // selected | all
        $uids = collect($request->input('uids', []))->filter()->values()->all();

        if ($mode === 'selected' && empty($uids)) {
            return back()->with('error', 'Select at least one row to approve.');
        }

        $approved = 0;
        $failed = 0;

        foreach ($import->rows as $dbRow) {
            if ($dbRow->status !== 'pending') {
                continue;
            }
            if ($mode === 'selected' && !in_array($dbRow->uid, $uids, true)) {
                continue;
            }

            try {
                $invoice = Invoice::find($dbRow->invoice_id);
                if (!$invoice) {
                    throw new \RuntimeException('Invoice no longer exists');
                }

                $item = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $dbRow->votehead_id)
                    ->first();
                $before = (float) ($item?->amount ?? 0);
                $delta = ($dbRow->type === 'debit') ? (float) $dbRow->amount : -(float) $dbRow->amount;
                $balBefore = (float) ($invoice->balance ?? 0);

                $j = JournalService::createAndApply([
                    'student_id' => $dbRow->student_id,
                    'votehead_id' => $dbRow->votehead_id,
                    'year' => $dbRow->year,
                    'term' => $dbRow->term,
                    'type' => $dbRow->type,
                    'amount' => (float) $dbRow->amount,
                    'reason' => $dbRow->reason,
                    'effective_date' => $dbRow->effective_date?->format('Y-m-d'),
                ]);

                $meta = $j->meta ?? [];
                $meta['bulk_import_id'] = $import->id;
                $meta['bulk_batch_code'] = $import->batch_code;
                $j->update(['meta' => $meta]);

                $invoice->refresh();
                $dbRow->update([
                    'before_amount' => round($before, 2),
                    'new_amount' => round($before + $delta, 2),
                    'invoice_balance_before' => round($balBefore, 2),
                    'invoice_balance_after' => round((float) $invoice->balance, 2),
                    'journal_id' => $j->id,
                    'status' => 'approved',
                    'message' => 'Applied as ' . $j->journal_number,
                ]);
                $approved++;
            } catch (\Throwable $e) {
                $dbRow->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $import->refreshCounts();

        $msg = "Approved {$approved} adjustment(s) for {$import->batch_code}.";
        if ($failed > 0) {
            $msg .= " {$failed} failed.";
        }

        return redirect()
            ->route('finance.journals.bulk.form')
            ->with($failed > 0 && $approved === 0 ? 'error' : 'success', $msg);
    }

    public function bulkClearPreview()
    {
        $importId = session('journal_bulk_import_id');
        if ($importId) {
            $import = JournalBulkImport::find($importId);
            if ($import && $import->approved_rows === 0) {
                $import->update(['status' => 'abandoned']);
            }
        }

        session()->forget(['journal_bulk_import_id', 'journal_bulk_preview']);

        return redirect()
            ->route('finance.journals.bulk.form')
            ->with('success', 'Preview cleared.');
    }

    private function previewPayloadFromImport(JournalBulkImport $import): array
    {
        return [
            'batch_code' => $import->batch_code,
            'import_id' => $import->id,
            'created_at' => optional($import->created_at)->toDateTimeString(),
            'valid' => $import->pending_rows + $import->approved_rows + $import->failed_rows,
            'invalid' => $import->invalid_rows,
            'pending' => $import->pending_rows,
            'approved' => $import->approved_rows,
            'failed' => $import->failed_rows,
            'total' => $import->total_rows,
            'rows' => $import->rows->map(fn (JournalBulkImportRow $r) => [
                'uid' => $r->uid,
                'line' => $r->line,
                'status' => $r->status,
                'message' => $r->message,
                'admission_number' => $r->admission_number,
                'student_id' => $r->student_id,
                'student_name' => $r->student_name,
                'votehead_id' => $r->votehead_id,
                'votehead_name' => $r->votehead_name,
                'effective_date' => $r->effective_date?->format('Y-m-d'),
                'type' => $r->type,
                'type_label' => $r->type_label,
                'year' => $r->year,
                'term' => $r->term,
                'reason' => $r->reason,
                'amount' => (float) $r->amount,
                'before_amount' => $r->before_amount !== null ? (float) $r->before_amount : null,
                'new_amount' => $r->new_amount !== null ? (float) $r->new_amount : null,
                'invoice_id' => $r->invoice_id,
                'invoice_balance_before' => $r->invoice_balance_before !== null ? (float) $r->invoice_balance_before : null,
                'invoice_balance_after' => $r->invoice_balance_after !== null ? (float) $r->invoice_balance_after : null,
            ])->all(),
        ];
    }

    private function parseFlexibleDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException('effective_date is empty');
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                // fall through to string formats
            }
        }

        $value = trim((string) $value);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed && $parsed->format($format) === $value) {
                return $parsed->format('Y-m-d');
            }
        }

        // Lenient parse for common day/month/year shapes
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        throw new \InvalidArgumentException("Could not parse date '{$value}'. Use YYYY-MM-DD or DD/MM/YYYY.");
    }

    private function findStudentByAdmission(string $admission): ?Student
    {
        $admission = trim($admission);

        $student = Student::where('admission_number', $admission)->first();
        if ($student) {
            return $student;
        }

        return Student::whereRaw('UPPER(TRIM(admission_number)) = ?', [mb_strtoupper($admission)])->first();
    }

    private function findVoteheadByName(string $name): ?Votehead
    {
        $normalized = mb_strtolower(trim($name));

        return Votehead::whereRaw('LOWER(name) = ?', [$normalized])->first()
            ?? Votehead::whereRaw('LOWER(name) LIKE ?', ['%' . $normalized . '%'])->first();
    }
}

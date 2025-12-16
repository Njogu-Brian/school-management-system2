<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Votehead;
use App\Models\Student;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Journal::with(['student', 'votehead', 'invoice', 'invoiceItem'])
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->year))
            ->when($request->filled('term'), fn($q) => $q->where('term', $request->term));

        $journals = $query->latest()->paginate(20)->withQueryString();
        
        // Also get credit and debit notes for display (all of them, not just from journals)
        // Only show notes that have invoices (whereHas ensures invoice exists)
        $creditNotesQuery = \App\Models\CreditNote::with(['invoice.student', 'invoiceItem.votehead', 'issuedBy'])
            ->whereHas('invoice'); // Only show notes with valid invoices
            
        // Apply filters only if they're explicitly provided
        if ($request->filled('student_id')) {
            $creditNotesQuery->whereHas('invoice', fn($iq) => $iq->where('student_id', $request->student_id));
        }
        if ($request->filled('year')) {
            $creditNotesQuery->whereHas('invoice', fn($iq) => $iq->where('year', $request->year));
        }
        if ($request->filled('term')) {
            $creditNotesQuery->whereHas('invoice', fn($iq) => $iq->where('term', $request->term));
        }
            
        $creditNotes = $creditNotesQuery->latest()->paginate(20)->withQueryString();
            
        $debitNotesQuery = \App\Models\DebitNote::with(['invoice.student', 'invoiceItem.votehead', 'issuedBy'])
            ->whereHas('invoice'); // Only show notes with valid invoices
            
        // Apply filters only if they're explicitly provided
        if ($request->filled('student_id')) {
            $debitNotesQuery->whereHas('invoice', fn($iq) => $iq->where('student_id', $request->student_id));
        }
        if ($request->filled('year')) {
            $debitNotesQuery->whereHas('invoice', fn($iq) => $iq->where('year', $request->year));
        }
        if ($request->filled('term')) {
            $debitNotesQuery->whereHas('invoice', fn($iq) => $iq->where('term', $request->term));
        }
            
        $debitNotes = $debitNotesQuery->latest()->paginate(20)->withQueryString();
        
        $students = \App\Models\Student::orderBy('first_name')->get();
        
        return view('finance.credit_debit_adjustments.index', compact('journals', 'creditNotes', 'debitNotes', 'students'));
    }

    public function create()
    {
        return view('finance.credit_debit_adjustments.create', [
            'voteheads'=> \App\Models\Votehead::orderBy('name')->get(),
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
        return view('finance.credit_debit_adjustments.bulk');
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
            'effective_date', // YYYY-MM-DD (optional)
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

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $sheet = Excel::toCollection(null, $request->file('file'))->first();
        if (!$sheet || $sheet->count() === 0) {
            return back()->with('error', 'The uploaded file appears to be empty.');
        }

        $success = 0;
        $failed  = 0;
        $rowsOut = [];

        foreach ($sheet as $idx => $row) {
            // normalize keys to lowercase snake
            $rowArr = [];
            foreach ($row->toArray() as $k => $v) {
                $key = Str::of((string)$k)->lower()->snake()->toString();
                $rowArr[$key] = is_string($v) ? trim($v) : $v;
            }

            $line = $idx + 2; // 1-based + heading row

            // Pull columns with tolerant names
            $adm   = $rowArr['admission_number'] ?? null;
            $vh    = $rowArr['votehead_name'] ?? null;
            $eff   = $rowArr['effective_date'] ?? null;
            $type  = $rowArr['type'] ?? null; // Cr / Dr / credit / debit
            $year  = (int)($rowArr['year'] ?? 0);
            $term  = (int)($rowArr['term'] ?? 0);
            $reason= $rowArr['reason'] ?? null;
            $amt   = $rowArr['amount'] ?? null;

            $err = [];

            if (!$adm)   $err[] = 'admission_number missing';
            if (!$vh)    $err[] = 'votehead_name missing';
            if (!$type)  $err[] = 'type missing';
            if (!$year)  $err[] = 'year missing';
            if (!in_array($term, [1,2,3], true)) $err[] = 'term must be 1,2 or 3';
            if (!$reason)$err[] = 'reason missing';
            if (!is_numeric($amt) || $amt <= 0) $err[] = 'amount must be > 0';

            // Resolve references
            $student = $adm ? Student::where('admission_number', $adm)->first() : null;
            if (!$student) $err[] = "student not found for admission_number '{$adm}'";

            $votehead = $vh ? Votehead::whereRaw('LOWER(name) = ?', [mb_strtolower($vh)])->first() : null;
            if (!$votehead) $err[] = "votehead not found for '{$vh}'";

            // Normalize type
            $typeNorm = null;
            if ($type) {
                $t = mb_strtolower($type);
                if (in_array($t, ['dr','debit']))  $typeNorm = 'debit';
                if (in_array($t, ['cr','credit'])) $typeNorm = 'credit';
            }
            if (!$typeNorm) $err[] = "type '{$type}' must be Cr/Dr or credit/debit";

            if ($err) {
                $failed++;
                $rowsOut[] = ['line' => $line, 'status' => 'Failed', 'message' => implode('; ', $err)];
                continue;
            }

            try {
                JournalService::createAndApply([
                    'student_id'    => $student->id,
                    'votehead_id'   => $votehead->id,
                    'year'          => $year,
                    'term'          => $term,
                    'type'          => $typeNorm,
                    'amount'        => (float)$amt,
                    'reason'        => $reason,
                    'effective_date'=> $eff ?: null,
                ]);

                $success++;
                $rowsOut[] = ['line' => $line, 'status' => 'OK', 'message' => 'Applied'];
            } catch (\Throwable $e) {
                $failed++;
                $rowsOut[] = ['line' => $line, 'status' => 'Failed', 'message' => $e->getMessage()];
            }
        }

        return back()->with([
            'bulk_summary' => [
                'success' => $success,
                'failed'  => $failed,
                'total'   => $success + $failed,
                'rows'    => $rowsOut,
            ],
        ]);
    }
}

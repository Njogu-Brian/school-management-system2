<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OptionalFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::with(['student.classroom','student.stream'])
            ->when($request->filled('year'), fn($qq)=>$qq->where('year',$request->year))
            ->when($request->filled('term'), fn($qq)=>$qq->where('term',$request->term))
            ->when($request->filled('class_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('classroom_id',$request->class_id)))
            ->when($request->filled('stream_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('stream_id',$request->stream_id)))
            ->when($request->filled('votehead_id'), fn($qq)=>$qq->whereHas('items', fn($ii)=>$ii->where('votehead_id',$request->votehead_id)))
            ->latest();

        $invoices = $q->paginate(20)->appends($request->all());

        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $streams    = \App\Models\Academics\Stream::orderBy('name')->get();
        $voteheads  = \App\Models\Votehead::orderBy('name')->get();

        return view('finance.invoices.index', compact('invoices','classrooms','streams','voteheads'));
    }

    public function create()
    {
        $classrooms = \App\Models\Academics\Classroom::all();
        return view('finance.invoices.create', compact('classrooms'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
        ]);

        $structure = FeeStructure::with('charges.votehead')
            ->where('classroom_id', $validated['classroom_id'])
            ->where('year', $validated['year'])
            ->first();

        if (!$structure) {
            return back()->with('error', 'Fee structure not found for selected class and year.');
        }

        $students = Student::where('classroom_id', $validated['classroom_id'])->get();
        $invoicesGenerated = 0;

        DB::transaction(function () use ($students, $structure, $validated, &$invoicesGenerated) {
            foreach ($students as $student) {
                $itemsToInsert = [];

                foreach ($structure->charges->where('term', $validated['term']) as $charge) {
                    $votehead = $charge->votehead;

                    if (!$votehead->is_mandatory) continue;

                    $shouldSkip = false;

                    if ($votehead->charge_type === 'once') {
                        $shouldSkip = InvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
                            ->where('votehead_id', $votehead->id)
                            ->exists();
                    } elseif ($votehead->charge_type === 'once_annually') {
                        $shouldSkip = InvoiceItem::whereHas('invoice', fn($q) =>
                            $q->where('student_id', $student->id)->where('year', $validated['year'])
                        )->where('votehead_id', $votehead->id)->exists();
                    } elseif ($votehead->charge_type === 'per_family') {
                        $shouldSkip = InvoiceItem::whereHas('invoice.student', fn($q) =>
                            $q->where('family_id', $student->family_id)
                        )->where('votehead_id', $votehead->id)->exists();
                    }

                    if ($shouldSkip) continue;

                    $itemsToInsert[] = [
                        'votehead_id' => $votehead->id,
                        'amount' => $charge->amount,
                    ];
                }

                if (count($itemsToInsert) > 0) {
                    $invoice = Invoice::firstOrCreate([
                        'student_id' => $student->id,
                        'term' => $validated['term'],
                        'year' => $validated['year'],
                    ], [
                        'invoice_number' => DocumentNumberService::generate('invoice', 'INV'),
                        'total' => 0,
                    ]);

                    foreach ($itemsToInsert as $item) {
                        InvoiceItem::firstOrCreate([
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $item['votehead_id'],
                        ], [
                            'amount' => $item['amount'],
                        ]);
                    }

                    // Update invoice total
                    $invoice->update(['total' => $invoice->items()->sum('amount')]);
                    $invoicesGenerated++;
                }
            }
        });

        return redirect()->route('finance.invoices.index')->with(
            'success',
            $invoicesGenerated > 0
                ? "$invoicesGenerated invoices generated successfully."
                : "No invoices were generated. All applicable fees already posted."
        );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('student.classroom','student.stream','items.votehead');
        return view('finance.invoices.show', compact('invoice'));
    }

    public function importForm()
    {
        return view('finance.invoices.import');
    }

    public function import(Request $request)
    {
        // Excel import logic goes here (to be implemented separately)
        return back()->with('success', 'Imported successfully.');
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $request->validate(['new_amount'=>'required|numeric|min:0','reason'=>'required|string|max:255']);

        $delta = (float)$request->new_amount - (float)$item->amount;
        if ($delta == 0) {
            return back()->with('success', 'No change.');
        }

        $type = $delta > 0 ? 'debit' : 'credit';

        \App\Services\JournalService::createAndApply([
            'student_id'   => $invoice->student_id,
            'votehead_id'  => $item->votehead_id,
            'year'         => $invoice->year,
            'term'         => $invoice->term,
            'type'         => $type,
            'amount'       => abs($delta),
            'reason'       => $request->reason ?: 'Manual edit',
            'effective_date' => now()->toDateString(),
        ]);

        // Item amount is already updated by service; refresh
        return back()->with('success', 'Invoice item updated.');
    }

    private function filteredInvoicesQuery(Request $request): Builder
    {
        return Invoice::query()
            ->with(['student.classroom','student.stream','items.votehead'])
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->year))
            ->when($request->filled('term'), fn($q) => $q->where('term', $request->term))
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('votehead_id'), fn($q) =>
                $q->whereHas('items', fn($i) => $i->where('votehead_id', request('votehead_id')))
            )
            ->when($request->filled('class_id'), fn($q) =>
                $q->whereHas('student', fn($s) => $s->where('classroom_id', request('class_id')))
            )
            ->when($request->filled('stream_id'), fn($q) =>
                $q->whereHas('student', fn($s) => $s->where('stream_id', request('stream_id')))
            )
            ->orderByDesc('year')->orderByDesc('term')->orderBy('student_id');
    }

    private function branding(): array
    {
        $kv = \Illuminate\Support\Facades\DB::table('settings')->pluck('value','key')->map(fn($v) => trim((string)$v));

        $name    = $kv['school_name']    ?? config('app.name', 'Your School');
        $email   = $kv['school_email']   ?? 'info@example.com';
        $phone   = $kv['school_phone']   ?? '';
        $website = $kv['school_website'] ?? '';
        $address = $kv['school_address'] ?? '';

        // Default to your actual file
        $logoRel = $kv['school_logo_path'] ?? 'images/logo.png';

        $candidates = [ public_path($logoRel), public_path('storage/'.$logoRel), storage_path('app/public/'.$logoRel) ];

        $logoBase64 = null;
        foreach ($candidates as $path) {
            if (!is_file($path)) continue;

            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');

            // If it's a PNG but neither GD nor Imagick is available, skip embedding to avoid DomPDF fatal
            if ($mime === 'image/png' && !extension_loaded('gd') && !extension_loaded('imagick')) {
                $logoBase64 = null;
                break;
            }

            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
            break;
        }

        return compact('name','email','phone','website','address','logoBase64');
    }

    public function printBulk(Request $request)
    {
        $invoices = $this->filteredInvoicesQuery($request)->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices found for the selected criteria.');
        }

        $filters   = $request->only(['year','term','votehead_id','class_id','stream_id','student_id']);
        $branding  = $this->branding();
        $printedBy = optional(auth()->user())->name ?? 'System';
        $printedAt = now();

        $pdf = Pdf::loadView('finance.invoices.pdf.bulk', compact(
            'invoices','filters','branding','printedBy','printedAt'
        ))->setPaper('A4','portrait');

        return $pdf->stream('invoices.pdf');
    }

    public function printSingle(Invoice $invoice)
    {
        $invoice->load(['student.classroom','student.stream','items.votehead']);

        $branding  = $this->branding();
        $printedBy = optional(auth()->user())->name ?? 'System';
        $printedAt = now();

        $pdf = Pdf::loadView('finance.invoices.pdf.single', compact(
            'invoice','branding','printedBy','printedAt'
        ))->setPaper('A4','portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

}

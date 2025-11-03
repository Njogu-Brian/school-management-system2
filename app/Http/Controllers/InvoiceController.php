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
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
        ]);

        $structure = FeeStructure::with('charges.votehead')
            ->where('classroom_id', $request->classrooms_id)
            ->where('year', $request->year)
            ->first();

        if (!$structure) {
            return back()->with('error', 'Fee structure not found for selected class and year.');
        }

        $students = Student::where('classroom_id', $request->classrooms_id)->get();
        $invoicesGenerated = 0;

        DB::transaction(function () use ($students, $structure, $request, &$invoicesGenerated) {
            foreach ($students as $student) {
                $itemsToInsert = [];

                foreach ($structure->charges->where('term', $request->term) as $charge) {
                    $votehead = $charge->votehead;

                    if (!$votehead->is_mandatory) continue;

                    $shouldSkip = false;

                    if ($votehead->charge_type === 'once') {
                        $shouldSkip = InvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
                            ->where('votehead_id', $votehead->id)
                            ->exists();
                    } elseif ($votehead->charge_type === 'once_annually') {
                        $shouldSkip = InvoiceItem::whereHas('invoice', fn($q) =>
                            $q->where('student_id', $student->id)->where('year', $request->year)
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
                        'term' => $request->term,
                        'year' => $request->year,
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
}

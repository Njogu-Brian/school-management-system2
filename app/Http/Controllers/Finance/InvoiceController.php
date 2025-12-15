<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\DocumentNumberService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OptionalFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Clean up any empty invoices (invoices with no items, payments, or allocations)
        // This ensures invoices deleted during reversal are removed from the system
        $this->cleanupEmptyInvoices();
        
        $q = Invoice::with(['student.classroom','student.stream', 'items'])
            ->whereHas('items') // Only show invoices that have at least one item
            ->when($request->filled('year'), fn($qq)=>$qq->where('year',$request->year))
            ->when($request->filled('term'), fn($qq)=>$qq->where('term',$request->term))
            ->when($request->filled('class_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('classroom_id',$request->class_id)))
            ->when($request->filled('stream_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('stream_id',$request->stream_id)))
            ->when($request->filled('votehead_id'), fn($qq)=>$qq->whereHas('items', fn($ii)=>$ii->where('votehead_id',$request->votehead_id)))
            ->latest();

        $invoices = $q->paginate(20)->appends($request->all());

        // Recalculate totals for all invoices to ensure they only include active items
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
        }

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
            ->where('classroom_id', $request->classroom_id)
            ->where('year', $request->year)
            ->first();

        if (!$structure) {
            return back()->with('error', 'Fee structure not found for selected class and year.');
        }

        $students = Student::where('classroom_id', $request->classroom_id)->get();
        $invoicesGenerated = 0;

        DB::transaction(function () use ($students, $structure, $request, &$invoicesGenerated) {
            foreach ($students as $student) {
                $itemsToInsert = [];

                foreach ($structure->charges->where('term', $request->term) as $charge) {
                    $votehead = $charge->votehead;

                    if (!$votehead->is_mandatory) continue;

                    // Use Votehead model's canChargeForStudent method which handles:
                    // - preferred_term for once_annually fees
                    // - once-only fees for new students only
                    if (!$votehead->canChargeForStudent($student, $request->year, $request->term)) {
                        continue;
                    }
                    
                    // Also check preferred_term for once_annually fees
                    if ($votehead->charge_type === 'once_annually' && 
                        $votehead->preferred_term !== null && 
                        $votehead->preferred_term != $request->term) {
                        // Skip if not the preferred term (unless already handled by canChargeForStudent)
                        continue;
                    }

                    // Apply fee concessions
                    $amount = $charge->amount;
                    $concession = \App\Models\FeeConcession::where('student_id', $student->id)
                        ->where(function($q) use ($votehead) {
                            $q->whereNull('votehead_id')
                              ->orWhere('votehead_id', $votehead->id);
                        })
                        ->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where(function($q) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', now());
                        })
                        ->first();

                    if ($concession) {
                        $discount = $concession->calculateDiscount($amount);
                        $amount = $amount - $discount;
                    }

                    $itemsToInsert[] = [
                        'votehead_id' => $votehead->id,
                        'amount' => $amount,
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
        // Show PDF inline in a new tab/window
        return $this->printSingle($invoice);
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
        $request->validate([
            'new_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $newAmount = (float)$request->new_amount;
        $delta = $newAmount - (float)$item->amount;
        
        if ($delta == 0) {
            return back()->with('success', 'No change.');
        }

        try {
            // Use enhanced InvoiceService with automatic credit/debit note creation
            $result = InvoiceService::updateItemAmount(
                $item,
                $newAmount,
                $request->reason,
                $request->notes
            );

            $message = 'Invoice item updated.';
            if ($result['credit_note']) {
                $message .= ' Credit note #' . $result['credit_note']->credit_note_number . ' created.';
            }
            if ($result['debit_note']) {
                $message .= ' Debit note #' . $result['debit_note']->debit_note_number . ' created.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Show invoice edit history (credit/debit notes, audit log)
     */
    public function history(Invoice $invoice)
    {
        $invoice->load([
            'items.creditNotes.issuedBy',
            'items.debitNotes.issuedBy',
            'creditNotes.issuedBy',
            'debitNotes.issuedBy',
            'items.allocations.payment',
        ]);
        
        // Get audit logs for invoice
        $auditLogs = \App\Models\AuditLog::where('auditable_type', Invoice::class)
            ->where('auditable_id', $invoice->id)
            ->orWhere(function ($q) use ($invoice) {
                $q->where('auditable_type', InvoiceItem::class)
                  ->whereIn('auditable_id', $invoice->items->pluck('id'));
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('finance.invoices.history', compact('invoice', 'auditLogs'));
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

    /**
     * Clean up empty invoices (invoices with no items, payments, or allocations)
     * This is called when viewing the invoice list to ensure reversed invoices are removed
     */
    private function cleanupEmptyInvoices(): void
    {
        // Find invoices with no items
        $emptyInvoices = Invoice::doesntHave('items')->get();
        
        foreach ($emptyInvoices as $invoice) {
            // Check if invoice has any payments
            $hasPayments = $invoice->payments()->count() > 0;
            
            // Check if invoice has any payment allocations
            $hasAllocations = \App\Models\PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoice) {
                $q->where('invoice_id', $invoice->id);
            })->count() > 0;
            
            // Check if invoice has any credit notes or debit notes
            $hasCreditNotes = $invoice->creditNotes()->count() > 0;
            $hasDebitNotes = $invoice->debitNotes()->count() > 0;
            
            // If invoice has no items, no payments, no allocations, and no notes, delete it
            if (!$hasPayments && !$hasAllocations && !$hasCreditNotes && !$hasDebitNotes) {
                \Log::info('Cleaning up empty invoice from list view', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $invoice->student_id,
                ]);
                $invoice->delete();
            }
        }
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

        // Filter to show only active items in PDF for each invoice (exclude pending items)
        foreach ($invoices as $invoice) {
            $invoice->load('items.votehead');
            $activeItems = $invoice->items->filter(function($item) {
                return ($item->status ?? 'active') === 'active';
            });
            $invoice->setRelation('items', $activeItems);
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
        
        // Filter to show only active items in PDF (exclude pending items)
        $activeItems = $invoice->items->filter(function($item) {
            return ($item->status ?? 'active') === 'active';
        });
        $invoice->setRelation('items', $activeItems);

        $branding  = $this->branding();
        $printedBy = optional(auth()->user())->name ?? 'System';
        $printedAt = now();

        $pdf = Pdf::loadView('finance.invoices.pdf.single', compact(
            'invoice','branding','printedBy','printedAt'
        ))->setPaper('A4','portrait');

        // Return inline PDF that opens in browser (user can print/download from browser)
        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

}

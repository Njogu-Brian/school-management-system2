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
use App\Services\UniformFeeService;
use App\Services\InvoiceFooterPlaceholderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OptionalFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::with(['student.classroom','student.stream', 'items', 'term', 'academicYear'])
            ->when($request->filled('year'), fn($qq)=>$qq->where('year',$request->year))
            ->when($request->filled('term'), fn($qq)=>$qq->where('term',$request->term))
            ->when($request->filled('student_id'), fn($qq)=>$qq->where('student_id', (int) $request->student_id))
            ->when($request->filled('class_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('classroom_id',$request->class_id)->where('archive', 0)->where('is_alumni', false)))
            ->when($request->filled('stream_id'), fn($qq)=>$qq->whereHas('student', fn($s)=>$s->where('stream_id',$request->stream_id)->where('archive', 0)->where('is_alumni', false)))
            ->when($request->filled('votehead_id'), fn($qq)=>$qq->whereHas('items', fn($ii)=>$ii->where('votehead_id',$request->votehead_id)))
            ->when($request->filled('status'), fn($qq)=>$qq->where('status',$request->status))
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
            ->where('classroom_id', $request->classroom_id)
            ->where('year', $request->year)
            ->first();

        if (!$structure) {
            return back()->with('error', 'Fee structure not found for selected class and year.');
        }

        $students = Student::where('classroom_id', $request->classroom_id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->get();
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
                        ->where('approval_status', 'approved') // Only approved discounts
                        ->where('start_date', '<=', now())
                        ->where(function($q) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', now());
                        })
                        // Match term and year
                        ->where(function($q) use ($request) {
                            $q->whereNull('term')->orWhere('term', $request->term);
                        })
                        ->where(function($q) use ($request) {
                            $q->whereNull('year')->orWhere('year', $request->year);
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
                    // Auto-allocate any unallocated payments for this student
                    InvoiceService::allocateUnallocatedPaymentsForStudent($student->id);
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
        // Apply discounts - force reapply to ensure they're calculated correctly
        $discountService = new \App\Services\DiscountService();
        $invoice = $discountService->applyDiscountsToInvoice($invoice->fresh(['student', 'term', 'academicYear']), true);
        
        // Get applied discounts for display
        $student = $invoice->student;
        $termNumber = $invoice->term;
        if (!$termNumber && $invoice->term_id && $invoice->term) {
            if (preg_match('/\d+/', $invoice->term->name, $matches)) {
                $termNumber = (int)$matches[0];
            }
        }
        $year = $invoice->year;
        if (!$year && $invoice->academic_year_id && $invoice->academicYear) {
            $year = $invoice->academicYear->year;
        }
        
        $appliedDiscounts = \App\Models\FeeConcession::where(function ($q) use ($student) {
                $q->where('student_id', $student->id)
                  ->orWhere(function ($sq) use ($student) {
                      if ($student->family_id) {
                          $sq->where('family_id', $student->family_id)
                            ->where('scope', 'family');
                      }
                  });
            })
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->when($termNumber, function($q) use ($termNumber) {
                $q->where(function($q) use ($termNumber) {
                    $q->whereNull('term')->orWhere('term', $termNumber);
                });
            })
            ->when($year, function($q) use ($year, $invoice) {
                $q->where(function($q) use ($year, $invoice) {
                    $q->whereNull('year')->orWhere('year', $year);
                    if ($invoice->academic_year_id) {
                        $q->orWhere('academic_year_id', $invoice->academic_year_id);
                    }
                });
            })
            ->with(['discountTemplate', 'votehead'])
            ->get();
        
        $invoice->load([
            'student.classroom',
            'student.stream',
            'term',
            'academicYear',
            'items.votehead',
            'items.allocations.payment',
            'creditNotes.issuedBy',
            'creditNotes.invoiceItem',
            'debitNotes.issuedBy',
            'debitNotes.invoiceItem',
            'payments.paymentMethod'
        ]);

        // Payments that have at least one allocation to an item on this invoice
        // (matches history page logic; $invoice->payments uses invoice_id and can miss allocated payments)
        $paymentsForHistory = \App\Models\Payment::whereHas('allocations', function ($q) use ($invoice) {
            $q->whereHas('invoiceItem', fn ($q2) => $q2->where('invoice_id', $invoice->id));
        })
            ->with(['paymentMethod', 'allocations.invoiceItem'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('finance.invoices.show', compact('invoice', 'appliedDiscounts', 'paymentsForHistory'));
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
        // Always treat as JSON if X-Requested-With header is present
        $isAjax = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
        $isManagedCustomItem = UniformFeeService::isManagedCustomItem($item);
        
        $request->validate([
            'new_amount' => 'required|numeric|min:0',
            'votehead_name' => $isManagedCustomItem ? 'required|string|max:255' : 'nullable|string|max:255',
            'reason' => $isManagedCustomItem ? 'nullable|string|max:255' : 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $newAmount = (float)$request->new_amount;
        $delta = $newAmount - (float)$item->amount;
        
        if ($delta == 0) {
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => 'No change.']);
            }
            return back()->with('success', 'No change.');
        }

        // Custom managed items: adjust amount + votehead directly, no credit/debit notes
        if ($isManagedCustomItem) {
            try {
                UniformFeeService::updateManagedItem(
                    $item,
                    (string) $request->input('votehead_name'),
                    $newAmount
                );
                $message = 'Custom invoice item updated.';
                if ($isAjax) {
                    return response()->json(['success' => true, 'message' => $message, 'item' => $item->fresh()]);
                }
                return back()->with('success', $message);
            } catch (\Exception $e) {
                \Log::error('Custom invoice item update failed: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'exception' => $e
                ]);
                if ($isAjax) {
                    return response()->json(['success' => false, 'error' => 'Update failed: ' . $e->getMessage()], 422);
                }
                return back()->with('error', 'Update failed: ' . $e->getMessage());
            }
        }

        try {
            // Use enhanced InvoiceService with automatic credit/debit note creation
            $result = InvoiceService::updateItemAmount(
                $item,
                $newAmount,
                $request->reason,
                $request->notes ?? null
            );

            $message = 'Invoice item updated.';
            if ($result['credit_note']) {
                $message .= ' Credit note #' . ($result['credit_note']->credit_note_number ?? $result['credit_note']->id) . ' created.';
            }
            if ($result['debit_note']) {
                $message .= ' Debit note #' . ($result['debit_note']->debit_note_number ?? $result['debit_note']->id) . ' created.';
            }

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'item' => $result['item'],
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Invoice item update failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'exception' => $e
            ]);
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Update failed: ' . $e->getMessage()
                ], 422);
            }
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Add or update custom line item on this invoice (shortcut; no credit/debit notes).
     */
    public function storeCustomItem(Request $request, Invoice $invoice)
    {
        $request->validate([
            'votehead_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
        try {
            UniformFeeService::addCustomItem(
                $invoice,
                (string) $request->input('votehead_name'),
                (float) $request->amount
            );
            return back()->with('success', 'Custom item added/updated. It will appear on the invoice, payments and statement.');
        } catch (\Exception $e) {
            \Log::error('Custom invoice item store failed: ' . $e->getMessage(), ['invoice_id' => $invoice->id]);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove legacy uniform line from this invoice.
     */
    public function removeLegacyUniformItem(Invoice $invoice)
    {
        try {
            UniformFeeService::removeLegacyUniformItem($invoice);
            return back()->with('success', 'Uniform line removed from invoice.');
        } catch (\Exception $e) {
            \Log::error('Uniform remove failed: ' . $e->getMessage(), ['invoice_id' => $invoice->id]);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove a managed custom item from this invoice.
     */
    public function removeCustomItem(Invoice $invoice, InvoiceItem $item)
    {
        if ((int) $item->invoice_id !== (int) $invoice->id) {
            return back()->with('error', 'Invalid invoice item for this invoice.');
        }

        if (!UniformFeeService::isManagedCustomItem($item)) {
            return back()->with('error', 'Only custom invoice items can be removed here.');
        }

        try {
            UniformFeeService::removeManagedItem($item);
            return back()->with('success', 'Custom invoice item removed from invoice.');
        } catch (\Exception $e) {
            \Log::error('Custom item remove failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
            ]);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * @deprecated Backward-compatible alias for old method naming.
     */
    public function storeUniform(Request $request, Invoice $invoice)
    {
        return $this->storeCustomItem($request, $invoice);
    }

    /**
     * @deprecated Backward-compatible alias for old method naming.
     */
    public function removeUniform(Invoice $invoice)
    {
        return $this->removeLegacyUniformItem($invoice);
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
            'items.allocations.payment.paymentMethod',
        ]);

        // Payments that have at least one allocation to an item on this invoice (so all payments for this invoice show in history)
        $paymentsForHistory = \App\Models\Payment::whereHas('allocations', function ($q) use ($invoice) {
            $q->whereHas('invoiceItem', fn ($q2) => $q2->where('invoice_id', $invoice->id));
        })
            ->with('paymentMethod')
            ->orderBy('payment_date', 'desc')
            ->get();

        // Get audit logs for invoice
        $auditLogs = \App\Models\AuditLog::where('auditable_type', Invoice::class)
            ->where('auditable_id', $invoice->id)
            ->orWhere(function ($q) use ($invoice) {
                $q->where('auditable_type', InvoiceItem::class)
                  ->whereIn('auditable_id', $invoice->items->pluck('id'));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('finance.invoices.history', compact('invoice', 'auditLogs', 'paymentsForHistory'));
    }

    private function filteredInvoicesQuery(Request $request): Builder
    {
        return Invoice::query()
            ->with(['student.classroom', 'student.stream', 'student.family', 'items.votehead', 'term', 'academicYear'])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '<>', 'reversed');
            })
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->when($request->filled('term'), fn ($q) => $q->where('term', $request->term))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('votehead_id'), fn ($q) =>
                $q->whereHas('items', fn ($i) => $i->where('votehead_id', request('votehead_id')))
            )
            ->when($request->filled('class_id'), fn ($q) =>
                $q->whereHas('student', fn ($s) => $s->where('classroom_id', request('class_id'))->where('archive', 0)->where('is_alumni', false))
            )
            ->when($request->filled('stream_id'), fn ($q) =>
                $q->whereHas('student', fn ($s) => $s->where('stream_id', request('stream_id'))->where('archive', 0)->where('is_alumni', false))
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('year')->orderByDesc('term')->orderBy('student_id');
    }

    /**
     * Class → stream → first name (for bulk PDF/CSV).
     */
    private function sortInvoicesForExport(Collection $invoices): Collection
    {
        return $invoices->sortBy(function (Invoice $invoice) {
            $s = $invoice->student;
            if (!$s) {
                return '~~~|~~~|~~~';
            }
            $class = strtolower(trim(optional($s->classroom)->name ?? '~~~'));
            $stream = strtolower(trim(optional($s->stream)->name ?? ''));
            $first = strtolower(trim($s->first_name ?? ''));

            return sprintf('%s|%s|%s', $class, $stream, $first);
        })->values();
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'term' => 'required|in:1,2,3',
            'votehead_id' => 'nullable|integer',
            'class_id' => 'nullable|integer',
            'stream_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'status' => 'nullable|in:unpaid,partial,paid',
        ]);

        $invoices = $this->sortInvoicesForExport($this->filteredInvoicesQuery($request)->get());
        if ($invoices->isEmpty()) {
            return redirect()
                ->route('finance.invoices.index', $request->only(['year', 'term', 'votehead_id', 'class_id', 'stream_id', 'student_id', 'status']))
                ->with('error', 'No invoices match the selected criteria for export.');
        }

        $filename = 'invoices-' . $request->year . '-T' . $request->term;
        if ($request->filled('class_id')) {
            $filename .= '-class-' . $request->class_id;
        }
        $filename .= '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Invoice #',
                'Student full name',
                'First name',
                'Admission #',
                'Class',
                'Stream',
                'Year',
                'Term',
                'Total',
                'Paid',
                'Balance',
                'Status',
            ]);
            foreach ($invoices as $invoice) {
                $invoice->recalculate();
                $s = $invoice->student;
                fputcsv($out, [
                    $invoice->invoice_number,
                    $s->full_name ?? '',
                    $s->first_name ?? '',
                    $s->admission_number ?? '',
                    optional($s->classroom)->name ?? '',
                    optional($s->stream)->name ?? '',
                    $invoice->year,
                    $invoice->term,
                    number_format((float) $invoice->total, 2, '.', ''),
                    number_format((float) $invoice->paid_amount, 2, '.', ''),
                    number_format((float) $invoice->balance, 2, '.', ''),
                    $invoice->status ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Backfill prior-term balances onto existing Term 2/3 invoices (same as ensure() would do).
     */
    public function carryForwardPriorTermBalances(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2026',
            'term' => 'required|in:2,3',
            'class_id' => 'nullable|integer',
        ]);

        $q = Invoice::query()
            ->where('year', (int) $request->year)
            ->where('term', (int) $request->term)
            ->where(function ($q2) {
                $q2->whereNull('status')->orWhere('status', '<>', 'reversed');
            })
            ->with('student');

        if ($request->filled('class_id')) {
            $q->whereHas('student', fn ($s) => $s->where('classroom_id', (int) $request->class_id));
        }

        $applied = 0;
        foreach ($q->get() as $invoice) {
            if (InvoiceService::applyPriorTermCarryForwardIfNeeded($invoice)) {
                $applied++;
            }
        }

        return back()->with(
            'success',
            "Prior-term carry-forward completed. New arrears lines added on {$applied} invoice(s). " .
            'Invoices with nothing to move or already processed were skipped.'
        );
    }

    private function branding(): array
    {
        $kv = \Illuminate\Support\Facades\DB::table('settings')->pluck('value','key')->map(fn($v) => trim((string)$v));

        $name    = $kv['school_name']    ?? config('app.name', 'Your School');
        $email   = $kv['school_email']   ?? 'info@example.com';
        $phone   = $kv['school_phone']   ?? '';
        $website = $kv['school_website'] ?? '';
        $address = $kv['school_address'] ?? '';

        // Try school_logo first (stored as filename in public/images/)
        // Then try school_logo_path (full path)
        $logoFilename = $kv['school_logo'] ?? null;
        $logoPathSetting = $kv['school_logo_path'] ?? null;
        
        $candidates = [];
        
        // If school_logo is set, check public/images/ first
        if ($logoFilename) {
            $candidates[] = public_path('images/' . $logoFilename);
        }
        
        // If school_logo_path is set, use it directly
        if ($logoPathSetting) {
            $candidates[] = public_path($logoPathSetting);
            $candidates[] = public_path('storage/' . $logoPathSetting);
            $candidates[] = storage_path('app/public/' . $logoPathSetting);
        }
        
        // Fallback to default
        if (empty($candidates)) {
            $candidates[] = public_path('images/logo.png');
        }

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
        $invoices = $this->sortInvoicesForExport($this->filteredInvoicesQuery($request)->get());
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices found for the selected criteria.');
        }

        $filters = $request->only(['year', 'term', 'votehead_id', 'class_id', 'stream_id', 'student_id']);
        $branding = $this->branding();
        $printedBy = optional(auth()->user())->name ?? 'System';
        $printedAt = now();
        $invoiceHeader = \App\Models\Setting::get('invoice_header', '');
        $invoiceFooterTemplate = \App\Models\Setting::get('invoice_footer', '');

        $pdf = Pdf::loadView('finance.invoices.pdf.bulk', compact(
            'invoices',
            'filters',
            'branding',
            'printedBy',
            'printedAt',
            'invoiceHeader',
            'invoiceFooterTemplate'
        ))->setPaper('A4', 'portrait');

        return $pdf->stream('invoices.pdf');
    }

    public function printSingle(Invoice $invoice)
    {
        $invoice->load(['student.classroom', 'student.stream', 'student.family', 'items.votehead', 'term', 'academicYear']);

        $branding  = $this->branding();
        $printedBy = optional(auth()->user())->name ?? 'System';
        $printedAt = now();
        $invoiceHeader = InvoiceFooterPlaceholderService::replace(
            \App\Models\Setting::get('invoice_header', ''),
            $invoice
        );
        $invoiceFooter = InvoiceFooterPlaceholderService::replace(
            \App\Models\Setting::get('invoice_footer', ''),
            $invoice
        );

        $pdf = Pdf::loadView('finance.invoices.pdf.single', compact(
            'invoice',
            'branding',
            'printedBy',
            'printedAt',
            'invoiceHeader',
            'invoiceFooter'
        ))->setPaper('A4', 'portrait');

        $filename = 'invoice-' . str_replace(['/', '\\'], '-', $invoice->invoice_number) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Public view of invoice using hashed ID (no authentication required)
     * This route only accepts hashed_id (10 chars), not numeric IDs
     */
    public function publicView(string $hash)
    {
        // Explicitly find by hashed_id to prevent numeric ID access
        $invoice = Invoice::where('hashed_id', $hash)
            ->whereRaw('LENGTH(hashed_id) = 10') // Ensure it's exactly 10 chars
            ->firstOrFail();
        
        // Load relationships
        $invoice->load([
            'student.parent',
            'student.classroom',
            'student.stream',
            'term',
            'academicYear',
            'items.votehead',
            'items.allocations.payment',
        ]);
        
        // Get school settings
        $schoolSettings = \App\Services\ReceiptService::getSchoolSettings();
        
        return view('finance.invoices.public', compact('invoice', 'schoolSettings'));
    }

}

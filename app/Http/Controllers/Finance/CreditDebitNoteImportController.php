<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNoteImport;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

class CreditDebitNoteImportController extends Controller
{
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
            'votehead_id' => 'required|exists:voteheads,id',
        ]);

        [$year, $term] = $this->resolveYearAndTerm($request->year, $request->term);
        $votehead = Votehead::findOrFail($request->votehead_id);

        $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];

        if (empty($sheet)) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $headerRow = array_shift($sheet);
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::slug(Str::lower(trim((string) $header)), '_');
        }

        $preview = [];
        $totalCredit = 0;
        $totalDebit = 0;

        foreach ($sheet as $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }

            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $student = $admission
                ? Student::where('admission_number', $admission)->first()
                : null;

            if (!$student) {
                $studentName = trim((string) ($assoc['name'] ?? $assoc['student_name'] ?? ''));
                if ($studentName !== '') {
                    $student = Student::whereRaw('LOWER(CONCAT(first_name," ",last_name)) = ?', [Str::lower($studentName)])->first();
                }
            }

            $creditAmount = $assoc['cr'] ?? $assoc['credit'] ?? $assoc['credit_amount'] ?? null;
            $creditAmount = is_numeric($creditAmount) ? (float) $creditAmount : null;
            if ($creditAmount && $creditAmount <= 0) {
                $creditAmount = null;
            }

            $debitAmount = $assoc['dr'] ?? $assoc['debit'] ?? $assoc['debit_amount'] ?? null;
            $debitAmount = is_numeric($debitAmount) ? (float) $debitAmount : null;
            if ($debitAmount && $debitAmount <= 0) {
                $debitAmount = null;
            }

            $status = 'ok';
            $message = null;
            if (!$student) {
                $status = 'missing_student';
                $message = 'Student not found';
            } elseif (!$creditAmount && !$debitAmount) {
                $status = 'missing_amounts';
                $message = 'Both credit and debit amounts are missing';
            } elseif ($creditAmount && $debitAmount) {
                $status = 'both_amounts';
                $message = 'Cannot have both credit and debit amounts';
            }

            if ($status === 'ok') {
                if ($creditAmount) {
                    $totalCredit += $creditAmount;
                }
                if ($debitAmount) {
                    $totalDebit += $debitAmount;
                }
            }

            $preview[] = [
                'student_id' => $student?->id,
                'student_name' => $student?->full_name ?? ($assoc['name'] ?? $assoc['student_name'] ?? null),
                'admission_number' => $admission ?: ($student?->admission_number ?? null),
                'credit_amount' => $creditAmount,
                'debit_amount' => $debitAmount,
                'status' => $status,
                'message' => $message,
            ];
        }

        return view('finance.credit_debit_notes.import_preview', [
            'preview' => $preview,
            'votehead' => $votehead,
            'year' => $year,
            'term' => $term,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
        ]);
    }

    public function importCommit(Request $request)
    {
        $request->validate([
            'rows' => 'required|array',
            'year' => 'required|integer',
            'term' => 'required|integer|in:1,2,3',
            'votehead_id' => 'required|exists:voteheads,id',
        ]);

        [$year, $term, $academicYearId] = $this->resolveYearAndTerm($request->year, $request->term);
        $votehead = Votehead::findOrFail($request->votehead_id);

        // Create import batch
        $importBatch = CreditDebitNoteImport::create([
            'year' => $year,
            'term' => $term,
            'votehead_id' => $votehead->id,
            'academic_year_id' => $academicYearId,
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'is_reversed' => false,
        ]);

        $createdOrUpdated = 0;
        $totalCredit = 0;
        $totalDebit = 0;

        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            if (!$row || ($row['status'] ?? '') !== 'ok' || empty($row['student_id'])) {
                continue;
            }

            $studentId = $row['student_id'];
            $creditAmount = $row['credit_amount'] ?? null;
            $debitAmount = $row['debit_amount'] ?? null;

            try {
                DB::transaction(function () use ($studentId, $votehead, $year, $term, $creditAmount, $debitAmount, $importBatch, &$totalCredit, &$totalDebit) {
                    // Ensure invoice exists
                    $invoice = InvoiceService::ensure($studentId, $year, $term);

                    // Get or create invoice item
                    $invoiceItem = InvoiceItem::firstOrNew([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $votehead->id,
                    ]);

                    $oldAmount = $invoiceItem->exists ? (float) $invoiceItem->amount : 0;

                    if ($creditAmount && $creditAmount > 0) {
                        // Apply credit (reduce amount)
                        $newAmount = max(0, $oldAmount - $creditAmount);
                        $invoiceItem->amount = $newAmount;
                        $invoiceItem->status = 'active';
                        $invoiceItem->source = 'credit_note_import';
                        if (!$invoiceItem->original_amount) {
                            $invoiceItem->original_amount = $oldAmount;
                        }
                        $invoiceItem->save();

                        // Create credit note record
                        CreditNote::create([
                            'invoice_id' => $invoice->id,
                            'invoice_item_id' => $invoiceItem->id,
                            'amount' => $creditAmount,
                            'reason' => 'Imported credit note',
                            'notes' => "Imported via batch #{$importBatch->id}",
                            'issued_by' => auth()->id(),
                            'issued_at' => now(),
                        ]);

                        $totalCredit += $creditAmount;
                    } elseif ($debitAmount && $debitAmount > 0) {
                        // Apply debit (increase amount)
                        $newAmount = $oldAmount + $debitAmount;
                        $invoiceItem->amount = $newAmount;
                        $invoiceItem->status = 'active';
                        $invoiceItem->source = 'debit_note_import';
                        if (!$invoiceItem->original_amount) {
                            $invoiceItem->original_amount = $oldAmount ?: $debitAmount;
                        }
                        $invoiceItem->save();

                        // Create debit note record
                        DebitNote::create([
                            'invoice_id' => $invoice->id,
                            'invoice_item_id' => $invoiceItem->id,
                            'amount' => $debitAmount,
                            'reason' => 'Imported debit note',
                            'notes' => "Imported via batch #{$importBatch->id}",
                            'issued_by' => auth()->id(),
                            'issued_at' => now(),
                        ]);

                        $totalDebit += $debitAmount;
                    }

                    // Recalculate invoice
                    InvoiceService::recalc($invoice);
                });

                $createdOrUpdated++;
            } catch (\Throwable $e) {
                Log::warning('Credit/Debit note import failed', [
                    'student_id' => $studentId,
                    'votehead_id' => $votehead->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Update import batch
        $importBatch->update([
            'notes_imported_count' => $createdOrUpdated,
            'total_credit_amount' => $totalCredit,
            'total_debit_amount' => $totalDebit,
        ]);

        return redirect()
            ->route('finance.invoices.index')
            ->with('success', "{$createdOrUpdated} credit/debit note(s) imported for {$votehead->name}, Term {$term}, {$year}.")
            ->with('import_batch_id', $importBatch->id);
    }

    public function template()
    {
        $headers = ['Name', 'Admission Number', 'CR (Credit)', 'DR (Debit)'];
        $sample = [
            ['John Doe', 'RKS001', 1000, ''],
            ['Jane Smith', 'RKS002', '', 2000],
        ];

        return Excel::download(new ArrayExport($sample, $headers), 'credit_debit_notes_template.xlsx');
    }

    public function reverse(CreditDebitNoteImport $import)
    {
        try {
            if ($import->is_reversed) {
                return back()->with('error', 'This import has already been reversed.');
            }

            $result = $this->reverseImport($import);

            return redirect()
                ->route('finance.invoices.index')
                ->with('success', "Credit/Debit note import #{$import->id} reversed successfully. {$result['notes_deleted']} notes and {$result['items_updated']} invoice items reverted.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reverse import: ' . $e->getMessage());
        }
    }

    private function reverseImport(CreditDebitNoteImport $import): array
    {
        return DB::transaction(function () use ($import) {
            // Find invoices for this year/term
            $invoices = Invoice::where('year', $import->year)
                ->where('term', $import->term)
                ->pluck('id');

            // Find invoice items for this votehead
            $invoiceItems = InvoiceItem::whereIn('invoice_id', $invoices)
                ->where('votehead_id', $import->votehead_id)
                ->get();

            $notesDeleted = 0;
            $itemsUpdated = 0;

            foreach ($invoiceItems as $item) {
                // Find credit notes created during import time window
                $creditNotes = CreditNote::where('invoice_item_id', $item->id)
                    ->where('created_at', '>=', $import->imported_at->startOfDay())
                    ->where('created_at', '<=', $import->imported_at->endOfDay())
                    ->where('notes', 'like', "%batch #{$import->id}%")
                    ->get();

                // Find debit notes created during import time window
                $debitNotes = DebitNote::where('invoice_item_id', $item->id)
                    ->where('created_at', '>=', $import->imported_at->startOfDay())
                    ->where('created_at', '<=', $import->imported_at->endOfDay())
                    ->where('notes', 'like', "%batch #{$import->id}%")
                    ->get();

                $itemUpdated = false;
                $originalAmount = $item->amount;

                foreach ($creditNotes as $note) {
                    // Reverse credit: add back the amount
                    $item->amount = $item->amount + $note->amount;
                    $note->delete();
                    $notesDeleted++;
                    $itemUpdated = true;
                }

                foreach ($debitNotes as $note) {
                    // Reverse debit: subtract the amount
                    $item->amount = max(0, $item->amount - $note->amount);
                    $note->delete();
                    $notesDeleted++;
                    $itemUpdated = true;
                }

                if ($itemUpdated) {
                    $item->save();
                    InvoiceService::recalc($item->invoice);
                    $itemsUpdated++;
                }
            }

            // Mark import as reversed
            $import->update([
                'is_reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);

            return [
                'notes_deleted' => $notesDeleted,
                'items_updated' => $itemsUpdated,
            ];
        });
    }

    private function resolveYearAndTerm(?int $year = null, ?int $term = null): array
    {
        $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $yearValue = $year ?? ($academicYear?->year ?? (int) date('Y'));

        $termModel = \App\Models\Term::where('is_current', true)->first();
        $termValue = $term ?? ($termModel ? (int) preg_replace('/[^0-9]/', '', $termModel->name) : 1);
        $termValue = $termValue ?: 1;

        return [$yearValue, $termValue, $academicYear?->id];
    }
}


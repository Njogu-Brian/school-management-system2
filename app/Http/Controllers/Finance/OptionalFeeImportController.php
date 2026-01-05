<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\OptionalFee;
use App\Models\OptionalFeeImport;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

class OptionalFeeImportController extends Controller
{
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
        ]);

        [$year, $term] = $this->resolveYearAndTerm($request->year, $request->term);

        $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];

        if (empty($sheet)) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $headerRow = array_shift($sheet);
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::slug(Str::lower(trim((string) $header)), '_');
        }

        $voteheads = Votehead::where('is_mandatory', false)->get()->keyBy(function($v) {
            return Str::lower($v->name);
        });
        
        $preview = [];
        $totalAmount = 0;
        $missingVoteheads = [];

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

            $voteheadName = trim((string) ($assoc['votehead_name'] ?? $assoc['votehead'] ?? ''));
            $votehead = null;
            if ($voteheadName) {
                $votehead = $voteheads->get(Str::lower($voteheadName));
                if (!$votehead) {
                    $missingVoteheads[] = $voteheadName;
                }
            }

            $amountField = $assoc['amount'] ?? null;
            $amount = is_numeric($amountField) ? (float) $amountField : null;

            $status = 'ok';
            $message = null;
            if (!$student) {
                $status = 'missing_student';
                $message = 'Student not found';
            } elseif (!$votehead) {
                $status = 'missing_votehead';
                $message = 'Votehead not found or not optional';
            } elseif ($amount === null || $amount <= 0) {
                $status = 'invalid_amount';
                $message = 'Amount is missing or invalid';
            }

            if ($status === 'ok') {
                $totalAmount += $amount;
            }

            $preview[] = [
                'student_id' => $student?->id,
                'student_name' => $student?->full_name ?? ($assoc['name'] ?? $assoc['student_name'] ?? null),
                'admission_number' => $admission ?: ($student?->admission_number ?? null),
                'votehead_id' => $votehead?->id,
                'votehead_name' => $voteheadName,
                'amount' => $amount,
                'status' => $status,
                'message' => $message,
            ];
        }

        $missingVoteheads = collect($missingVoteheads)->filter()->unique()->values();

        return view('finance.optional_fees.import_preview', [
            'preview' => $preview,
            'missingVoteheads' => $missingVoteheads,
            'year' => $year,
            'term' => $term,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function importCommit(Request $request)
    {
        $request->validate([
            'rows' => 'required|array',
            'year' => 'required|integer',
            'term' => 'required|integer|in:1,2,3',
        ]);

        [$year, $term, $academicYearId] = $this->resolveYearAndTerm($request->year, $request->term);

        // Create import batch
        $importBatch = OptionalFeeImport::create([
            'year' => $year,
            'term' => $term,
            'academic_year_id' => $academicYearId,
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'is_reversed' => false,
        ]);

        $createdOrUpdated = 0;
        $totalAmount = 0;

        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            if (!$row || ($row['status'] ?? '') !== 'ok' || empty($row['student_id']) || empty($row['votehead_id'])) {
                continue;
            }

            $studentId = $row['student_id'];
            $voteheadId = $row['votehead_id'];
            $amount = (float) ($row['amount'] ?? 0);

            try {
                DB::transaction(function () use ($studentId, $voteheadId, $year, $term, $academicYearId, $amount, $importBatch) {
                    // Create or update optional fee
                    OptionalFee::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'votehead_id' => $voteheadId,
                            'year' => $year,
                            'term' => $term,
                        ],
                        [
                            'academic_year_id' => $academicYearId,
                            'amount' => $amount,
                            'status' => 'billed',
                            'assigned_by' => auth()->id(),
                            'assigned_at' => now(),
                        ]
                    );

                    // Ensure invoice exists
                    $invoice = InvoiceService::ensure($studentId, $year, $term);

                    // Create or update invoice item
                    $invoiceItem = \App\Models\InvoiceItem::updateOrCreate(
                        [
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $voteheadId,
                            'source' => 'optional_fee',
                        ],
                        [
                            'amount' => $amount,
                            'status' => 'active',
                            'posted_at' => now(),
                        ]
                    );

                    // Store original amount if not set
                    if (!$invoiceItem->original_amount) {
                        $invoiceItem->update(['original_amount' => $amount]);
                    }

                    // Recalculate invoice
                    InvoiceService::recalc($invoice);
                });

                $createdOrUpdated++;
                $totalAmount += $amount;
            } catch (\Throwable $e) {
                Log::warning('Optional fee import failed', [
                    'student_id' => $studentId,
                    'votehead_id' => $voteheadId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Update import batch
        $importBatch->update([
            'fees_imported_count' => $createdOrUpdated,
            'total_amount' => $totalAmount,
        ]);

        return redirect()
            ->route('finance.optional_fees.index')
            ->with('success', "{$createdOrUpdated} optional fee(s) imported for Term {$term}, {$year}.")
            ->with('import_batch_id', $importBatch->id);
    }

    public function template()
    {
        $headers = ['Admission Number', 'Name', 'Votehead Name', 'Amount'];
        $sample = [
            ['RKS001', 'John Doe', 'Sports Fee', 5000],
            ['RKS002', 'Jane Smith', 'Library Fee', 3000],
        ];

        return Excel::download(new ArrayExport($sample, $headers), 'optional_fees_template.xlsx');
    }

    public function reverse(OptionalFeeImport $import)
    {
        try {
            if ($import->is_reversed) {
                return back()->with('error', 'This import has already been reversed.');
            }

            $result = $this->reverseImport($import);

            return redirect()
                ->route('finance.optional_fees.index')
                ->with('success', "Optional fee import #{$import->id} reversed successfully. {$result['fees_deleted']} optional fees and {$result['items_deleted']} invoice items deleted.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reverse import: ' . $e->getMessage());
        }
    }

    private function reverseImport(OptionalFeeImport $import): array
    {
        return DB::transaction(function () use ($import) {
            // Find all optional fees created by this import
            $optionalFees = OptionalFee::where('year', $import->year)
                ->where('term', $import->term)
                ->where('assigned_at', '>=', $import->imported_at->startOfDay())
                ->where('assigned_at', '<=', $import->imported_at->endOfDay())
                ->get();

            $feesDeleted = 0;
            $itemsDeleted = 0;

            foreach ($optionalFees as $fee) {
                // Delete invoice item
                $invoice = \App\Models\Invoice::where('student_id', $fee->student_id)
                    ->where('year', $import->year)
                    ->where('term', $import->term)
                    ->first();

                if ($invoice) {
                    $deleted = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                        ->where('votehead_id', $fee->votehead_id)
                        ->where('source', 'optional_fee')
                        ->delete();
                    
                    $itemsDeleted += $deleted;

                    // Recalculate invoice
                    InvoiceService::recalc($invoice);
                }

                // Delete optional fee
                $fee->delete();
                $feesDeleted++;
            }

            // Mark import as reversed
            $import->update([
                'is_reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);

            return [
                'fees_deleted' => $feesDeleted,
                'items_deleted' => $itemsDeleted,
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


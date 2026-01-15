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
        
        // Get all optional voteheads and create a mapping by name (case-insensitive)
        $voteheads = Votehead::where('is_mandatory', false)->get()->keyBy(function($v) {
            return Str::lower($v->name);
        });
        
        // Normalize header row - expect: Name, Admission Number, then votehead names
        $normalizedHeaders = [];
        foreach ($headerRow as $index => $header) {
            $normalizedHeaders[$index] = trim((string) $header);
        }
        
        // Identify votehead columns (skip first two: Name and Admission Number)
        $voteheadColumns = [];
        for ($i = 2; $i < count($normalizedHeaders); $i++) {
            $headerName = $normalizedHeaders[$i];
            if (empty($headerName)) {
                continue;
            }
            
            // Try to find matching votehead
            $votehead = $voteheads->get(Str::lower($headerName));
            if ($votehead) {
                $voteheadColumns[$i] = [
                    'votehead_id' => $votehead->id,
                    'votehead_name' => $votehead->name,
                    'header_name' => $headerName,
                ];
            } else {
                $voteheadColumns[$i] = [
                    'votehead_id' => null,
                    'votehead_name' => null,
                    'header_name' => $headerName,
                ];
            }
        }
        
        $preview = [];
        $totalAmount = 0;
        $missingVoteheads = [];
        $studentsToMatch = []; // For students with missing admission numbers

        // Process each row (each row is a student)
        foreach ($sheet as $rowIndex => $row) {
            // Get student name (first column)
            $studentName = trim((string) ($row[0] ?? ''));
            
            // Get admission number (second column)
            $admission = trim((string) ($row[1] ?? ''));
            
            // Find student
            $student = null;
            $matchedStudents = [];
            
            if ($admission) {
                $student = Student::where('admission_number', $admission)->first();
            }
            
            // If no student found by admission, try to match by name
            if (!$student && $studentName) {
                // Try exact match first
                $student = Student::whereRaw('LOWER(CONCAT(first_name," ",last_name)) = ?', [Str::lower($studentName)])->first();
                
                // If still not found and no admission number, find potential matches
                if (!$student && !$admission) {
                    $nameParts = explode(' ', $studentName, 2);
                    if (count($nameParts) >= 2) {
                        $matchedStudents = Student::whereRaw('LOWER(first_name) = ?', [Str::lower($nameParts[0])])
                            ->whereRaw('LOWER(last_name) LIKE ?', [Str::lower($nameParts[1] ?? '') . '%'])
                            ->limit(10)
                            ->get();
                    } else {
                        $matchedStudents = Student::whereRaw('LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ?', [
                            Str::lower($studentName) . '%',
                            Str::lower($studentName) . '%'
                        ])
                        ->limit(10)
                        ->get();
                    }
                    
                    if ($matchedStudents->count() === 1) {
                        $student = $matchedStudents->first();
                    } elseif ($matchedStudents->count() > 1) {
                        // Store for user to select
                        $studentsToMatch[] = [
                            'row_index' => $rowIndex,
                            'student_name' => $studentName,
                            'admission_number' => $admission,
                            'matched_students' => $matchedStudents,
                        ];
                    }
                }
            }
            
            // Process each votehead column (starting from column 2)
            foreach ($voteheadColumns as $colIndex => $voteheadInfo) {
                $amountValue = $row[$colIndex] ?? null;
                $amount = null;
                
                // Try to parse amount
                if ($amountValue !== null && $amountValue !== '') {
                    // Remove any formatting and convert to float
                    $cleaned = preg_replace('/[^\d.-]/', '', (string) $amountValue);
                    $amount = is_numeric($cleaned) ? (float) $cleaned : null;
                }
                
                // Skip if no amount or votehead not found
                if ($amount === null || $amount <= 0) {
                    continue; // Skip empty amounts
                }
                
                if (!$voteheadInfo['votehead_id']) {
                    $missingVoteheads[] = $voteheadInfo['header_name'];
                    continue;
                }
                
                $status = 'ok';
                $message = null;
                $changeType = 'new'; // new, existing, changed, removed
                $existingAmount = null;
                $existingBilling = null;
                
                if (!$student) {
                    if (!empty($matchedStudents)) {
                        $status = 'needs_matching';
                        $message = 'Multiple students found - please select';
                    } else {
                        $status = 'missing_student';
                        $message = 'Student not found';
                    }
                } else {
                    // Check if this optional fee already exists
                    $existingBilling = OptionalFee::where('student_id', $student->id)
                        ->where('votehead_id', $voteheadInfo['votehead_id'])
                        ->where('year', $year)
                        ->where('term', $term)
                        ->where('status', 'billed')
                        ->first();
                    
                    if ($existingBilling) {
                        $existingAmount = (float) $existingBilling->amount;
                        if (abs($existingAmount - $amount) < 0.01) {
                            $changeType = 'existing';
                            $status = 'already_billed';
                            $message = 'Already billed';
                        } else {
                            $changeType = 'changed';
                            $message = "Amount changed from " . number_format($existingAmount, 2) . " to " . number_format($amount, 2);
                        }
                    } else {
                        $changeType = 'new';
                        $totalAmount += $amount;
                    }
                }
                
                $preview[] = [
                    'student_id' => $student?->id,
                    'student_name' => $student?->full_name ?? $studentName,
                    'admission_number' => $admission ?: ($student?->admission_number ?? null),
                    'votehead_id' => $voteheadInfo['votehead_id'],
                    'votehead_name' => $voteheadInfo['votehead_name'],
                    'amount' => $amount,
                    'existing_amount' => $existingAmount,
                    'existing_billing_id' => $existingBilling?->id,
                    'change_type' => $changeType,
                    'status' => $status,
                    'message' => $message,
                    'needs_confirmation' => $changeType === 'changed',
                    'matched_students' => !empty($matchedStudents) ? $matchedStudents->map(function($s) {
                        return [
                            'id' => $s->id,
                            'name' => $s->full_name,
                            'admission_number' => $s->admission_number,
                        ];
                    })->toArray() : null,
                    'original_row_data' => [
                        'student_name' => $studentName,
                        'admission_number' => $admission,
                    ],
                ];
            }
        }

        $missingVoteheads = collect($missingVoteheads)->filter()->unique()->values();

        // Get all existing optional fees for this year/term to detect removals
        $existingOptionalFees = OptionalFee::where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->with(['student', 'votehead'])
            ->get()
            ->groupBy('student_id');
        
        // Find optional fees that exist but are not in the import (removals)
        $removals = [];
        foreach ($existingOptionalFees as $studentId => $fees) {
            $student = $fees->first()->student;
            if (!$student) continue;
            
            foreach ($fees as $fee) {
                // Check if this fee is in the preview
                $inPreview = collect($preview)->contains(function($p) use ($studentId, $fee) {
                    return ($p['student_id'] ?? null) == $studentId 
                        && ($p['votehead_id'] ?? null) == $fee->votehead_id;
                });
                
                if (!$inPreview) {
                    $removals[] = [
                        'student_id' => $studentId,
                        'student_name' => $student->full_name,
                        'admission_number' => $student->admission_number,
                        'votehead_id' => $fee->votehead_id,
                        'votehead_name' => $fee->votehead->name ?? 'Unknown',
                        'amount' => (float) $fee->amount,
                        'change_type' => 'removed',
                        'status' => 'ok',
                        'message' => 'Will be removed',
                    ];
                }
            }
        }

        return view('finance.optional_fees.import_preview', [
            'preview' => $preview,
            'removals' => $removals,
            'missingVoteheads' => $missingVoteheads,
            'studentsToMatch' => $studentsToMatch,
            'year' => $year,
            'term' => $term,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function importCommit(Request $request)
    {
        $request->validate([
            'rows' => 'required|array',
            'removals' => 'nullable|array',
            'student_matches' => 'nullable|array', // For student matching: row_index => student_id
            'confirmations' => 'nullable|array', // For confirmations: row_index => 'use_new' or 'keep_existing'
            'year' => 'required|integer',
            'term' => 'required|integer|in:1,2,3',
        ]);

        [$year, $term, $academicYearId] = $this->resolveYearAndTerm($request->year, $request->term);
        $studentMatches = $request->input('student_matches', []);
        $confirmations = $request->input('confirmations', []);

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
        $skipped = 0;
        $removed = 0;
        $totalAmount = 0;

        // Process additions and updates
        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            if (!$row || empty($row['votehead_id'])) {
                continue;
            }

            // Handle student matching
            $studentId = $row['student_id'] ?? null;
            if (!$studentId && isset($row['row_index'])) {
                // Check if student was selected via search for missing students
                if (isset($studentMatches[$row['row_index']])) {
                    $studentId = $studentMatches[$row['row_index']];
                }
            }
            
            // Skip rows without valid student
            if (!$studentId || ($row['status'] ?? '') === 'missing_student') {
                continue;
            }

            // Skip if already billed with same amount
            if (($row['status'] ?? '') === 'already_billed' && ($row['change_type'] ?? '') === 'existing') {
                $skipped++;
                continue;
            }

            // Handle confirmations for changed entries
            if (($row['needs_confirmation'] ?? false) && isset($confirmations[$row['row_index'] ?? ''])) {
                $confirmation = $confirmations[$row['row_index']];
                if ($confirmation === 'keep_existing') {
                    $skipped++;
                    continue; // Skip this row, keep existing
                }
                // If 'use_new', proceed with update (discard old)
            } elseif (($row['needs_confirmation'] ?? false)) {
                // If needs confirmation but not provided, skip
                continue;
            }

            $voteheadId = $row['votehead_id'];
            $amount = (float) ($row['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            try {
                DB::transaction(function () use ($studentId, $voteheadId, $year, $term, $academicYearId, $amount, $importBatch, $row) {
                    // For changed entries with 'use_new', ensure we use the new amount
                    $finalAmount = $amount;
                    if (($row['change_type'] ?? '') === 'changed' && ($row['needs_confirmation'] ?? false)) {
                        // Use new amount (already set in $amount)
                        $finalAmount = $amount;
                    }
                    
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
                            'amount' => $finalAmount,
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
                            'source' => 'optional',
                        ],
                        [
                            'amount' => $finalAmount,
                            'status' => 'active',
                            'posted_at' => now(),
                        ]
                    );

                    // Store original amount if not set
                    if (!$invoiceItem->original_amount) {
                        $invoiceItem->update(['original_amount' => $finalAmount]);
                    }

                    // Recalculate invoice
                    InvoiceService::recalc($invoice);
                });

                $createdOrUpdated++;
                $totalAmount += $finalAmount;
            } catch (\Throwable $e) {
                Log::warning('Optional fee import failed', [
                    'student_id' => $studentId,
                    'votehead_id' => $voteheadId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Process removals
        $removals = $request->input('removals', []);
        foreach ($removals as $encoded) {
            $removal = json_decode(base64_decode($encoded), true);
            
            if (!$removal || empty($removal['student_id']) || empty($removal['votehead_id'])) {
                continue;
            }

            $studentId = $removal['student_id'];
            $voteheadId = $removal['votehead_id'];

            try {
                DB::transaction(function () use ($studentId, $voteheadId, $year, $term) {
                    // Delete optional fee
                    OptionalFee::where('student_id', $studentId)
                        ->where('votehead_id', $voteheadId)
                        ->where('year', $year)
                        ->where('term', $term)
                        ->where('status', 'billed')
                        ->delete();

                    // Delete invoice item
                    $invoice = Invoice::where('student_id', $studentId)
                        ->where('year', $year)
                        ->where('term', $term)
                        ->first();

                    if ($invoice) {
                        \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                            ->where('votehead_id', $voteheadId)
                            ->where('source', 'optional')
                            ->delete();

                        // Recalculate invoice
                        InvoiceService::recalc($invoice);
                    }
                });

                $removed++;
            } catch (\Throwable $e) {
                Log::warning('Optional fee removal failed', [
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

        $message = "{$createdOrUpdated} optional fee(s) imported";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped (already billed)";
        }
        if ($removed > 0) {
            $message .= ", {$removed} removed";
        }
        $message .= " for Term {$term}, {$year}.";

        return redirect()
            ->route('finance.optional_fees.index')
            ->with('success', $message)
            ->with('import_batch_id', $importBatch->id);
    }

    public function template()
    {
        // Get all optional voteheads
        $voteheads = Votehead::where('is_mandatory', false)->orderBy('name')->get();
        
        // Build headers: Name, Admission Number, then all votehead names
        $headers = ['Name', 'Admission Number'];
        foreach ($voteheads as $votehead) {
            $headers[] = $votehead->name;
        }
        
        // Create sample data with empty amounts for voteheads
        $sample = [
            array_merge(['John Doe', 'RKS001'], array_fill(0, $voteheads->count(), '')),
            array_merge(['Jane Smith', 'RKS002'], array_fill(0, $voteheads->count(), '')),
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
                        ->where('source', 'optional')
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


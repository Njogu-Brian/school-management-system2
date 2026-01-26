<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\LegacyStatementTerm;
use App\Models\InvoiceItem;
use App\Models\Votehead;
use App\Models\BalanceBroughtForwardImport;
use App\Services\StudentBalanceService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BalanceBroughtForwardController extends Controller
{
    /**
     * Display list of students with balance brought forward
     */
    public function index(Request $request)
    {
        $balanceBroughtForwardVotehead = Votehead::where('code', 'BAL_BF')->first();
        
        // Get students with balance brought forward from legacy data (pre-2026)
        $legacyStudentIds = LegacyStatementTerm::where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->where('ending_balance', '>', 0)
            ->whereNotNull('student_id')
            ->distinct()
            ->pluck('student_id');

        // Get students with balance brought forward in invoices (BAL_BF items)
        $invoiceBfStudentIds = collect();
        if ($balanceBroughtForwardVotehead) {
            $invoiceBfStudentIds = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoice_items.votehead_id', $balanceBroughtForwardVotehead->id)
                ->where('invoice_items.source', 'balance_brought_forward')
                ->whereNull('invoice_items.deleted_at') // Handle soft deletes
                ->where('invoices.status', '!=', 'reversed')
                ->select('invoices.student_id')
                ->distinct()
                ->pluck('student_id')
                ->filter();
        }

        // Combine student IDs with balance brought forward
        $allStudentIds = $legacyStudentIds
            ->merge($invoiceBfStudentIds)
            ->unique()
            ->filter();
        
        // Get students with balance brought forward
        $students = Student::whereIn('id', $allStudentIds)
            ->with(['classroom', 'stream', 'category'])
            ->orderBy('admission_number')
            ->get()
            ->map(function($student) use ($balanceBroughtForwardVotehead) {
                // Get balance brought forward from legacy
                $legacyBf = StudentBalanceService::getBalanceBroughtForward($student);
                
                // Get balance brought forward from invoices
                // IMPORTANT: Show the ORIGINAL amount (as imported/manually set), not the outstanding balance
                // This ensures the value remains static and doesn't change as payments are made
                $invoiceBf = 0;
                $invoiceBfSource = null;
                if ($balanceBroughtForwardVotehead) {
                    $invoiceItems = InvoiceItem::whereHas('invoice', function($q) use ($student) {
                        $q->where('student_id', $student->id)
                          ->where('status', '!=', 'reversed');
                    })
                    ->where('votehead_id', $balanceBroughtForwardVotehead->id)
                    ->where('source', 'balance_brought_forward')
                    ->get();
                    
                    if ($invoiceItems->isNotEmpty()) {
                        // Use the original amount (as imported/manually set), not outstanding balance
                        // This keeps the value static regardless of payments made
                        $invoiceBf = $invoiceItems->sum(function($item) {
                            // Use original_amount if available (from import), otherwise use amount
                            // This represents the static value that was imported or manually set
                            return (float) ($item->original_amount ?? $item->amount);
                        });
                        $firstInvoice = $invoiceItems->first()->invoice;
                        $invoiceBfSource = "Term {$firstInvoice->term}, {$firstInvoice->year}";
                    }
                }
                
                // Use invoice BF if exists, otherwise use legacy BF
                $totalBf = $invoiceBf > 0 ? $invoiceBf : $legacyBf;
                $source = $totalBf > 0 ? ($invoiceBf > 0 ? $invoiceBfSource : 'Legacy Import') : null;
                
                return [
                    'student' => $student,
                    'balance_brought_forward' => $totalBf,
                    'source' => $source,
                    'has_invoice_bf' => $invoiceBf > 0,
                    'has_balance' => $totalBf > 0,
                ];
            })
            ->filter(function($item) {
                return $item['balance_brought_forward'] > 0;
            });

        // Get latest import batch for reversal
        $latestImport = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('balance_brought_forward_imports')) {
                $latestImport = BalanceBroughtForwardImport::where('is_reversed', false)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        } catch (\Exception $e) {
            // Table doesn't exist or other error - ignore and continue
            Log::warning('Could not fetch latest balance brought forward import: ' . $e->getMessage());
        }

        return view('finance.balance_brought_forward.index', [
            'students' => $students,
            'latestImport' => $latestImport,
        ]);
    }

    /**
     * Preview Excel import and show differences
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];

        if (empty($sheet)) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $headerRow = array_shift($sheet);
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::slug(Str::lower(trim((string) $header)), '_');
        }

        $balanceBroughtForwardVotehead = Votehead::where('code', 'BAL_BF')->first();
        $preview = [];
        $systemData = [];

        // Build system data map (admission_number => balance)
        // Include archived and alumni students for complete comparison
        $allStudentIds = Student::withArchived()->pluck('id');
        foreach ($allStudentIds as $studentId) {
            $student = Student::withArchived()->find($studentId);
            if (!$student) continue;
            
            $legacyBf = StudentBalanceService::getBalanceBroughtForward($student);
            
            $invoiceBf = 0;
            if ($balanceBroughtForwardVotehead) {
                $invoiceItems = InvoiceItem::whereHas('invoice', function($q) use ($student) {
                    $q->where('student_id', $student->id)
                      ->where('status', '!=', 'reversed');
                })
                ->where('votehead_id', $balanceBroughtForwardVotehead->id)
                ->where('source', 'balance_brought_forward')
                ->get();
                
                if ($invoiceItems->isNotEmpty()) {
                    // Use original_amount (static value) for comparison, not outstanding balance
                    $invoiceBf = $invoiceItems->sum(function($item) {
                        // Use original_amount if available (from import), otherwise use amount
                        return (float) ($item->original_amount ?? $item->amount);
                    });
                }
            }
            
            $systemBf = $invoiceBf > 0 ? $invoiceBf : $legacyBf;
            if ($systemBf > 0) {
                $systemData[$student->admission_number] = [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'balance' => $systemBf,
                ];
            }
        }

        $importData = [];
        foreach ($sheet as $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }

            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $balance = $assoc['balance'] ?? $assoc['balance_brought_forward'] ?? $assoc['amount'] ?? null;
            
            if ($admission && is_numeric($balance)) {
                $importData[$admission] = (float) $balance;
            }
        }

        // Compare system vs import
        $allAdmissions = collect(array_keys($systemData))->merge(array_keys($importData))->unique();
        
        foreach ($allAdmissions as $admission) {
            $systemBalance = $systemData[$admission]['balance'] ?? 0;
            $importBalance = $importData[$admission] ?? 0;
            // Try to find student - check active first, then archived/alumni
            $student = null;
            if (isset($systemData[$admission])) {
                $student = Student::find($systemData[$admission]['student_id']);
            }
            
            // If not found, search by admission number (including archived/alumni)
            if (!$student) {
                $student = Student::withArchived()
                    ->where('admission_number', $admission)
                    ->first();
            }
            
            if (!$student) {
                $preview[] = [
                    'student_id' => null,
                    'student_name' => $admission,
                    'admission_number' => $admission,
                    'system_balance' => null,
                    'import_balance' => $importBalance,
                    'status' => 'student_not_found',
                    'message' => 'Student not found in system (searched active, archived, and alumni)',
                    'difference' => null,
                ];
                continue;
            }

            $status = 'ok';
            $message = null;
            $difference = null;

            if ($systemBalance > 0 && $importBalance == 0) {
                $status = 'exists_in_system_only';
                $message = 'Exists in system but not in import';
                $difference = $systemBalance;
            } elseif ($systemBalance == 0 && $importBalance > 0) {
                $status = 'exists_in_import_only';
                $message = 'Exists in import but not in system';
                $difference = -$importBalance;
            } elseif (abs($systemBalance - $importBalance) > 0.01) {
                $status = 'amount_differs';
                $difference = $importBalance - $systemBalance;
                $message = 'Amount differs: System = ' . number_format($systemBalance, 2) . ', Import = ' . number_format($importBalance, 2);
            }

            $preview[] = [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'admission_number' => $admission,
                'system_balance' => $systemBalance > 0 ? $systemBalance : null,
                'import_balance' => $importBalance > 0 ? $importBalance : null,
                'status' => $status,
                'message' => $message,
                'difference' => $difference,
            ];
        }

        // Sort by status (issues first), then by admission number
        usort($preview, function($a, $b) {
            $statusOrder = ['student_not_found' => 1, 'exists_in_system_only' => 2, 'exists_in_import_only' => 3, 'amount_differs' => 4, 'ok' => 5];
            $aOrder = $statusOrder[$a['status']] ?? 99;
            $bOrder = $statusOrder[$b['status']] ?? 99;
            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }
            return strcmp($a['admission_number'], $b['admission_number']);
        });

        $hasIssues = collect($preview)->contains(function($item) {
            return $item['status'] !== 'ok';
        });

        return view('finance.balance_brought_forward.import_preview', [
            'preview' => $preview,
            'hasIssues' => $hasIssues,
        ]);
    }

    /**
     * Commit the import and update balance brought forward
     */
    public function importCommit(Request $request)
    {
        $request->validate([
            'rows' => 'required|array',
            'skip' => 'nullable|array',
        ]);

        $balanceBroughtForwardVotehead = Votehead::firstOrCreate(
            ['code' => 'BAL_BF'],
            [
                'name' => 'Balance Brought Forward',
                'is_active' => true,
            ]
        );

        // Get current academic year and term
        $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $term = \App\Models\Term::where('is_current', true)->first();
        
        if (!$academicYear || !$term) {
            return back()->with('error', 'No active academic year or term found');
        }

        $year = $academicYear->year;
        $termNumber = (int) preg_replace('/[^0-9]/', '', $term->name) ?: 1;

        return DB::transaction(function () use ($request, $balanceBroughtForwardVotehead, $academicYear, $term, $year, $termNumber) {
            // Step 1: Create snapshot of current balances BEFORE import
            $snapshot = $this->createSnapshot($balanceBroughtForwardVotehead, $year, $termNumber);

            // Step 2: Parse import rows
            $skippedIndices = $request->input('skip', []);
            $importStudentIds = [];
            $importData = [];
            $errors = [];
            
            foreach ($request->rows as $index => $rowData) {
                // Skip if this row is marked to skip
                if (in_array($index, $skippedIndices)) {
                    continue;
                }

                // Determine student ID: use matched student if provided, otherwise use original
                $studentId = null;
                if (!empty($rowData['matched_student_id'])) {
                    $studentId = (int) $rowData['matched_student_id'];
                } elseif (!empty($rowData['original_student_id'])) {
                    $studentId = (int) $rowData['original_student_id'];
                }

                if (!$studentId) {
                    $admission = $rowData['original_admission_number'] ?? 'Unknown';
                    $errors[] = "Row {$index} (Admission: {$admission}): No student matched";
                    continue;
                }

                // Determine which balance to use: import or system
                $useBalance = $rowData['use_balance'] ?? 'import';
                $finalBalance = 0;

                if ($useBalance === 'system') {
                    $finalBalance = (float) ($rowData['system_balance'] ?? 0);
                } else {
                    $finalBalance = (float) ($rowData['import_balance'] ?? 0);
                }

                // Only process if balance > 0
                if ($finalBalance > 0) {
                    $importStudentIds[] = $studentId;
                    $importData[$studentId] = $finalBalance;
                } elseif ($finalBalance == 0 && !empty($rowData['system_balance']) && $rowData['system_balance'] > 0) {
                    // If using import balance but it's 0, we might want to remove the system balance
                    // Add to list so it gets deleted
                    $importStudentIds[] = $studentId;
                }
            }

            // Step 3: Delete balances for students NOT in import (only if they had balances)
            $deletedCount = $this->deleteBalancesNotInImport($balanceBroughtForwardVotehead, $year, $termNumber, $importStudentIds);

            // Step 4: Update/create balances for students IN import
            $updated = 0;
            $skipped = 0;
            $totalAmount = 0;

            foreach ($importData as $studentId => $balance) {
                try {
                    // Find student (including archived/alumni)
                    $student = Student::withArchived()->find($studentId);
                    if (!$student) {
                        $errors[] = "Student ID {$studentId}: Student not found (including archived/alumni)";
                        continue;
                    }

                    // Ensure invoice exists
                    $invoice = InvoiceService::ensure($studentId, $year, $termNumber);

                    // Find or create balance brought forward item
                    $invoiceItem = InvoiceItem::firstOrNew([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $balanceBroughtForwardVotehead->id,
                        'source' => 'balance_brought_forward',
                    ]);

                    $oldAmount = $invoiceItem->exists ? (float) $invoiceItem->amount : 0;
                    $invoiceItem->amount = $balance;
                    $invoiceItem->status = 'active';
                    $invoiceItem->effective_date = $invoice->issued_date ?? now();
                    
                    // Store original_amount to preserve the static value (as imported/manually set)
                    // This ensures the balance brought forward page shows the original value, not outstanding balance
                    if (!$invoiceItem->original_amount) {
                        // If this is a new item, set original_amount to the balance being imported
                        // If updating, preserve the original original_amount or use the old amount
                        $invoiceItem->original_amount = $oldAmount > 0 ? $oldAmount : $balance;
                    }
                    // If original_amount already exists, don't change it (preserves the original import value)
                    
                    $invoiceItem->save();

                    // Recalculate invoice (this will update student fee balances, statements, and receipts automatically)
                    // Enable auto-allocation to ensure payments are allocated properly
                    app()->instance('auto_allocating', true);
                    InvoiceService::recalc($invoice);
                    app()->instance('auto_allocating', false);

                    // Also recalculate all invoices for this student to ensure statements are updated
                    $studentInvoices = \App\Models\Invoice::where('student_id', $studentId)->get();
                    foreach ($studentInvoices as $studentInvoice) {
                        try {
                            app()->instance('auto_allocating', true);
                            InvoiceService::recalc($studentInvoice);
                            app()->instance('auto_allocating', false);
                        } catch (\Exception $e) {
                            Log::warning('Failed to recalculate student invoice during balance import', [
                                'invoice_id' => $studentInvoice->id,
                                'student_id' => $studentId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $updated++;
                    $totalAmount += $balance;
                } catch (\Exception $e) {
                    Log::error('Balance brought forward import error', [
                        'student_id' => $studentId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = "Student ID {$studentId}: " . $e->getMessage();
                }
            }

            $skipped = count($skippedIndices);

            // Step 5: Create import batch record
            $importBatch = BalanceBroughtForwardImport::create([
                'year' => $year,
                'term' => $termNumber,
                'academic_year_id' => $academicYear->id,
                'term_id' => $term->id,
                'balances_updated_count' => $updated,
                'balances_deleted_count' => $deletedCount,
                'total_amount' => $totalAmount,
                'snapshot_before' => $snapshot,
                'imported_by' => auth()->id(),
                'imported_at' => now(),
                'is_reversed' => false,
            ]);

            $message = "{$updated} balance(s) updated and {$deletedCount} balance(s) deleted successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) were skipped.";
            }
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(s) occurred.";
            }

            return redirect()
                ->route('finance.balance-brought-forward.index')
                ->with('success', $message)
                ->with('import_batch_id', $importBatch->id)
                ->with('errors', $errors);
        });
    }

    /**
     * Create snapshot of current balances before import
     */
    private function createSnapshot($votehead, $year, $termNumber): array
    {
        $snapshot = [];

        // Get all students with balance brought forward (from invoices)
        $invoiceItems = InvoiceItem::whereHas('invoice', function($q) use ($year, $termNumber) {
            $q->where('year', $year)
              ->where('term', $termNumber)
              ->where('status', '!=', 'reversed');
        })
        ->where('votehead_id', $votehead->id)
        ->where('source', 'balance_brought_forward')
        ->with('invoice.student')
        ->get();

        foreach ($invoiceItems as $item) {
            $student = $item->invoice->student;
            if ($student) {
                $snapshot[$student->id] = [
                    'student_id' => $student->id,
                    'admission_number' => $student->admission_number,
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'amount' => (float) $item->amount,
                ];
            }
        }

        return $snapshot;
    }

    /**
     * Delete balances for students NOT in the import list
     */
    private function deleteBalancesNotInImport($votehead, $year, $termNumber, array $importStudentIds): int
    {
        // Get all invoices for this year/term
        $invoices = \App\Models\Invoice::where('year', $year)
            ->where('term', $termNumber)
            ->where('status', '!=', 'reversed')
            ->whereNotIn('student_id', $importStudentIds)
            ->pluck('id');

        if ($invoices->isEmpty()) {
            return 0;
        }

        // Delete invoice items for BAL_BF votehead that are not in import
        $deleted = InvoiceItem::whereIn('invoice_id', $invoices)
            ->where('votehead_id', $votehead->id)
            ->where('source', 'balance_brought_forward')
            ->delete();

        // Recalculate affected invoices
        foreach ($invoices as $invoiceId) {
            $invoice = \App\Models\Invoice::find($invoiceId);
            if ($invoice) {
                InvoiceService::recalc($invoice);
            }
        }

        return $deleted;
    }

    /**
     * Reverse an import
     */
    public function reverse(BalanceBroughtForwardImport $import)
    {
        try {
            if ($import->is_reversed) {
                return back()->with('error', 'This import has already been reversed.');
            }

            return DB::transaction(function () use ($import) {
                $snapshot = $import->snapshot_before ?? [];
                $balanceBroughtForwardVotehead = Votehead::where('code', 'BAL_BF')->first();
                
                if (!$balanceBroughtForwardVotehead) {
                    throw new \Exception('BAL_BF votehead not found');
                }

                // Get all current invoice items for this year/term
                $currentItems = InvoiceItem::whereHas('invoice', function($q) use ($import) {
                    $q->where('year', $import->year)
                      ->where('term', $import->term)
                      ->where('status', '!=', 'reversed');
                })
                ->where('votehead_id', $balanceBroughtForwardVotehead->id)
                ->where('source', 'balance_brought_forward')
                ->get();

                // Delete all current items (they were created/updated by the import)
                $invoiceIds = [];
                foreach ($currentItems as $item) {
                    $invoiceIds[] = $item->invoice_id;
                    $item->delete();
                }

                // Restore balances from snapshot
                $restored = 0;
                foreach ($snapshot as $studentData) {
                    try {
                        $student = Student::find($studentData['student_id']);
                        if (!$student) {
                            continue;
                        }

                        $invoice = InvoiceService::ensure($studentData['student_id'], $import->year, $import->term);
                        
                        // Restore the invoice item
                        $invoiceItem = InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $balanceBroughtForwardVotehead->id,
                            'source' => 'balance_brought_forward',
                            'amount' => $studentData['amount'],
                            'status' => 'active',
                            'effective_date' => $invoice->issued_date ?? now(),
                        ]);

                        $invoiceIds[] = $invoice->id;
                        InvoiceService::recalc($invoice);
                        $restored++;
                    } catch (\Exception $e) {
                        Log::error('Balance brought forward reversal error', [
                            'student_id' => $studentData['student_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Recalculate all affected invoices
                foreach (array_unique($invoiceIds) as $invoiceId) {
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if ($invoice) {
                        InvoiceService::recalc($invoice);
                    }
                }

                // Mark import as reversed
                $import->update([
                    'is_reversed' => true,
                    'reversed_by' => auth()->id(),
                    'reversed_at' => now(),
                ]);

                return redirect()
                    ->route('finance.balance-brought-forward.index')
                    ->with('success', "Import #{$import->id} reversed successfully. {$restored} balance(s) restored.");
            });
        } catch (\Exception $e) {
            Log::error('Balance brought forward reversal failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to reverse import: ' . $e->getMessage());
        }
    }

    /**
     * Add balance brought forward for a student (from form)
     */
    public function add(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
            'student_id' => 'required|exists:students,id',
        ]);

        $studentModel = Student::findOrFail($request->input('student_id'));
        
        // Use the same logic as update but get student from request
        return $this->saveBalance($studentModel, $request->input('balance'));
    }

    /**
     * Update a single student's balance brought forward
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        return $this->saveBalance($student, $request->input('balance'));
    }

    /**
     * Helper method to save balance brought forward
     */
    private function saveBalance(Student $student, float $balance)
    {
        $balanceBroughtForwardVotehead = Votehead::firstOrCreate(
            ['code' => 'BAL_BF'],
            [
                'name' => 'Balance Brought Forward',
                'is_active' => true,
            ]
        );

        try {
            DB::transaction(function () use ($student, $balance, $balanceBroughtForwardVotehead) {
                $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
                $term = \App\Models\Term::where('is_current', true)->first();
                
                if (!$academicYear || !$term) {
                    throw new \Exception("No active academic year or term found");
                }

                $year = $academicYear->year;
                $termNumber = (int) preg_replace('/[^0-9]/', '', $term->name) ?: 1;

                $invoice = InvoiceService::ensure($student->id, $year, $termNumber);

                $invoiceItem = InvoiceItem::firstOrNew([
                    'invoice_id' => $invoice->id,
                    'votehead_id' => $balanceBroughtForwardVotehead->id,
                    'source' => 'balance_brought_forward',
                ]);

                $oldAmount = $invoiceItem->exists ? (float) $invoiceItem->amount : 0;
                $invoiceItem->amount = $balance;
                $invoiceItem->status = 'active';
                $invoiceItem->effective_date = $invoice->issued_date ?? now();
                
                // Store original_amount to preserve the static value (as imported/manually set)
                // This ensures the balance brought forward page shows the original value, not outstanding balance
                if (!$invoiceItem->original_amount) {
                    // If this is a new item, set original_amount to the balance being set
                    // If updating, preserve the original original_amount or use the old amount
                    $invoiceItem->original_amount = $oldAmount > 0 ? $oldAmount : $balance;
                }
                // If original_amount already exists, don't change it (preserves the original import value)
                
                $invoiceItem->save();

                InvoiceService::recalc($invoice);
            });

            return back()->with('success', 'Balance brought forward saved successfully.');
        } catch (\Exception $e) {
            Log::error('Balance brought forward update error', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel file
     */
    public function template()
    {
        $headers = ['Admission Number', 'Balance Brought Forward'];
        $sample = [
            ['RKS001', 5000.00],
            ['RKS002', 3000.00],
        ];

        return Excel::download(new \App\Exports\ArrayExport($sample, $headers), 'balance_brought_forward_template.xlsx');
    }
}


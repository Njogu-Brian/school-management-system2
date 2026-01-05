<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\LegacyStatementTerm;
use App\Models\InvoiceItem;
use App\Models\Votehead;
use App\Services\StudentBalanceService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                        $invoiceBf = $invoiceItems->sum(function($item) {
                            $paid = $item->allocations()->sum('amount');
                            return max(0, $item->amount - ($item->discount_amount ?? 0) - $paid);
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

        return view('finance.balance_brought_forward.index', [
            'students' => $students,
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
        $allStudentIds = Student::pluck('id');
        foreach ($allStudentIds as $studentId) {
            $student = Student::find($studentId);
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
                    $invoiceBf = $invoiceItems->sum(function($item) {
                        $paid = $item->allocations()->sum('amount');
                        return max(0, $item->amount - ($item->discount_amount ?? 0) - $paid);
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
            $student = isset($systemData[$admission]) 
                ? Student::find($systemData[$admission]['student_id'])
                : Student::where('admission_number', $admission)->first();
            
            if (!$student) {
                $preview[] = [
                    'student_id' => null,
                    'student_name' => $admission,
                    'admission_number' => $admission,
                    'system_balance' => null,
                    'import_balance' => $importBalance,
                    'status' => 'student_not_found',
                    'message' => 'Student not found in system',
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
        ]);

        $balanceBroughtForwardVotehead = Votehead::firstOrCreate(
            ['code' => 'BAL_BF'],
            [
                'name' => 'Balance Brought Forward',
                'is_active' => true,
            ]
        );

        $updated = 0;
        $errors = [];

        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            if (!$row || empty($row['student_id']) || !isset($row['import_balance'])) {
                continue;
            }

            $studentId = $row['student_id'];
            $importBalance = (float) ($row['import_balance'] ?? 0);

            if ($importBalance <= 0) {
                continue; // Skip zero or negative balances
            }

            try {
                DB::transaction(function () use ($studentId, $importBalance, $balanceBroughtForwardVotehead, &$updated) {
                    $student = Student::find($studentId);
                    if (!$student) {
                        throw new \Exception("Student not found: {$studentId}");
                    }

                    // Get current academic year and term
                    $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
                    $term = \App\Models\Term::where('is_current', true)->first();
                    
                    if (!$academicYear || !$term) {
                        throw new \Exception("No active academic year or term found");
                    }

                    $year = $academicYear->year;
                    $termNumber = (int) preg_replace('/[^0-9]/', '', $term->name) ?: 1;

                    // Ensure invoice exists
                    $invoice = InvoiceService::ensure($studentId, $year, $termNumber);

                    // Find or create balance brought forward item
                    $invoiceItem = InvoiceItem::firstOrNew([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $balanceBroughtForwardVotehead->id,
                        'source' => 'balance_brought_forward',
                    ]);

                    // Update the amount
                    $oldAmount = $invoiceItem->exists ? (float) $invoiceItem->amount : 0;
                    $invoiceItem->amount = $importBalance;
                    $invoiceItem->status = 'active';
                    $invoiceItem->effective_date = $invoice->issued_date ?? now();
                    
                    if (!$invoiceItem->original_amount && $oldAmount > 0) {
                        $invoiceItem->original_amount = $oldAmount;
                    }
                    
                    $invoiceItem->save();

                    // Recalculate invoice
                    InvoiceService::recalc($invoice);
                    $updated++;
                });
            } catch (\Exception $e) {
                Log::error('Balance brought forward import error', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "Student ID {$studentId}: " . $e->getMessage();
            }
        }

        $message = "{$updated} balance(s) brought forward updated successfully.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " error(s) occurred.";
        }

        return redirect()
            ->route('finance.balance-brought-forward.index')
            ->with('success', $message)
            ->with('errors', $errors);
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
                
                if (!$invoiceItem->original_amount && $oldAmount > 0) {
                    $invoiceItem->original_amount = $oldAmount;
                }
                
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


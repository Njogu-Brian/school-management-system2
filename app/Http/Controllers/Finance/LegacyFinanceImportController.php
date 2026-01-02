<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;
use App\Models\LegacyStatementLineEditHistory;
use App\Services\LegacyFinanceImportService;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegacyFinanceImportController extends Controller
{
    public function index(Request $request)
    {
        $batches = LegacyFinanceImportBatch::latest()->paginate(15);

        return view('finance.legacy-imports.index', [
            'batches' => $batches,
        ]);
    }

    public function show(LegacyFinanceImportBatch $batch)
    {
        // This view can be heavy on large batches; extend execution time just for this action
        @set_time_limit(180);

        $batch->load(['terms' => function ($query) {
            $query->orderBy('student_name');
        }]);

        $lines = LegacyStatementLine::where('batch_id', $batch->id)
            ->with('term')
            ->orderBy('term_id')
            ->orderBy('sequence_no')
            ->get();

        $students = Student::select('id', 'first_name', 'last_name', 'admission_number')
            ->orderBy('first_name')
            ->get();

        $grouped = $batch->terms->groupBy('admission_number')->map(function ($terms) use ($lines) {
            $studentName = $terms->first()->student_name;
            $admission = $terms->first()->admission_number;
            $studentId = $terms->first()->student_id;
            $isMissing = $studentId === null;

            $termData = $terms->map(function ($term) use ($lines) {
                $termLines = $lines->where('term_id', $term->id);
                return [
                    'model' => $term,
                    'lines' => $termLines,
                    'hasDraft' => $termLines->where('confidence', 'draft')->count() > 0,
                ];
            });

            $hasDraft = $termData->contains(fn($t) => $t['hasDraft']);

            return [
                'student_name' => $studentName,
                'admission_number' => $admission,
                'student_id' => $studentId,
                'is_missing' => $isMissing,
                'terms' => $termData,
                'has_draft' => $hasDraft,
            ];
        });

        return view('finance.legacy-imports.show', [
            'batch' => $batch,
            'grouped' => $grouped,
            'students' => $students,
        ]);
    }


    public function rerun(Request $request, LegacyFinanceImportBatch $batch, LegacyFinanceImportService $service)
    {
        $path = $this->resolvePdfPath($batch->file_name);
        if (!$path) {
            return back()->with('error', 'Source file not found: ' . $batch->file_name);
        }

        LegacyStatementLine::where('batch_id', $batch->id)->delete();
        LegacyStatementTerm::where('batch_id', $batch->id)->delete();

        // Parse into the SAME batch, and mark status running during processing
        $batch->update(['status' => 'running']);
        $result = $service->import($path, $batch->class_label, $batch->uploaded_by, $batch);

        return redirect()
            ->route('finance.legacy-imports.show', $result['batch_id'])
            ->with('success', 'Re-parsed and reloaded batch from PDF.');
    }

    public function destroy(LegacyFinanceImportBatch $batch)
    {
        // Get all term IDs for this batch before deletion
        $termIds = LegacyStatementTerm::where('batch_id', $batch->id)->pluck('id');
        
        // Delete edit history for all lines in this batch
        $lineIds = LegacyStatementLine::where('batch_id', $batch->id)->pluck('id');
        LegacyStatementLineEditHistory::whereIn('line_id', $lineIds)->delete();
        
        // Delete parsed data (cascade will handle related data)
        LegacyStatementLine::where('batch_id', $batch->id)->delete();
        LegacyStatementTerm::where('batch_id', $batch->id)->delete();

        // Delete PDF file if present
        $path = storage_path('app/private/legacy-imports/' . $batch->file_name);
        if (is_file($path)) {
            @unlink($path);
        }
        
        // Also check alternative path
        $altPath = storage_path('app/legacy-imports/' . $batch->file_name);
        if (is_file($altPath)) {
            @unlink($altPath);
        }

        $batch->delete();

        return redirect()
            ->route('finance.legacy-imports.index')
            ->with('success', 'Legacy import batch, all transactions, and PDF deleted. All related data has been removed from student statements.');
    }

    public function updateLine(Request $request, LegacyStatementLine $line)
    {
        $validated = $request->validate([
            'txn_date' => 'nullable|date',
            'narration_raw' => 'required|string',
            'amount_dr' => 'nullable|numeric',
            'amount_cr' => 'nullable|numeric',
            'running_balance' => 'nullable|numeric',
            'confidence' => 'required|in:high,draft',
        ]);

        if (!empty($validated['amount_dr']) && !empty($validated['amount_cr'])) {
            return back()->withErrors(['amount_cr' => 'Dr and Cr cannot both be set.'])->withInput();
        }

        // Capture before values for edit history
        $beforeValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
        ];

        $narration = $validated['narration_raw'];
        $reference = $line->reference_number;
        $txnCode = $line->txn_code;
        if ($narration !== $line->narration_raw) {
            $service = app(\App\Services\LegacyFinanceImportService::class);
            $reference = $service->extractReference($narration);
            $txnCode = $service->extractTxnCode($narration);
        }

        $line->update([
            'txn_date' => $validated['txn_date'] ?? null,
            'narration_raw' => $validated['narration_raw'],
            'amount_dr' => $validated['amount_dr'] ?? null,
            'amount_cr' => $validated['amount_cr'] ?? null,
            'running_balance' => $validated['running_balance'] ?? null,
            'confidence' => $validated['confidence'],
            'reference_number' => $reference,
            'txn_code' => $txnCode,
        ]);

        // Auto-set confidence to high if amounts are clear and date present
        if ($line->amount_dr !== null || $line->amount_cr !== null) {
            $line->confidence = 'high';
            $line->save();
        }

        // Recalculate running balances for all subsequent transactions (including across terms)
        $this->recalculateRunningBalancesFromLine($line);
        
        // Refresh the line to get the updated running balance after recalculation
        $line->refresh();

        // Capture after values AFTER recalculation to get the correct running balance
        $afterValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
        ];

        // Determine which fields changed
        $changedFields = [];
        foreach ($beforeValues as $field => $beforeValue) {
            $afterValue = $afterValues[$field] ?? null;
            if ($beforeValue != $afterValue) {
                $changedFields[] = $field;
            }
        }

        // Store edit history if any fields changed
        if (!empty($changedFields)) {
            LegacyStatementLineEditHistory::create([
                'line_id' => $line->id,
                'batch_id' => $line->batch_id,
                'edited_by' => auth()->id(),
                'before_values' => $beforeValues,
                'after_values' => $afterValues,
                'changed_fields' => $changedFields,
            ]);
        }

        if ($validated['confidence'] === 'high' && $line->term) {
            $remainingDrafts = LegacyStatementLine::where('term_id', $line->term_id)
                ->where('confidence', 'draft')
                ->count();
            if ($remainingDrafts === 0) {
                $line->term->update(['status' => 'imported', 'confidence' => 'high']);
            }
        } elseif ($validated['confidence'] === 'draft' && $line->term) {
            $line->term->update(['status' => 'draft', 'confidence' => 'draft']);
        }

        // If term balances and no imbalance, upgrade remaining drafts to high
        if ($line->term) {
            $this->autoPromoteIfBalanced($line->term_id);
            $this->autoPromoteStudentIfBalanced($line->term);
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Line updated successfully',
                'running_balance' => $line->running_balance,
            ]);
        }

        return back()->with('success', 'Line updated.');
    }

    public function store(Request $request, LegacyFinanceImportService $service)
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf',
            'class_label' => 'nullable|string|max:50',
        ]);

        $path = $request->file('pdf')->store('legacy-imports');
        $fullPath = $this->resolvePdfPath(basename($path)) ?? Storage::path($path);

        $result = $service->import(
            $fullPath,
            $validated['class_label'] ?? null,
            $request->user()?->id
        );

        $message = 'Legacy statements imported. Review draft lines and confirm.';
        if (isset($result['duplicates_skipped']) && $result['duplicates_skipped'] > 0) {
            $message .= ' ' . $result['duplicates_skipped'] . ' duplicate transaction(s) were skipped.';
        }

        return redirect()
            ->route('finance.legacy-imports.show', $result['batch_id'])
            ->with('success', $message)
            ->with('duplicates_skipped', $result['duplicates_skipped'] ?? 0);
    }

    public function editHistory(LegacyFinanceImportBatch $batch)
    {
        $editHistory = LegacyStatementLineEditHistory::where('batch_id', $batch->id)
            ->with(['line.term', 'editedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('finance.legacy-imports.edit-history', [
            'batch' => $batch,
            'editHistory' => $editHistory,
        ]);
    }

    public function revertEdit(Request $request, LegacyStatementLineEditHistory $editHistory)
    {
        $line = $editHistory->line;
        if (!$line) {
            return back()->withErrors(['error' => 'Transaction line not found.']);
        }

        // Capture current values as new "before" for the revert
        $currentValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
        ];

        // Get the original values from before_values (what we're reverting to)
        $revertValues = $editHistory->before_values;

        // Update the line with the original values
        $narration = $revertValues['narration_raw'] ?? $line->narration_raw;
        $reference = $revertValues['reference_number'] ?? $line->reference_number;
        $txnCode = $revertValues['txn_code'] ?? $line->txn_code;
        
        if ($narration !== $line->narration_raw) {
            $service = app(\App\Services\LegacyFinanceImportService::class);
            $reference = $service->extractReference($narration);
            $txnCode = $service->extractTxnCode($narration);
        }

        $line->update([
            'txn_date' => $revertValues['txn_date'] ? \Carbon\Carbon::parse($revertValues['txn_date']) : null,
            'narration_raw' => $narration,
            'amount_dr' => $revertValues['amount_dr'] ?? null,
            'amount_cr' => $revertValues['amount_cr'] ?? null,
            'running_balance' => $revertValues['running_balance'] ?? null,
            'confidence' => $revertValues['confidence'] ?? $line->confidence,
            'reference_number' => $reference,
            'txn_code' => $txnCode,
        ]);

        // Recalculate running balances after revert
        $this->recalculateRunningBalancesFromLine($line);
        $line->refresh();

        // Capture the reverted values
        $revertedValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
        ];

        // Determine which fields changed in the revert
        $revertChangedFields = [];
        foreach ($currentValues as $field => $currentValue) {
            $revertedValue = $revertedValues[$field] ?? null;
            if ($currentValue != $revertedValue) {
                $revertChangedFields[] = $field;
            }
        }

        // Create a new edit history entry for the revert
        if (!empty($revertChangedFields)) {
            LegacyStatementLineEditHistory::create([
                'line_id' => $line->id,
                'batch_id' => $line->batch_id,
                'edited_by' => auth()->id(),
                'before_values' => $currentValues,
                'after_values' => $revertedValues,
                'changed_fields' => $revertChangedFields,
                'notes' => 'Reverted from edit history #' . $editHistory->id,
            ]);
        }

        return back()->with('success', 'Edit reverted successfully.');
    }

    public function searchStudent(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->select('id', 'first_name', 'last_name', 'admission_number')
            ->where(function ($w) use ($q) {
                $w->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('admission_number', 'like', "%{$q}%");
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'label' => "{$s->first_name} {$s->last_name} ({$s->admission_number})",
                ];
            });

        return response()->json($students);
    }

    private function resolvePdfPath(string $fileName): ?string
    {
        $candidates = [
            storage_path('app/private/legacy-imports/' . $fileName),
            storage_path('app/legacy-imports/' . $fileName),
        ];

        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        return null;
    }

    private function recalculateTermRunningBalance(int $termId): void
    {
        $lines = LegacyStatementLine::where('term_id', $termId)
            ->orderBy('sequence_no')
            ->get();

        $running = 0;
        foreach ($lines as $l) {
            $dr = (float) ($l->amount_dr ?? 0);
            $cr = (float) ($l->amount_cr ?? 0);
            $running += ($dr - $cr);
            $l->running_balance = $running;
            $l->save();
        }
    }

    /**
     * Recalculate running balances for all transactions from the edited line onwards,
     * including all subsequent transactions in the same term and all subsequent terms for the same student.
     * If editing a transaction in the first term, recalculate all subsequent transactions including in later terms.
     */
    private function recalculateRunningBalancesFromLine(LegacyStatementLine $line): void
    {
        $term = $line->term;
        if (!$term) {
            return;
        }

        // Get all terms for this student in this batch, ordered by academic year and term number
        $allTerms = LegacyStatementTerm::where('batch_id', $term->batch_id)
            ->where('admission_number', $term->admission_number)
            ->orderBy('academic_year')
            ->orderBy('term_number')
            ->get();

        // Determine starting balance and whether to recalculate from start of term
        $startingBalance = 0;
        $isFirstTerm = ($term->term_number == 1);
        $recalculateFromStartOfTerm = $isFirstTerm;
        
        if ($recalculateFromStartOfTerm) {
            // For first term, use the term's starting_balance
            $startingBalance = (float) ($term->starting_balance ?? 0);
        } else {
            // For subsequent terms, get ending balance from previous term
            $previousTerm = $allTerms->where('academic_year', $term->academic_year)
                ->where('term_number', $term->term_number - 1)
                ->first();
            
            if ($previousTerm) {
                // Get the last line of previous term to get its running balance
                $lastLineOfPreviousTerm = LegacyStatementLine::where('term_id', $previousTerm->id)
                    ->orderBy('sequence_no', 'desc')
                    ->first();
                
                if ($lastLineOfPreviousTerm && $lastLineOfPreviousTerm->running_balance !== null) {
                    $startingBalance = (float) $lastLineOfPreviousTerm->running_balance;
                } elseif ($previousTerm->ending_balance !== null) {
                    $startingBalance = (float) $previousTerm->ending_balance;
                }
            }
        }

        $currentRunningBalance = $startingBalance;
        $foundEditedLine = false;

        foreach ($allTerms as $t) {
            $termLines = LegacyStatementLine::where('term_id', $t->id)
                ->orderBy('sequence_no')
                ->get();

            // If we're recalculating from start of term and this is the term with the edited line
            if ($recalculateFromStartOfTerm && $t->id === $term->id) {
                // Recalculate all lines in this term from the start
                foreach ($termLines as $l) {
                    $dr = (float) ($l->amount_dr ?? 0);
                    $cr = (float) ($l->amount_cr ?? 0);
                    $currentRunningBalance += ($dr - $cr);
                    $l->running_balance = $currentRunningBalance;
                    $l->save();
                }
            } else {
                // For other terms or if not recalculating from start
                foreach ($termLines as $l) {
                    // If we've reached the edited line, start recalculating from here
                    if ($l->id === $line->id) {
                        $foundEditedLine = true;
                    }

                    if ($foundEditedLine) {
                        $dr = (float) ($l->amount_dr ?? 0);
                        $cr = (float) ($l->amount_cr ?? 0);
                        $currentRunningBalance += ($dr - $cr);
                        $l->running_balance = $currentRunningBalance;
                        $l->save();
                    } else {
                        // Use existing running balance for lines before the edited one
                        $currentRunningBalance = (float) ($l->running_balance ?? $currentRunningBalance);
                    }
                }
            }

            // Update term ending balance
            if ($termLines->isNotEmpty()) {
                $lastLine = $termLines->last();
                $t->ending_balance = $lastLine->running_balance;
                $t->save();
            }
        }
    }

    /**
     * If total Dr == total Cr for a term, promote drafts to high confidence.
     */
    private function autoPromoteIfBalanced(int $termId): void
    {
        $totalDr = LegacyStatementLine::where('term_id', $termId)->sum('amount_dr');
        $totalCr = LegacyStatementLine::where('term_id', $termId)->sum('amount_cr');

        if (abs($totalDr - $totalCr) < 0.01) {
            LegacyStatementLine::where('term_id', $termId)
                ->where('confidence', 'draft')
                ->update(['confidence' => 'high']);

            $remainingDrafts = LegacyStatementLine::where('term_id', $termId)
                ->where('confidence', 'draft')
                ->count();

            $status = $remainingDrafts === 0 ? 'imported' : 'draft';
            \App\Models\LegacyStatementTerm::where('id', $termId)->update([
                'status' => $status,
                'confidence' => $remainingDrafts === 0 ? 'high' : 'draft',
            ]);
        }
    }

    /**
     * If the student's overall statement (all terms in batch) balances within tolerance,
     * promote all remaining drafts to high confidence.
     */
    private function autoPromoteStudentIfBalanced(\App\Models\LegacyStatementTerm $term): void
    {
        if (!$term->batch_id || !$term->admission_number) {
            return;
        }

        $termIds = LegacyStatementTerm::where('batch_id', $term->batch_id)
            ->where('admission_number', $term->admission_number)
            ->pluck('id');

        if ($termIds->isEmpty()) return;

        $linesQuery = LegacyStatementLine::whereIn('term_id', $termIds);
        $totalDr = (float) $linesQuery->sum('amount_dr');
        $totalCr = (float) $linesQuery->sum('amount_cr');

        // Tolerance of 1.00 to auto-promote drafts
        if (abs($totalDr - $totalCr) <= 1.00) {
            LegacyStatementLine::whereIn('term_id', $termIds)
                ->where('confidence', 'draft')
                ->update(['confidence' => 'high']);

            LegacyStatementTerm::whereIn('id', $termIds)
                ->update(['status' => 'imported', 'confidence' => 'high']);
        }
    }
}


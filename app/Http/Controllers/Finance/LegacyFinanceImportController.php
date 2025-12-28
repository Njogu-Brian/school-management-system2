<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;
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

    public function processClass(Request $request, LegacyFinanceImportBatch $batch)
    {
        $validated = $request->validate([
            'class_label' => 'required|string',
        ]);

        ProcessLegacyBatchPosting::dispatch($batch->id, $validated['class_label']);

        return back()->with('success', 'Class processing queued: '.$validated['class_label']);
    }

    public function resolveVotehead(Request $request, LegacyFinanceImportBatch $batch)
    {
        $validated = $request->validate([
            'legacy_label' => 'required|string',
            'mode' => 'required|in:existing,new',
            'votehead_id' => 'nullable|exists:voteheads,id',
            'name' => 'nullable|string',
            'votehead_category_id' => 'nullable|exists:votehead_categories,id',
        ]);

        $normalized = $this->normalizeLabel($validated['legacy_label']);

        if ($validated['mode'] === 'existing') {
            if (!$validated['votehead_id']) {
                return back()->withErrors('Select a votehead to map.');
            }
            LegacyVoteheadMapping::updateOrCreate(
                ['legacy_label' => $normalized],
                ['votehead_id' => $validated['votehead_id'], 'status' => 'resolved', 'resolved_by' => $request->user()?->id]
            );
        } else {
            if (!$validated['name']) {
                return back()->withErrors('Provide a votehead name.');
            }
            $vh = Votehead::create([
                'name' => $validated['name'],
                'code' => strtoupper(substr(preg_replace('/\s+/', '_', $validated['name']), 0, 20)),
                'votehead_category_id' => $validated['votehead_category_id'],
                'is_active' => true,
            ]);
            LegacyVoteheadMapping::updateOrCreate(
                ['legacy_label' => $normalized],
                ['votehead_id' => $vh->id, 'status' => 'resolved', 'resolved_by' => $request->user()?->id]
            );
        }

        ProcessLegacyBatchPosting::dispatch($batch->id);
        return back()->with('success', 'Votehead mapping saved. Posting resumed.');
    }

    public function reversePosting(LegacyFinanceImportBatch $batch, LegacyLedgerPostingService $service)
    {
        $service->reverseBatch($batch->id);
        return back()->with('success', 'Legacy postings reversed for this batch.');
    }

    /**
     * JSON report of postings for a batch (optional class filter).
     */
    public function report(Request $request, LegacyFinanceImportBatch $batch, \App\Services\LegacyPostingReportService $report)
    {
        $class = $request->get('class_label');
        return response()->json([
            'summary' => $report->summary($batch->id, $class),
            'payments' => $report->payments($batch->id, $class),
            'credits' => $report->credits($batch->id, $class),
            'debits' => $report->debits($batch->id, $class),
            'discounts' => $report->discounts($batch->id, $class),
            'invoices' => $report->invoices($batch->id, $class),
        ]);
    }

    private function normalizeLabel(string $label): string
    {
        $label = preg_replace('/\(.*?\)/', '', $label); // drop parenthetical tags like (JV on ...)
        $label = preg_replace('/\s+/', ' ', $label);
        $label = trim($label);
        return strtoupper($label);
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
        // Delete parsed data
        LegacyStatementLine::where('batch_id', $batch->id)->delete();
        LegacyStatementTerm::where('batch_id', $batch->id)->delete();

        // Delete PDF file if present
        $path = storage_path('app/private/legacy-imports/' . $batch->file_name);
        if (is_file($path)) {
            @unlink($path);
        }

        $batch->delete();

        return redirect()
            ->route('finance.legacy-imports.index')
            ->with('success', 'Legacy import batch and PDF deleted.');
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

        $narration = $validated['narration_raw'];
        $reference = $line->reference_number;
        $txnCode = $line->txn_code;
        if ($narration !== $line->narration_raw) {
            $service = app(LegacyFinanceImportService::class);
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

        // Recalculate running balances for the term
        $this->recalculateTermRunningBalance($line->term_id);

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

        return redirect()
            ->route('finance.legacy-imports.show', $result['batch_id'])
            ->with('success', 'Legacy statements imported. Review draft lines and confirm.');
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


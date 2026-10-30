<?php

namespace App\Console\Commands;

use App\Models\Academics\Classroom;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Services\Academics\StudentPlacementService;
use App\Services\FeePostingService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;

/**
 * Fix 2027 invoices stamped one class too far after premature promote + future fee post.
 *
 * Scope (only these fields):
 * - students.classroom_id / stream_id (live 2026 class restore when still on destination)
 * - student_academic_history.applied_at / effective_date (re-schedule pending promote)
 * - invoices.classroom_id / stream_id + structure invoice_items amounts for the target year
 *
 * Never touches: exam marks, report cards, attendance, guardians/parents, siblings,
 * payments, receipts, credit/debit note rows (amounts recalculated around existing notes).
 */
class RepairDoubleHopInvoicesCommand extends Command
{
    protected $signature = 'invoices:repair-double-hop
                            {--year=2027 : Invoice year}
                            {--term=1 : Invoice term}
                            {--effective=2027-01-01 : Effective date for restored pending promotions}
                            {--fix : Apply changes (default dry-run)}
                            {--csv= : Optional path to write overcharged learner list CSV}';

    protected $description = 'Repair double-hop future invoices + restore live class; preserves CNs/exams/attendance/family/payments';

    public function handle(StudentPlacementService $placement, FeePostingService $fees): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');
        $fix = (bool) $this->option('fix');
        $effective = Carbon::parse((string) $this->option('effective'))->startOfDay();

        $classrooms = Classroom::withoutGlobalScope('offered')->get(['id', 'name', 'next_class_id'])->keyBy('id');
        $getProposed = new ReflectionMethod(FeePostingService::class, 'getProposedItems');
        $getProposed->setAccessible(true);

        $liveRestores = $this->findLiveClassRestores($effective);
        $invoiceRows = $this->findDoubleHopInvoices($placement, $fees, $getProposed, $classrooms, $year, $term);

        if ($liveRestores->isEmpty() && empty($invoiceRows)) {
            $this->info('No premature live classes or double-hop invoices found.');

            return self::SUCCESS;
        }

        $this->info(($fix ? 'FIXING' : 'DRY-RUN'));
        $this->comment('Safe scope: classroom/stream on student + history schedule + year invoice stamp/amounts only.');
        $this->comment('Untouched: exam marks, report cards, attendance, parents/siblings, payments, credit-note rows.');

        if ($liveRestores->isNotEmpty()) {
            $this->info('Live class restores: '.$liveRestores->count());
            foreach ($liveRestores as $row) {
                /** @var Student $student */
                $student = $row['student'];
                /** @var StudentAcademicHistory $history */
                $history = $row['history'];
                $this->line(sprintf(
                    '  LIVE  %s %s | classroom %s → %s (pending next %s)',
                    $student->admission_number,
                    trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    $student->classroom_id ?? 'alumni',
                    $history->classroom_id,
                    $history->next_classroom_id ?? 'alumni'
                ));
            }
        }

        $list = [];
        if (! empty($invoiceRows)) {
            $this->info('Double-hop invoices: '.count($invoiceRows));
            foreach ($invoiceRows as $row) {
                /** @var Student $student */
                $student = $row['student'];
                $this->line(sprintf(
                    '  INV   %s %s | %s → %s | now=%s est=%s delta=%+0.2f credits=%s | %s',
                    $student->admission_number,
                    trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    $row['stamped']->name,
                    $row['expected']->name,
                    number_format($row['current_total'], 2),
                    number_format($row['estimated_new'], 2),
                    $row['delta'],
                    number_format($row['credits'], 2),
                    $row['invoice']->invoice_number
                ));
                $list[] = [
                    'admission_number' => $student->admission_number,
                    'name' => trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    'from_class' => $row['stamped']->name,
                    'to_class' => $row['expected']->name,
                    'invoice' => $row['invoice']->invoice_number,
                    'old_total' => $row['current_total'],
                    'new_total' => null,
                    'delta' => $row['delta'],
                    'credit_notes' => $row['credits'],
                ];
            }
        }

        if (! $fix) {
            $this->comment('Dry-run only. Re-run with --fix to apply.');
            $this->writeCsv($list, false);

            return self::SUCCESS;
        }

        DB::transaction(function () use ($liveRestores, $invoiceRows, $effective, &$list) {
            foreach ($liveRestores as $row) {
                /** @var Student $student */
                $student = $row['student'];
                /** @var StudentAcademicHistory $history */
                $history = $row['history'];

                // Only live placement fields — no family, archive cascade beyond alumni false, no exams.
                $student->update([
                    'is_alumni' => false,
                    'alumni_date' => null,
                    'classroom_id' => $history->classroom_id,
                    'stream_id' => $history->stream_id,
                ]);

                $history->update([
                    'effective_date' => $effective->toDateString(),
                    'applied_at' => null,
                    'is_current' => true,
                    'notes' => trim(($history->notes ?? '').' | Restored premature live move; scheduled for '.$effective->toDateString()),
                ]);

                StudentAcademicHistory::where('student_id', $student->id)
                    ->where('id', '!=', $history->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            foreach ($invoiceRows as $idx => $row) {
                /** @var Invoice $invoice */
                $invoice = $row['invoice']->fresh(['items.creditNotes', 'items.debitNotes']);
                $rightPlacement = $row['right_placement'];
                /** @var Collection $rightItems */
                $rightItems = $row['right_items'];
                $oldTotal = $row['current_total'];

                $invoice->update([
                    'classroom_id' => (int) $rightPlacement['classroom_id'],
                    'stream_id' => $rightPlacement['stream_id'] ?? null,
                ]);

                $proposedByVotehead = $rightItems->keyBy('votehead_id');
                $protectedSources = ['transport', 'balance_brought_forward', 'swimming_attendance', 'extra_income', 'optional'];

                foreach ($invoice->items as $item) {
                    $source = (string) ($item->source ?? 'structure');
                    if (in_array($source, $protectedSources, true)) {
                        continue;
                    }

                    $proposed = $proposedByVotehead->get($item->votehead_id);
                    if (! $proposed) {
                        continue;
                    }

                    $credits = (float) $item->creditNotes()->sum('amount');
                    $debits = (float) $item->debitNotes()->sum('amount');
                    $structureAmount = (float) $proposed['amount'];
                    $finalAmount = max(0, $structureAmount - $credits + $debits);

                    $item->update([
                        'amount' => $finalAmount,
                        'original_amount' => $structureAmount,
                    ]);
                }

                foreach ($proposedByVotehead as $voteheadId => $proposed) {
                    $exists = $invoice->items->firstWhere('votehead_id', (int) $voteheadId);
                    if ($exists) {
                        continue;
                    }
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => (int) $voteheadId,
                        'amount' => (float) $proposed['amount'],
                        'original_amount' => (float) $proposed['amount'],
                        'status' => 'active',
                        'source' => $proposed['origin'] ?? 'structure',
                    ]);
                }

                $invoice->refresh();
                InvoiceService::recalc($invoice->fresh('items'));
                InvoiceService::allocateUnallocatedPaymentsForStudent((int) $invoice->student_id);
                $invoice->refresh();

                $list[$idx]['new_total'] = round((float) $invoice->total, 2);
                $list[$idx]['delta'] = round((float) $invoice->total - $oldTotal, 2);
            }
        });

        $this->info(sprintf(
            'Done. Restored %d live class(es). Repaired %d invoice(s). Credit notes and payments preserved.',
            $liveRestores->count(),
            count($invoiceRows)
        ));
        $this->writeCsv($list, true);

        return self::SUCCESS;
    }

    /**
     * Students whose live classroom_id already equals a not-yet-due promotion destination.
     *
     * @return Collection<int, array{student: Student, history: StudentAcademicHistory}>
     */
    private function findLiveClassRestores(Carbon $effective): Collection
    {
        $rows = StudentAcademicHistory::query()
            ->whereIn('promotion_status', ['promoted', 'graduated'])
            ->where(function ($q) use ($effective) {
                $q->whereNotNull('applied_at')
                    ->orWhereNull('effective_date')
                    ->orWhereDate('effective_date', '>=', $effective->toDateString());
            })
            ->orderBy('id')
            ->get()
            ->groupBy('student_id')
            ->map->last()
            ->values();

        $out = collect();
        foreach ($rows as $history) {
            /** @var Student|null $student */
            $student = Student::withAlumni()->whereKey($history->student_id)->first();
            if (! $student || ! $history->classroom_id) {
                continue;
            }

            $movedEarly = false;
            if ($history->promotion_status === 'graduated') {
                $movedEarly = (bool) $student->is_alumni || $student->classroom_id === null;
            } elseif ($history->next_classroom_id) {
                $movedEarly = (int) $student->classroom_id === (int) $history->next_classroom_id;
            }

            if (! $movedEarly) {
                continue;
            }

            $out->push(['student' => $student, 'history' => $history]);
        }

        return $out;
    }

    private function findDoubleHopInvoices(
        StudentPlacementService $placement,
        FeePostingService $fees,
        ReflectionMethod $getProposed,
        Collection $classrooms,
        int $year,
        int $term
    ): array {
        $invoices = Invoice::query()
            ->with(['classroom', 'student.classroom', 'student.stream', 'items.creditNotes', 'items.debitNotes', 'items.votehead'])
            ->where('year', $year)
            ->where('term', $term)
            ->whereNotNull('classroom_id')
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '<>', 'reversed');
            })
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($invoices as $invoice) {
            $student = $invoice->student;
            if (! $student || ! $student->classroom_id) {
                continue;
            }

            try {
                $correct = $placement->forYear($student, $year);
            } catch (\InvalidArgumentException $e) {
                $this->warn("Skip {$student->admission_number}: {$e->getMessage()}");
                continue;
            }

            if (empty($correct['classroom_id']) || (int) $invoice->classroom_id === (int) $correct['classroom_id']) {
                continue;
            }

            $stamped = $classrooms->get($invoice->classroom_id);
            $expected = $classrooms->get($correct['classroom_id']);
            if (! $stamped || ! $expected) {
                continue;
            }

            $wrongPlacement = [
                'classroom_id' => (int) $stamped->id,
                'stream_id' => $invoice->stream_id ? (int) $invoice->stream_id : null,
                'classroom_name' => (string) $stamped->name,
                'used_next_class' => true,
            ];
            $rightPlacement = [
                'classroom_id' => (int) $correct['classroom_id'],
                'stream_id' => isset($correct['stream_id']) ? $correct['stream_id'] : null,
                'classroom_name' => (string) ($correct['classroom_name'] ?? $expected->name),
                'used_next_class' => true,
            ];

            /** @var Collection $wrongItems */
            $wrongItems = $getProposed->invoke($fees, $student, $year, $term, [], $wrongPlacement);
            /** @var Collection $rightItems */
            $rightItems = $getProposed->invoke($fees, $student, $year, $term, [], $rightPlacement);

            $currentTotal = round((float) $invoice->total, 2);
            $wrongStructure = round((float) $wrongItems->sum('amount'), 2);
            $rightStructure = round((float) $rightItems->sum('amount'), 2);
            $extras = round($currentTotal - $wrongStructure, 2);
            $estimatedNew = round($rightStructure + $extras, 2);
            $delta = round($estimatedNew - $currentTotal, 2);

            $rows[] = [
                'invoice' => $invoice,
                'student' => $student,
                'stamped' => $stamped,
                'expected' => $expected,
                'right_placement' => $rightPlacement,
                'right_items' => $rightItems,
                'current_total' => $currentTotal,
                'estimated_new' => $estimatedNew,
                'delta' => $delta,
                'credits' => round((float) $invoice->items->sum(fn ($i) => $i->creditNotes->sum('amount')), 2),
            ];
        }

        return $rows;
    }

    private function writeCsv(array $list, bool $applied): void
    {
        if ($list === []) {
            return;
        }

        $path = $this->option('csv');
        if (! $path) {
            $path = storage_path('app/exports/double_hop_overcharged_'.($applied ? 'fixed' : 'dryrun').'_'.now()->format('Ymd_His').'.csv');
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($path, 'w');
        fputcsv($fh, ['admission_number', 'name', 'from_class', 'to_class', 'invoice', 'old_total', 'new_total', 'delta', 'credit_notes']);
        foreach ($list as $row) {
            fputcsv($fh, [
                $row['admission_number'],
                $row['name'],
                $row['from_class'],
                $row['to_class'],
                $row['invoice'],
                $row['old_total'],
                $row['new_total'],
                $row['delta'],
                $row['credit_notes'],
            ]);
        }
        fclose($fh);
        $this->info('Wrote '.$path);
    }
}

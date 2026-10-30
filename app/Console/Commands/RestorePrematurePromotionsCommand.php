<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Services\Academics\StudentPlacementService;
use App\Services\FamilyArchiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Undo early live class moves from promotions that should wait until 1 Jan next year.
 * Restores students.classroom_id to the history from-class and clears applied_at.
 */
class RestorePrematurePromotionsCommand extends Command
{
    protected $signature = 'promotions:restore-premature
                            {--fix : Apply changes (default is dry-run)}
                            {--from= : Only history with promotion_date on/after this date (Y-m-d; default: all)}
                            {--effective=2027-01-01 : Effective date to schedule restored promotions for}
                            {--calendar-year= : Calendar year that must still be current (default: now)}';

    protected $description = 'Restore live classes for promotions applied before their calendar-year effective date';

    public function handle(StudentPlacementService $placement): int
    {
        $fix = (bool) $this->option('fix');
        $from = $this->option('from') ? (string) $this->option('from') : null;
        $effective = Carbon::parse((string) $this->option('effective'))->startOfDay();
        $calendarYear = (int) ($this->option('calendar-year') ?: $placement->calendarYear());

        if ($calendarYear >= (int) $effective->year) {
            $this->warn("Calendar year {$calendarYear} is not before effective {$effective->toDateString()}; nothing to restore.");

            return self::SUCCESS;
        }

        $rows = StudentAcademicHistory::query()
            ->whereIn('promotion_status', ['promoted', 'graduated'])
            ->when($from, fn ($q) => $q->whereDate('promotion_date', '>=', $from))
            ->where(function ($q) use ($effective) {
                // Already-applied rows, or rows already stamped for the future effective date.
                $q->whereNotNull('applied_at')
                    ->orWhereNull('effective_date')
                    ->orWhereDate('effective_date', '>=', $effective->toDateString());
            })
            ->orderBy('id')
            ->get();

        // Prefer the latest history row per student.
        $rows = $rows->groupBy('student_id')->map->last()->values();

        $candidates = collect();
        foreach ($rows as $history) {
            /** @var Student|null $student */
            $student = Student::withAlumni()->whereKey($history->student_id)->first();
            if (! $student) {
                continue;
            }

            $fromClassId = $history->classroom_id ? (int) $history->classroom_id : null;
            if (! $fromClassId) {
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

            $candidates->push([$history, $student]);
        }

        if ($candidates->isEmpty()) {
            $this->warn('No premature promotions found to restore.');

            return self::SUCCESS;
        }

        $this->info(($fix ? 'FIXING' : 'DRY-RUN').": {$candidates->count()} student(s) → restore 2026 live class; schedule {$effective->toDateString()}");

        $fixed = 0;
        $invoicesFixed = 0;

        $invoiceYear = $calendarYear;

        DB::transaction(function () use ($candidates, $fix, $effective, $invoiceYear, &$fixed, &$invoicesFixed) {
            foreach ($candidates as [$history, $student]) {
                /** @var StudentAcademicHistory $history */
                /** @var Student $student */
                $this->line(sprintf(
                    '  #%d %s (%s): live %s → restore class #%s; next #%s',
                    $student->id,
                    trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    $student->admission_number,
                    $student->classroom_id ?? 'alumni',
                    $history->classroom_id,
                    $history->next_classroom_id ?? 'alumni'
                ));

                if (! $fix) {
                    continue;
                }

                $wasAlumni = (bool) $student->is_alumni;

                $student->update([
                    'is_alumni' => false,
                    'alumni_date' => null,
                    'classroom_id' => $history->classroom_id,
                    'stream_id' => $history->stream_id,
                    'archive' => 0,
                ]);

                if ($wasAlumni) {
                    app(FamilyArchiveService::class)->onStudentRestored(
                        Student::withAlumni()->whereKey($student->id)->first()
                    );
                }

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

                $invoicesFixed += $this->repairCurrentYearInvoices($student, $history, $invoiceYear);

                $fixed++;
            }
        });

        if ($fix) {
            $this->info("Restored {$fixed} student(s). Repaired {$invoicesFixed} invoice placement(s).");
        } else {
            $this->comment('Dry-run only. Re-run with --fix to apply.');
        }

        return self::SUCCESS;
    }

    private function repairCurrentYearInvoices(Student $student, StudentAcademicHistory $history, int $year): int
    {
        if (! Schema::hasColumn('invoices', 'classroom_id') || ! $history->classroom_id) {
            return 0;
        }

        $nextId = $history->next_classroom_id ? (int) $history->next_classroom_id : null;
        $fromId = (int) $history->classroom_id;

        // Only rewrite invoices that were stamped with the destination class after early promote.
        if (! $nextId) {
            return 0;
        }

        $count = 0;
        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->where('classroom_id', $nextId)
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'classroom_id' => $fromId,
                'stream_id' => $history->stream_id,
            ]);
            $count++;
        }

        return $count;
    }
}

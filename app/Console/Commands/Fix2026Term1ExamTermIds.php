<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Exam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Fix2026Term1ExamTermIds extends Command
{
    protected $signature = 'exams:fix-term-ids
        {--academic-year=2026 : Academic year number to fix (e.g., 2026)}
        {--term-name="Term 1" : Term name to fix inside that academic year}
        {--dry-run : Show what would be updated, without saving}';

    protected $description = 'Fix wrong term_id for selected academic year exams whose names indicate the target term.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $academicYearNumber = (string) $this->option('academic-year');
        $termName = (string) $this->option('term-name');

        $academicYear = AcademicYear::query()
            ->where('year', $academicYearNumber)
            ->first();

        if (! $academicYear) {
            $this->error("Academic year '{$academicYearNumber}' not found in `academic_years`.");
            return self::FAILURE;
        }

        $correctTerm = Term::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('name', $termName)
            ->first();

        if (! $correctTerm) {
            $this->error("Correct {$termName} term not found for academic_year_id={$academicYear->id}.");
            return self::FAILURE;
        }

        // Extract term number from "Term 1", "Term 2", etc.
        $termNumber = null;
        if (preg_match('/term\\s*(\\d+)/i', $termName, $m)) {
            $termNumber = $m[1];
        }

        $patterns = [];
        if ($termNumber !== null) {
            $patterns = [
                "%term {$termNumber}%",
                "%term{$termNumber}%",
            ];
        } else {
            $patterns = ["%{$termName}%"];
        }

        // We only target exams currently linked to ANY other term with the same name.
        $wrongTermIds = Term::query()
            ->where('name', $termName)
            ->where('academic_year_id', '!=', $academicYear->id)
            ->pluck('id')
            ->all();

        if ($wrongTermIds === []) {
            $this->warn("No wrong term_ids found for term '{$termName}'. Nothing to fix.");
            return self::SUCCESS;
        }

        $targetExamsQuery = Exam::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('term_id', $wrongTermIds)
            ->where('term_id', '!=', $correctTerm->id)
            ->where(function ($q) use ($patterns) {
                foreach ($patterns as $p) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', [strtolower($p)]);
                }
            });

        $targetExamsCount = (clone $targetExamsQuery)->count();
        if ($targetExamsCount === 0) {
            $this->info("No exams found that look like '{$termName}' but are linked to a wrong term_id.");
            return self::SUCCESS;
        }

        $sessionIds = (clone $targetExamsQuery)
            ->whereNotNull('exam_session_id')
            ->distinct()
            ->pluck('exam_session_id')
            ->filter()
            ->values()
            ->all();

        $this->line("Academic year: {$academicYearNumber} (id={$academicYear->id})");
        $this->line("Target term: {$termName} (correct term_id={$correctTerm->id})");
        $this->line('Dry run: ' . ($dryRun ? 'yes' : 'no'));
        $this->line("Exams to update: {$targetExamsCount}");
        $this->line('Affected exam_sessions: ' . count($sessionIds));

        if ($dryRun) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($targetExamsQuery, $sessionIds, $correctTerm) {
            $targetExamsQuery->update(['term_id' => $correctTerm->id]);

            if ($sessionIds !== []) {
                DB::table('exam_sessions')
                    ->whereIn('id', $sessionIds)
                    ->update(['term_id' => $correctTerm->id]);
            }
        });

        $this->info('Done. Term IDs updated for exams and their sessions.');
        return self::SUCCESS;
    }
}


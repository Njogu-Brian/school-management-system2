<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Term;
use App\Services\FeeClearanceStatusService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecomputeFeeClearances extends Command
{
    protected $signature = 'fee-clearance:recompute {--term_id=} {--student_id=} {--dry-run}';

    protected $description = 'Recompute and snapshot student fee clearance status for a term';

    public function handle(FeeClearanceStatusService $service)
    {
        $termId = $this->option('term_id');
        $studentId = $this->option('student_id');
        $dryRun = (bool) $this->option('dry-run');

        $term = $termId
            ? Term::find($termId)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        if (!$term) {
            $this->error('No term found (provide --term_id or set a current term).');
            return 1;
        }

        $asOf = Carbon::now();

        $studentsQuery = Student::where('archive', 0)
            ->where('is_alumni', false);

        if ($studentId) {
            $studentsQuery->where('id', $studentId);
        }

        $total = (int) $studentsQuery->count();
        $this->info("Recomputing fee clearances for term #{$term->id} ({$term->name}) — {$total} student(s).");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        $studentsQuery->orderBy('id')->chunk(250, function ($chunk) use ($service, $term, $asOf, $dryRun, $bar, &$updated) {
            foreach ($chunk as $student) {
                if ($dryRun) {
                    $service->compute($student, $term, $asOf);
                } else {
                    $service->upsertSnapshot($student, $term, $asOf);
                    $updated++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run complete (no rows written).');
            return 0;
        }

        $this->info("Done. Upserted {$updated} clearance snapshot(s).");
        return 0;
    }
}


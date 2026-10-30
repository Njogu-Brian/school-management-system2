<?php

namespace App\Console\Commands;

use App\Models\StudentAcademicHistory;
use App\Services\Academics\StudentPlacementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyDuePromotionsCommand extends Command
{
    protected $signature = 'promotions:apply-due
                            {--date= : Apply promotions effective on or before this date (Y-m-d; default today)}
                            {--dry-run : List due promotions without applying}';

    protected $description = 'Apply deferred student promotions/graduations whose effective_date is due';

    public function handle(StudentPlacementService $placement): int
    {
        $onOrBefore = $this->option('date') ?: now()->toDateString();
        $dryRun = (bool) $this->option('dry-run');

        $rows = StudentAcademicHistory::query()
            ->due($onOrBefore)
            ->whereIn('promotion_status', ['promoted', 'graduated', 'demoted'])
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info("No due promotions on or before {$onOrBefore}.");

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'DRY-RUN' : 'APPLYING').": {$rows->count()} promotion(s) due on/before {$onOrBefore}");

        $applied = 0;
        DB::transaction(function () use ($rows, $placement, $dryRun, &$applied) {
            foreach ($rows as $history) {
                $this->line(sprintf(
                    '  history #%d student #%d %s → next=%s effective=%s',
                    $history->id,
                    $history->student_id,
                    $history->promotion_status,
                    $history->next_classroom_id ?? 'alumni',
                    optional($history->effective_date)?->toDateString() ?? 'n/a'
                ));

                if ($dryRun) {
                    continue;
                }

                $placement->applyHistory($history);
                $applied++;
            }
        });

        if ($dryRun) {
            $this->comment('Dry-run only. Re-run without --dry-run to apply.');
        } else {
            $this->info("Applied {$applied} promotion(s).");
        }

        return self::SUCCESS;
    }
}

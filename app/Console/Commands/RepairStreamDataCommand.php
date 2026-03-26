<?php

namespace App\Console\Commands;

use App\Services\StreamLifecycleService;
use Illuminate\Console\Command;

class RepairStreamDataCommand extends Command
{
    protected $signature = 'streams:repair-data {--dry-run : Show what would change without saving}';

    protected $description = 'Align stream_teacher, classroom_subjects, fee_structures, students, and admissions with primary+pivot classroom links (fixes ghost Assign Teachers rows after streams were edited or removed)';

    public function handle(StreamLifecycleService $streamLifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no database changes will be made.');
        }

        $counts = $streamLifecycle->repairOrphanStreamData($dryRun);

        $this->table(
            ['Metric', 'Count'],
            collect($counts)->map(fn ($n, $k) => [str_replace('_', ' ', $k), $n])->values()->all()
        );

        if ($dryRun) {
            $this->info('Run without --dry-run to apply fixes.');
        } else {
            $this->info('Stream data repair completed.');
        }

        return self::SUCCESS;
    }
}

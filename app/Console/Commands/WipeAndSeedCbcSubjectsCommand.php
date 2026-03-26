<?php

namespace App\Console\Commands;

use App\Services\CbcRationalizedSubjectSyncService;
use Illuminate\Console\Command;

class WipeAndSeedCbcSubjectsCommand extends Command
{
    protected $signature = 'cbc:wipe-and-seed-subjects
                            {--assign : After seeding, assign core subjects to classrooms by level_type}
                            {--force : Required in production; skips interactive confirmation}';

    protected $description = 'DESTRUCTIVE: delete all subject-linked rows (exams, marks, homework, timetables, etc.), delete all subjects, seed canonical Kenya CBC codes, optionally assign classes.';

    public function handle(CbcRationalizedSubjectSyncService $sync): int
    {
        if (! $this->option('force') && ! $this->confirm('This deletes ALL subjects and related academic records. Continue?', false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $assign = (bool) $this->option('assign');
        $result = $sync->wipeAllSubjectsAndReseed($assign);

        $this->info(sprintf(
            'Done. %d subject codes seeded; %d assignment rows; %d level bands.',
            $result['created'],
            $result['migrated_assignments'],
            $result['levels_processed'] ?? 0
        ));

        return self::SUCCESS;
    }
}

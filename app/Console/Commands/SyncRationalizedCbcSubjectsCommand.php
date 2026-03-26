<?php

namespace App\Console\Commands;

use App\Services\CbcRationalizedSubjectSyncService;
use Illuminate\Console\Command;

class SyncRationalizedCbcSubjectsCommand extends Command
{
    protected $signature = 'cbc:sync-rationalized-subjects
                            {level? : Foundation|PP1|PP2|Grade 1|…|Grade 9, or "all"}
                            {--assign : Link core subjects to classrooms (auto-match by classroom level_type when none listed)}';

    protected $description = 'Upsert canonical Kenya CBC subject codes (one row per code) and optionally assign core subjects to classrooms by level.';

    public function handle(CbcRationalizedSubjectSyncService $sync): int
    {
        $levelArg = $this->argument('level');
        $assign = (bool) $this->option('assign');

        if (! $levelArg || strcasecmp((string) $levelArg, 'all') === 0) {
            $result = $sync->syncAllLevels(
                $assign,
                [],
            );
            $this->info(sprintf(
                'All %d level bands: %d catalogue codes, %d classroom assignment row(s).',
                $result['levels_processed'],
                $result['created'],
                $result['migrated_assignments'],
            ));
        } else {
            $result = $sync->syncLevel(
                (string) $levelArg,
                $assign,
                [],
            );
            $this->line(sprintf(
                '<info>%s</info> (%s): %d catalogue codes, %d assignment row(s), %d classrooms matched, %d codes for this band.',
                $levelArg,
                $result['level_type'],
                $result['created'],
                $result['migrated_assignments'],
                $result['assign_classroom_count'] ?? 0,
                $result['codes_for_level'] ?? 0
            ));
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}

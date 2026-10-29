<?php

namespace App\Console\Commands;

use App\Services\Academics\ExamPaperDeduplicator;
use Illuminate\Console\Command;

class DedupeExamPapersCommand extends Command
{
    protected $signature = 'exams:dedupe-papers {--dry-run : Report duplicates without changing data}';

    protected $description = 'Merge duplicate exam papers for the same type/year/term/class/subject/stream, preserving marks';

    public function handle(ExamPaperDeduplicator $deduplicator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Scanning duplicate exam papers (dry-run)...' : 'Deduplicating exam papers...');

        $stats = $deduplicator->dedupe($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Duplicate groups', $stats['groups']],
                ['Papers removed', $stats['removed']],
                ['Marks moved', $stats['marks_moved']],
                ['Duplicate marks dropped', $stats['marks_dropped']],
            ]
        );

        if ($dryRun) {
            $this->comment('Dry-run only. Re-run without --dry-run to apply.');
        } else {
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}

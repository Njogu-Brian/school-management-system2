<?php

namespace App\Console\Commands;

use App\Models\ParentInfo;
use Illuminate\Console\Command;

class SplitParentStoredNames extends Command
{
    protected $signature = 'parents:split-stored-names {--dry-run : Show what would change without saving}';

    protected $description = 'Split parent/guardian names dumped into first_name into first/middle/last';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $updated = 0;
        $slots = ['father', 'mother', 'guardian'];

        ParentInfo::query()->orderBy('id')->chunkById(200, function ($parents) use ($dryRun, $slots, &$updated) {
            foreach ($parents as $parent) {
                $changes = [];
                foreach ($slots as $slot) {
                    $split = $parent->splitStoredNameAttributes($slot);
                    if ($split) {
                        $changes = array_merge($changes, $split);
                    }
                }
                if ($changes === []) {
                    continue;
                }
                $updated++;
                $this->line(sprintf(
                    '#%d  father [%s / %s / %s]  mother [%s / %s / %s]  guardian [%s / %s / %s]',
                    $parent->id,
                    $changes['father_first_name'] ?? $parent->father_first_name,
                    $changes['father_middle_name'] ?? $parent->father_middle_name,
                    $changes['father_last_name'] ?? $parent->father_last_name,
                    $changes['mother_first_name'] ?? $parent->mother_first_name,
                    $changes['mother_middle_name'] ?? $parent->mother_middle_name,
                    $changes['mother_last_name'] ?? $parent->mother_last_name,
                    $changes['guardian_first_name'] ?? $parent->guardian_first_name,
                    $changes['guardian_middle_name'] ?? $parent->guardian_middle_name,
                    $changes['guardian_last_name'] ?? $parent->guardian_last_name,
                ));
                if (! $dryRun) {
                    $parent->forceFill($changes)->save();
                }
            }
        });

        $this->info(($dryRun ? 'Would update ' : 'Updated ').$updated.' parent record(s).');

        return self::SUCCESS;
    }
}

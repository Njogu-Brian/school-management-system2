<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyUpdateLink;
use Illuminate\Console\Command;

class EnsureProfileUpdateLinks extends Command
{
    protected $signature = 'families:ensure-profile-update-links
                            {--dry-run : List what would be done without making changes}';
    protected $description = 'Ensure every existing family has one profile update link (does not create families).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $linksCreated = 0;

        // Families without an update link → create one (families maintain one link each)
        $familiesWithoutLink = Family::whereDoesntHave('updateLink')->get();
        foreach ($familiesWithoutLink as $family) {
            if ($dryRun) {
                $this->line("Would create profile update link for family #{$family->id}");
                $linksCreated++;
                continue;
            }
            FamilyUpdateLink::firstOrCreate(
                ['family_id' => $family->id],
                ['is_active' => true]
            );
            $linksCreated++;
            $this->line("Created profile update link for family #{$family->id}");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Action', $dryRun ? 'Would do' : 'Done'],
            [
                ['Profile update links created (families missing link)', $linksCreated],
            ]
        );

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyUpdateLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetProfileUpdateLinks extends Command
{
    protected $signature = 'families:reset-profile-update-links
                            {--dry-run : List what would be done without making changes}';

    protected $description = 'Regenerate profile update links for all existing families only. Does NOT create new families.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $families = Family::with('updateLink')->get();
        $linksCreated = 0;
        $linksReset = 0;

        foreach ($families as $family) {
            if ($family->updateLink) {
                if (!$dryRun) {
                    $family->updateLink->update([
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                        'last_sent_at' => null,
                    ]);
                }
                $linksReset++;
            } else {
                if (!$dryRun) {
                    FamilyUpdateLink::create([
                        'family_id' => $family->id,
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                    ]);
                }
                $linksCreated++;
            }
        }

        $this->info('Profile update links regenerated for ' . $families->count() . ' families.');
        $this->info('  - Reset: ' . $linksReset . ', Created: ' . $linksCreated);

        return self::SUCCESS;
    }
}

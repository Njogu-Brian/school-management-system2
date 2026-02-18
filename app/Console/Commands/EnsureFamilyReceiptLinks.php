<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyReceiptLink;
use Illuminate\Console\Command;

class EnsureFamilyReceiptLinks extends Command
{
    protected $signature = 'families:ensure-receipt-links
                            {--dry-run : List what would be done without making changes}';
    protected $description = 'Ensure every family with students has one permanent receipt link (my-receipts). Families share one link.';

    public function handle(): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('family_receipt_links')) {
            $this->error('Table family_receipt_links does not exist. Run: php artisan migrate');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $families = Family::has('students')->get();
        $created = 0;

        foreach ($families as $family) {
            if (FamilyReceiptLink::where('family_id', $family->id)->exists()) {
                continue;
            }
            if ($dryRun) {
                $this->line("Would create receipt link for family #{$family->id}");
                $created++;
                continue;
            }
            FamilyReceiptLink::firstOrCreate(
                ['family_id' => $family->id],
                ['is_active' => true]
            );
            $created++;
            $this->line("Created receipt link for family #{$family->id}");
        }

        $this->newLine();
        $this->info('Summary: ' . $created . ' receipt link(s) ' . ($dryRun ? 'would be created' : 'created') . '.');

        return self::SUCCESS;
    }
}

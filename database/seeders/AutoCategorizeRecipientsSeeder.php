<?php

namespace Database\Seeders;

use App\Services\Finance\RecipientMemoryService;
use Illuminate\Database\Seeder;

/**
 * Backfills auto-categorisation across ALL currently pending statement lines,
 * using everything you've already classified (recipient phone, vendor/recipient
 * name, and the purpose words you type). Matching lines are set to
 * confirmed / personal and grouped, ready for you to submit and approve.
 *
 * Safe to re-run any time: it only touches pending lines and only fills blanks.
 * The more you classify, the more it can auto-apply on the next run and on
 * every future statement upload.
 *
 * Run with:  php artisan db:seed --class=AutoCategorizeRecipientsSeeder
 */
class AutoCategorizeRecipientsSeeder extends Seeder
{
    public function run(): void
    {
        $stats = app(RecipientMemoryService::class)->applyToPendingLines();

        $this->command?->info(sprintf(
            'Scanned %d pending line(s). Auto-categorised %d business + %d personal (by phone %d, by name %d, by keyword %d).',
            $stats['scanned'],
            $stats['confirmed'],
            $stats['personal'],
            $stats['by_phone'],
            $stats['by_name'],
            $stats['by_keyword'],
        ));
    }
}

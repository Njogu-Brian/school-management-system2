<?php

namespace App\Console\Commands;

use App\Services\ParentCredentialsService;
use Illuminate\Console\Command;

/**
 * Reset every parent app login to the admission-year temporary password (RKS001-26).
 * Does not change elevated staff accounts that happen to have parent_id.
 */
class ResetParentTempPasswords extends Command
{
    protected $signature = 'parents:reset-temp-passwords {--dry-run : Count accounts without changing passwords}';

    protected $description = 'Reset all parent Users-app passwords to admission-YY and require a change on next sign-in';

    public function handle(ParentCredentialsService $credentials): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $credentials->resetAllParentTempPasswords($dryRun);

        $this->info(($dryRun ? 'Would reset ' : 'Reset ').$result['ok'].' parent account(s).');
        if ($result['skipped'] > 0) {
            $this->line('Skipped '.$result['skipped'].' elevated staff account(s) with parent_id.');
        }
        if ($result['fail'] > 0) {
            $this->warn('Failed: '.$result['fail']);
            foreach (array_slice($result['errors'], 0, 20) as $error) {
                $this->line('  '.$error);
            }
        }

        return $result['fail'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

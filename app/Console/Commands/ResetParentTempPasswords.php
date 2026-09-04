<?php

namespace App\Console\Commands;

use App\Services\ParentCredentialsService;
use Illuminate\Console\Command;

/**
 * Reset every parent app login to admission-full year (RKS001-2026).
 * Does not change staff accounts (including staff who are parents of enrolled children).
 */
class ResetParentTempPasswords extends Command
{
    protected $signature = 'parents:reset-temp-passwords {--dry-run : Count accounts without changing passwords}';

    protected $description = 'Set all non-staff parent passwords to admission-full year (e.g. RKS001-2026)';

    public function handle(ParentCredentialsService $credentials): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $credentials->resetAllParentTempPasswords($dryRun);

        $this->info(($dryRun ? 'Would reset ' : 'Reset ').$result['ok'].' parent account(s).');
        if ($result['skipped'] > 0) {
            $this->line('Skipped '.$result['skipped'].' staff account(s) — existing credentials left unchanged.');
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

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Remove Parent role from elevated staff so Admin/API scope is not narrowed.
 * Keeps parent_id for Users-app dual mode.
 */
class RepairElevatedParentRolesCommand extends Command
{
    protected $signature = 'parents:repair-elevated-parent-roles {--dry-run : List affected users only}';

    protected $description = 'Remove Parent role from Super Admin/Admin/staff so dashboards are not parent-scoped';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $users = User::role('Parent')->get()->filter(fn (User $u) => $u->hasElevatedStaffRole());

        if ($users->isEmpty()) {
            $this->info('No elevated users with Parent role found.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $this->line(sprintf(
                '%s #%d %s <%s> parent_id=%s',
                $dryRun ? '[dry-run]' : '[fix]',
                $user->id,
                $user->name,
                $user->email,
                $user->parent_id ?? 'null'
            ));
            if (! $dryRun) {
                $user->removeRole('Parent');
            }
        }

        $this->info(($dryRun ? 'Would repair ' : 'Repaired ').$users->count().' user(s).');

        return self::SUCCESS;
    }
}

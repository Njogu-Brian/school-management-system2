<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\StaffProfileChange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncStaffDobFromProfileChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staff:sync-dob-from-changes 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if staff already has a date_of_birth}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync date_of_birth from approved profile changes to staff table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Finding approved profile changes with date_of_birth...');

        // Get all approved profile changes that include date_of_birth
        $approvedChanges = StaffProfileChange::where('status', 'approved')
            ->whereNotNull('reviewed_at')
            ->with('staff')
            ->get()
            ->filter(function ($change) {
                $changes = $change->changes ?? [];
                return isset($changes['date_of_birth']) && 
                       isset($changes['date_of_birth']['new']) &&
                       !empty($changes['date_of_birth']['new']);
            });

        if ($approvedChanges->isEmpty()) {
            $this->warn('No approved profile changes with date_of_birth found.');
            return 0;
        }

        $this->info("Found {$approvedChanges->count()} approved profile change(s) with date_of_birth.");

        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($approvedChanges as $change) {
            $staff = $change->staff;
            
            if (!$staff) {
                $this->warn("  ⚠ Skipping change #{$change->id}: Staff not found");
                $skipped++;
                continue;
            }

            $changes = $change->changes;
            $newDob = $changes['date_of_birth']['new'] ?? null;

            if (empty($newDob)) {
                $this->warn("  ⚠ Skipping change #{$change->id} for {$staff->full_name}: No new date_of_birth value");
                $skipped++;
                continue;
            }

            // Parse the date
            try {
                $parsedDate = Carbon::parse($newDob)->format('Y-m-d');
            } catch (\Exception $e) {
                $errors[] = "Change #{$change->id} for {$staff->full_name}: Invalid date format '{$newDob}'";
                $this->error("  ✗ Change #{$change->id} for {$staff->full_name}: Invalid date format '{$newDob}'");
                $skipped++;
                continue;
            }

            // Check if staff already has a date_of_birth
            if (!$force && $staff->date_of_birth) {
                $currentDob = $staff->date_of_birth->format('Y-m-d');
                if ($currentDob === $parsedDate) {
                    $this->line("  → Skipping {$staff->full_name}: Already has matching date_of_birth ({$currentDob})");
                    $skipped++;
                    continue;
                } else {
                    $this->warn("  ⚠ {$staff->full_name} already has date_of_birth ({$currentDob}), approved change has ({$parsedDate})");
                    if (!$dryRun) {
                        $this->line("    Updating to approved value...");
                    }
                }
            }

            if ($dryRun) {
                $currentDob = $staff->date_of_birth ? $staff->date_of_birth->format('Y-m-d') : 'NULL';
                $this->line("  → Would update {$staff->full_name} ({$staff->staff_id}): {$currentDob} → {$parsedDate}");
                $updated++;
            } else {
                try {
                    DB::transaction(function () use ($staff, $parsedDate) {
                        $staff->date_of_birth = $parsedDate;
                        $staff->save();
                    });
                    $this->info("  ✓ Updated {$staff->full_name} ({$staff->staff_id}): {$parsedDate}");
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = "Change #{$change->id} for {$staff->full_name}: {$e->getMessage()}";
                    $this->error("  ✗ Failed to update {$staff->full_name}: {$e->getMessage()}");
                    $skipped++;
                }
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN MODE: No changes were made. Run without --dry-run to apply changes.');
        }

        return 0;
    }
}


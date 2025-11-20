<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class FixEmailWhitespace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:email-whitespace {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix leading/trailing whitespace in email addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No changes will be made");
            $this->newLine();
        }

        $this->info("Fixing email whitespace issues...");
        $this->newLine();

        // Fix users table
        $this->info("Step 1: Checking users table...");
        $users = User::all();
        $userFixes = 0;
        
        foreach ($users as $user) {
            $originalEmail = $user->email;
            $trimmedEmail = trim($originalEmail);
            
            if ($originalEmail !== $trimmedEmail) {
                $this->line("   User ID {$user->id}: '{$originalEmail}' → '{$trimmedEmail}'");
                $userFixes++;
                
                if (!$dryRun) {
                    $user->email = $trimmedEmail;
                    $user->save();
                }
            }
        }
        
        if ($userFixes > 0) {
            $this->info("   ✅ Found {$userFixes} user(s) with whitespace issues");
            if ($dryRun) {
                $this->warn("   (Would fix {$userFixes} user(s) in live mode)");
            } else {
                $this->info("   ✅ Fixed {$userFixes} user(s)");
            }
        } else {
            $this->info("   ✅ No whitespace issues found in users table");
        }
        $this->newLine();

        // Fix staff table
        $this->info("Step 2: Checking staff table...");
        $staff = Staff::whereNotNull('work_email')->get();
        $staffFixes = 0;
        
        foreach ($staff as $s) {
            $originalEmail = $s->work_email;
            $trimmedEmail = trim($originalEmail);
            
            if ($originalEmail !== $trimmedEmail) {
                $this->line("   Staff ID {$s->id}: '{$originalEmail}' → '{$trimmedEmail}'");
                $staffFixes++;
                
                if (!$dryRun) {
                    $s->work_email = $trimmedEmail;
                    $s->save();
                }
            }
        }
        
        if ($staffFixes > 0) {
            $this->info("   ✅ Found {$staffFixes} staff member(s) with whitespace issues");
            if ($dryRun) {
                $this->warn("   (Would fix {$staffFixes} staff member(s) in live mode)");
            } else {
                $this->info("   ✅ Fixed {$staffFixes} staff member(s)");
            }
        } else {
            $this->info("   ✅ No whitespace issues found in staff table");
        }
        $this->newLine();

        // Summary
        $totalFixes = $userFixes + $staffFixes;
        if ($totalFixes > 0) {
            if ($dryRun) {
                $this->warn("📋 SUMMARY: Found {$totalFixes} email(s) with whitespace issues");
                $this->info("   Run without --dry-run to apply fixes: php artisan fix:email-whitespace");
            } else {
                $this->info("📋 SUMMARY: Fixed {$totalFixes} email(s) with whitespace issues");
                $this->info("   ✅ All email addresses have been cleaned");
            }
        } else {
            $this->info("📋 SUMMARY: No whitespace issues found. All emails are clean!");
        }

        return 0;
    }
}

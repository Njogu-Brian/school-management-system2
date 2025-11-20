<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:login {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test user login with detailed diagnostics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $this->info("🔍 Testing login for: {$email}");
        $this->newLine();

        // Check environment
        $env = config('app.env');
        $this->info("🌍 Environment: {$env}");
        $this->line("   Database: " . config('database.connections.mysql.database'));
        $this->newLine();

        // Step 1: Check if user exists (case-insensitive)
        $this->info("Step 1: Checking if user exists in users table...");
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        
        if (!$user) {
            $this->error("❌ User NOT FOUND in users table");
            $this->warn("   Searching with exact case...");
            $exactUser = User::where('email', $email)->first();
            if ($exactUser) {
                $this->warn("   ⚠️  Found user with different case: {$exactUser->email}");
            } else {
                // Check for similar emails
                $similarUsers = User::where('email', 'like', '%' . substr($email, 0, strpos($email, '@')) . '%')
                    ->limit(5)
                    ->get(['id', 'email', 'name']);
                if ($similarUsers->count() > 0) {
                    $this->warn("   Found similar emails:");
                    foreach ($similarUsers as $su) {
                        $this->line("      - {$su->email} (ID: {$su->id}, Name: {$su->name})");
                    }
                }
                
                // Check total user count
                $totalUsers = User::count();
                $this->line("   Total users in database: {$totalUsers}");
                
                if ($env !== 'production') {
                    $this->warn("   ⚠️  You're testing on {$env} environment.");
                    $this->warn("   This user might exist in production but not in {$env}.");
                    $this->warn("   To test on production, run: php artisan test:login {$email} {$password} --env=production");
                }
            }
        } else {
            $this->info("✅ User FOUND in users table");
            $this->line("   User ID: {$user->id}");
            $this->line("   Email in DB: {$user->email}");
            $this->line("   Name: {$user->name}");
            $this->line("   Created: {$user->created_at}");
        }
        $this->newLine();

        // Step 2: Check if staff exists with this email
        $this->info("Step 2: Checking if staff exists with this email...");
        $staff = Staff::whereRaw('LOWER(work_email) = ?', [strtolower($email)])->first();
        
        if (!$staff) {
            $this->error("❌ Staff NOT FOUND with this work_email");
            $exactStaff = Staff::where('work_email', $email)->first();
            if ($exactStaff) {
                $this->warn("   ⚠️  Found staff with different case: {$exactStaff->work_email}");
            } else {
                // Check for similar emails
                $emailPrefix = substr($email, 0, strpos($email, '@'));
                $similarStaff = Staff::where('work_email', 'like', '%' . $emailPrefix . '%')
                    ->limit(5)
                    ->get(['id', 'work_email', 'first_name', 'last_name', 'user_id']);
                if ($similarStaff->count() > 0) {
                    $this->warn("   Found similar work_emails:");
                    foreach ($similarStaff as $ss) {
                        $this->line("      - {$ss->work_email} (ID: {$ss->id}, Name: {$ss->first_name} {$ss->last_name}, User ID: {$ss->user_id})");
                    }
                }
                
                // Check total staff count
                $totalStaff = Staff::count();
                $this->line("   Total staff in database: {$totalStaff}");
                
                if ($env !== 'production') {
                    $this->warn("   ⚠️  You're testing on {$env} environment.");
                    $this->warn("   This staff member might exist in production but not in {$env}.");
                }
            }
        } else {
            $this->info("✅ Staff FOUND");
            $this->line("   Staff ID: {$staff->id}");
            $this->line("   Staff ID Number: {$staff->staff_id}");
            $this->line("   Work Email: {$staff->work_email}");
            $this->line("   Name: {$staff->first_name} {$staff->last_name}");
            $this->line("   User ID Link: " . ($staff->user_id ?? 'NULL'));
            
            // Check if user_id matches
            if ($user && $staff->user_id != $user->id) {
                $this->error("   ❌ MISMATCH: Staff user_id ({$staff->user_id}) doesn't match User id ({$user->id})");
            } elseif ($user && $staff->user_id == $user->id) {
                $this->info("   ✅ Staff user_id matches User id");
            } elseif (!$user && $staff->user_id) {
                $this->error("   ❌ Staff has user_id ({$staff->user_id}) but User doesn't exist!");
                $missingUser = User::find($staff->user_id);
                if (!$missingUser) {
                    $this->error("   ❌ User with ID {$staff->user_id} does NOT exist in users table!");
                }
            }
        }
        $this->newLine();

        // Step 3: Test password verification
        if ($user) {
            $this->info("Step 3: Testing password verification...");
            $this->line("   Testing password: {$password}");
            
            // Check if password matches
            $passwordMatches = Hash::check($password, $user->password);
            
            if ($passwordMatches) {
                $this->info("✅ Password MATCHES");
            } else {
                $this->error("❌ Password DOES NOT MATCH");
                
                // Try to identify what the password might be
                $this->warn("   Attempting to identify correct password...");
                
                if ($staff) {
                    // Check if password is the ID number
                    if ($staff->id_number) {
                        $idNumberMatch = Hash::check($staff->id_number, $user->password);
                        if ($idNumberMatch) {
                            $this->info("   ✅ Password is the ID Number: {$staff->id_number}");
                        } else {
                            $this->warn("   ❌ Password is NOT the ID Number");
                        }
                    }
                    
                    // Check if password is the staff_id
                    if ($staff->staff_id) {
                        $staffIdMatch = Hash::check($staff->staff_id, $user->password);
                        if ($staffIdMatch) {
                            $this->info("   ✅ Password is the Staff ID: {$staff->staff_id}");
                        }
                    }
                }
                
                // Show password hash for debugging
                $this->line("   Current password hash: {$user->password}");
            }
            
            // Check must_change_password flag
            if ($user->must_change_password) {
                $this->warn("   ⚠️  User must change password on next login");
            }
        } else {
            $this->error("❌ Cannot test password - User not found");
        }
        $this->newLine();

        // Step 4: Check email case sensitivity
        $this->info("Step 4: Email case sensitivity check...");
        if ($user) {
            $emailMatches = strtolower($user->email) === strtolower($email);
            if ($emailMatches) {
                $this->info("✅ Email case matches (case-insensitive)");
            } else {
                $this->warn("   ⚠️  Email case differs:");
                $this->line("      Input: {$email}");
                $this->line("      DB:    {$user->email}");
            }
        }
        $this->newLine();

        // Step 5: Test authentication attempt
        $this->info("Step 5: Testing Laravel authentication...");
        if ($user) {
            $credentials = [
                'email' => $user->email, // Use exact email from DB
                'password' => $password
            ];
            
            $authAttempt = auth()->attempt($credentials, false);
            
            if ($authAttempt) {
                $this->info("✅ Authentication SUCCESSFUL");
                auth()->logout(); // Clean up
            } else {
                $this->error("❌ Authentication FAILED");
                $this->line("   Credentials used:");
                $this->line("      Email: {$credentials['email']}");
                $this->line("      Password: {$password}");
            }
        } else {
            $this->error("❌ Cannot test authentication - User not found");
        }
        $this->newLine();

        // Step 6: Summary and recommendations
        $this->info("📋 SUMMARY:");
        $this->newLine();
        
        $issues = [];
        if (!$user) {
            $issues[] = "User does not exist in users table";
        }
        if (!$staff) {
            $issues[] = "Staff does not exist with this email";
        }
        if ($user && $staff && $staff->user_id != $user->id) {
            $issues[] = "Staff user_id doesn't match User id";
        }
        if ($user && !Hash::check($password, $user->password)) {
            $issues[] = "Password does not match";
        }
        
        if (empty($issues)) {
            $this->info("✅ All checks passed! Login should work.");
        } else {
            $this->error("❌ Issues found:");
            foreach ($issues as $i => $issue) {
                $this->line("   " . ($i + 1) . ". {$issue}");
            }
            $this->newLine();
            
            // Check if this is a local environment issue
            if ((!$user || !$staff) && $env !== 'production') {
                $this->warn("⚠️  IMPORTANT: You're testing on {$env} environment.");
                $this->warn("   The user/staff might exist in production but not locally.");
                $this->newLine();
                $this->info("🔧 To test on PRODUCTION:");
                $this->line("   1. SSH into production server");
                $this->line("   2. Navigate to project directory");
                $this->line("   3. Run: php artisan test:login {$email} {$password}");
                $this->newLine();
                $this->info("   OR use this command with production database:");
                $this->line("   php artisan test:login {$email} {$password} --database=production");
                $this->newLine();
            }
            
            $this->warn("💡 RECOMMENDATIONS:");
            
            if (!$user && $staff) {
                $this->line("   - Create user account for this staff member");
                $this->line("   - Link staff.user_id to the new user");
            }
            
            if ($user && !Hash::check($password, $user->password)) {
                $this->line("   - Reset password using: php artisan tinker");
                $this->line("     \$user = User::find({$user->id});");
                $this->line("     \$user->password = Hash::make('{$password}');");
                $this->line("     \$user->save();");
            }
            
            if ($user && $staff && $staff->user_id != $user->id) {
                $this->line("   - Fix staff.user_id: UPDATE staff SET user_id = {$user->id} WHERE id = {$staff->id};");
            }
        }

        return 0;
    }
}

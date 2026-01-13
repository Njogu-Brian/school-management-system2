<?php
/**
 * Quick Script to Assign Senior Teacher Role
 * 
 * Usage: php assign_senior_teacher.php
 * 
 * This script will:
 * 1. List all staff members
 * 2. Allow you to assign Senior Teacher role
 * 3. Verify the assignment
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Staff;
use Spatie\Permission\Models\Role;

echo "\n=================================================\n";
echo "     Senior Teacher Role Assignment Tool\n";
echo "=================================================\n\n";

// Check if Senior Teacher role exists
$seniorTeacherRole = Role::where('name', 'Senior Teacher')->first();
if (!$seniorTeacherRole) {
    echo "❌ ERROR: Senior Teacher role not found!\n";
    echo "Please run: php artisan db:seed --class=SeniorTeacherPermissionsSeeder\n\n";
    exit(1);
}

echo "✅ Senior Teacher role found with {$seniorTeacherRole->permissions->count()} permissions\n\n";

// Get all staff members with users
$staff = Staff::whereHas('user')
    ->with('user', 'position')
    ->where('status', 'Active')
    ->orderBy('first_name')
    ->get();

if ($staff->isEmpty()) {
    echo "❌ No active staff members found with user accounts.\n\n";
    exit(1);
}

echo "Active Staff Members:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-30s %-25s %-15s\n", "ID", "Name", "Position", "Current Role");
echo str_repeat("-", 80) . "\n";

foreach ($staff as $member) {
    $roles = $member->user->roles->pluck('name')->implode(', ');
    printf(
        "%-5s %-30s %-25s %-15s\n",
        $member->id,
        substr($member->full_name, 0, 30),
        substr($member->position->name ?? 'N/A', 0, 25),
        substr($roles ?: 'None', 0, 15)
    );
}

echo str_repeat("-", 80) . "\n\n";

// Interactive assignment
echo "Enter Staff ID to assign Senior Teacher role (or 'q' to quit): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$staffId = trim($line);

if (strtolower($staffId) === 'q') {
    echo "Exiting...\n\n";
    exit(0);
}

$selectedStaff = Staff::with('user')->find($staffId);

if (!$selectedStaff || !$selectedStaff->user) {
    echo "❌ Invalid Staff ID or staff member has no user account.\n\n";
    exit(1);
}

echo "\nSelected: {$selectedStaff->full_name}\n";
echo "Current roles: " . ($selectedStaff->user->roles->pluck('name')->implode(', ') ?: 'None') . "\n";
echo "\nConfirm assignment? (yes/no): ";
$confirm = trim(fgets($handle));

if (strtolower($confirm) !== 'yes') {
    echo "Assignment cancelled.\n\n";
    exit(0);
}

// Assign the role
try {
    $selectedStaff->user->assignRole('Senior Teacher');
    echo "\n✅ SUCCESS! Senior Teacher role assigned to {$selectedStaff->full_name}\n\n";
    
    // Show next steps
    echo "Next Steps:\n";
    echo "----------\n";
    echo "1. Log in to admin panel\n";
    echo "2. Navigate to: HR → Senior Teacher Assignments\n";
    echo "3. Click 'Manage Assignments' for {$selectedStaff->full_name}\n";
    echo "4. Assign supervised classrooms and staff\n\n";
    
    echo "Or use this direct URL:\n";
    echo "http://your-domain.com/admin/senior-teacher-assignments/{$selectedStaff->user_id}/edit\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

fclose($handle);



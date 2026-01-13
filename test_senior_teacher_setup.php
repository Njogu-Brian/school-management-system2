<?php
/**
 * Senior Teacher Setup Verification Script
 * 
 * Usage: php test_senior_teacher_setup.php
 * 
 * This script verifies:
 * 1. Database tables exist
 * 2. Role and permissions are set up
 * 3. Routes are registered
 * 4. Controllers exist
 * 5. Views exist
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "\n=================================================\n";
echo "   Senior Teacher Setup Verification Test\n";
echo "=================================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Database Tables
echo "Test 1: Database Tables\n";
echo str_repeat("-", 50) . "\n";

$tables = [
    'senior_teacher_classrooms',
    'senior_teacher_staff'
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "✅ Table '{$table}' exists\n";
        $passed++;
    } else {
        echo "❌ Table '{$table}' missing\n";
        $failed++;
    }
}
echo "\n";

// Test 2: Role
echo "Test 2: Senior Teacher Role\n";
echo str_repeat("-", 50) . "\n";

$role = Role::where('name', 'Senior Teacher')->first();
if ($role) {
    echo "✅ Senior Teacher role exists\n";
    echo "   Permissions: {$role->permissions->count()}\n";
    $passed++;
} else {
    echo "❌ Senior Teacher role not found\n";
    $failed++;
}
echo "\n";

// Test 3: Key Permissions
echo "Test 3: Key Permissions\n";
echo str_repeat("-", 50) . "\n";

$keyPermissions = [
    'dashboard.senior_teacher.view',
    'senior_teacher.supervisory_classes.view',
    'senior_teacher.supervised_staff.view',
    'finance.fee_balances.view',
];

foreach ($keyPermissions as $perm) {
    if (Permission::where('name', $perm)->exists()) {
        echo "✅ Permission '{$perm}' exists\n";
        $passed++;
    } else {
        echo "❌ Permission '{$perm}' missing\n";
        $failed++;
    }
}
echo "\n";

// Test 4: Routes
echo "Test 4: Routes Registration\n";
echo str_repeat("-", 50) . "\n";

$keyRoutes = [
    'senior_teacher.dashboard',
    'senior_teacher.supervised_classrooms',
    'senior_teacher.supervised_staff',
    'senior_teacher.students.index',
    'senior_teacher.fee_balances',
    'admin.senior_teacher_assignments.index',
];

foreach ($keyRoutes as $routeName) {
    if (Route::has($routeName)) {
        echo "✅ Route '{$routeName}' registered\n";
        $passed++;
    } else {
        echo "❌ Route '{$routeName}' missing\n";
        $failed++;
    }
}
echo "\n";

// Test 5: Controllers
echo "Test 5: Controllers\n";
echo str_repeat("-", 50) . "\n";

$controllers = [
    'App\Http\Controllers\SeniorTeacher\SeniorTeacherController',
    'App\Http\Controllers\Admin\SeniorTeacherAssignmentController',
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "✅ Controller '{$controller}' exists\n";
        $passed++;
    } else {
        echo "❌ Controller '{$controller}' missing\n";
        $failed++;
    }
}
echo "\n";

// Test 6: Views
echo "Test 6: Views\n";
echo str_repeat("-", 50) . "\n";

$views = [
    'senior_teacher.dashboard',
    'senior_teacher.supervised_classrooms',
    'senior_teacher.supervised_staff',
    'senior_teacher.students',
    'senior_teacher.fee_balances',
    'admin.senior_teacher_assignments.index',
];

foreach ($views as $view) {
    $viewPath = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
    if (file_exists($viewPath)) {
        echo "✅ View '{$view}' exists\n";
        $passed++;
    } else {
        echo "❌ View '{$view}' missing\n";
        $failed++;
    }
}
echo "\n";

// Test 7: User Model Methods
echo "Test 7: User Model Methods\n";
echo str_repeat("-", 50) . "\n";

$methods = [
    'supervisedClassrooms',
    'supervisedStaff',
    'isSupervisingClassroom',
    'isSupervisingStaff',
    'getSupervisedClassroomIds',
    'getSupervisedStaffIds',
];

$userModel = new \App\Models\User();
foreach ($methods as $method) {
    if (method_exists($userModel, $method)) {
        echo "✅ Method 'User::{$method}()' exists\n";
        $passed++;
    } else {
        echo "❌ Method 'User::{$method}()' missing\n";
        $failed++;
    }
}
echo "\n";

// Summary
echo "=================================================\n";
echo "                   SUMMARY\n";
echo "=================================================\n";
echo "Tests Passed: {$passed}\n";
echo "Tests Failed: {$failed}\n";
echo "Total Tests:  " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "🎉 ALL TESTS PASSED! Senior Teacher role is ready.\n\n";
    echo "Next Steps:\n";
    echo "1. Run: php assign_senior_teacher.php\n";
    echo "2. Or manually assign via: HR → Staff → Edit\n";
    echo "3. Configure assignments via: HR → Senior Teacher Assignments\n\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please review the errors above.\n\n";
    exit(1);
}



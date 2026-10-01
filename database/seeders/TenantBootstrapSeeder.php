<?php

namespace Database\Seeders;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Bootstrap an empty tenant ERP (no learners). Safe-ish to re-run for settings/admin.
 *
 * @param  array<string, mixed>|null  $options  When called from TenantProvisioningService
 */
class TenantBootstrapSeeder extends Seeder
{
    public function run($options = null): void
    {
        $opts = is_array($options) ? $options : [];

        $this->call([
            CanonicalRolesAndPermissionsSeeder::class,
            PaymentMethodSeeder::class,
            ChartOfAccountsSeeder::class,
            VoteheadCategorySeeder::class,
        ]);

        if (class_exists(\Database\Seeders\StatutoryRulesetSeeder::class)) {
            $this->call(\Database\Seeders\StatutoryRulesetSeeder::class);
        }

        $schoolName = $opts['school_name'] ?? 'New School';
        $settings = [
            'school_name' => $schoolName,
            'school_motto' => 'Learning that connects',
            'school_email' => $opts['school_email'] ?? 'info@school.test',
            'school_phone' => $opts['school_phone'] ?? null,
            'school_address' => 'Kenya',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'primary_color' => $opts['primary_color'] ?? '#0d6efd',
            'secondary_color' => $opts['secondary_color'] ?? '#198754',
            'show_finance_module' => 'true',
            'show_transport_module' => 'true',
        ];

        foreach ($settings as $key => $value) {
            if ($value === null) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($opts['seed_classes'] ?? true) {
            $this->seedClassesAndStreams();
        }

        $email = $opts['admin_email'] ?? null;
        if ($email) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $opts['admin_name'] ?? 'School Admin',
                    'phone_number' => $opts['admin_phone'] ?? null,
                    'password' => Hash::make($opts['admin_password'] ?? 'ChangeMe!123'),
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::findOrCreate('Super Admin', 'web');
            if (! $user->hasRole($role)) {
                $user->syncRoles([$role]);
            }

            if (Schema::hasTable('staff')) {
                $parts = preg_split('/\s+/', trim((string) ($opts['admin_name'] ?? 'School Admin')), 3) ?: ['School', 'Admin'];
                Staff::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'first_name' => $parts[0] ?? 'School',
                        'middle_name' => $parts[2] ?? null,
                        'last_name' => $parts[1] ?? ($parts[0] ?? 'Admin'),
                        'work_email' => $email,
                        'phone_number' => $opts['admin_phone'] ?? null,
                        'status' => 'active',
                        'employment_status' => 'active',
                    ]
                );
            }
        }
    }

    private function seedClassesAndStreams(): void
    {
        if (! Schema::hasTable('classrooms') || ! Schema::hasTable('streams')) {
            return;
        }

        $grades = [];
        for ($g = 1; $g <= 9; $g++) {
            $grades[] = "Grade {$g}";
        }

        foreach ($grades as $i => $name) {
            $classroom = Classroom::query()->firstOrCreate(
                ['name' => $name],
                [
                    'level' => $i + 1,
                    'campus' => $i < 6 ? 'lower' : 'upper',
                    'is_beginner' => $i === 0,
                ]
            );

            foreach (['A', 'B'] as $streamName) {
                Stream::query()->firstOrCreate(
                    ['name' => $streamName, 'classroom_id' => $classroom->id]
                );
            }
        }
    }
}

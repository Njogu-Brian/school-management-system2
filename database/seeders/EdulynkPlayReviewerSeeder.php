<?php

namespace Database\Seeders;

use App\Models\SchoolRegistry;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Play Console reviewer login for the combined Edulynk app (com.edulynk.app).
 *
 * School code RKS001 → Royal Kings tenant. Sign in with sales@edulynk.co.ke.
 *
 *   php artisan db:seed --class=EdulynkPlayReviewerSeeder
 *
 * Optional .env overrides:
 *   PLAY_EDULYNK_REVIEWER_EMAIL=sales@edulynk.co.ke
 *   PLAY_EDULYNK_REVIEWER_PASSWORD=...
 */
class EdulynkPlayReviewerSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureRoyalKingsSchoolCode();

        $email = strtolower(trim((string) (env('PLAY_EDULYNK_REVIEWER_EMAIL') ?: 'sales@edulynk.co.ke')));
        $password = (string) (env('PLAY_EDULYNK_REVIEWER_PASSWORD') ?: 'Sunshine@2025');
        $name = 'Edulynk Play Reviewer';

        $role = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );
        $permissions = Permission::query()->get();
        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'phone_number' => '0708225397',
                'parent_id' => null,
                'parent_profile_review_required' => false,
            ]
        );

        $user->syncRoles([$role]);

        Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'staff_id' => 'EDULYNK-PLAY',
                'first_name' => 'Edulynk',
                'last_name' => 'Reviewer',
                'work_email' => $email,
                'phone_number' => '0708225397',
                'status' => 'active',
                'employment_status' => 'active',
                'biometric_exempt' => true,
            ]
        );

        $this->command?->info('Edulynk Play reviewer account ready.');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['School code', 'RKS001'],
                ['School', 'Royal Kings Schools'],
                ['Username / email', $email],
                ['Password', $password],
                ['Role', 'Super Admin'],
                ['must_change_password', 'false'],
            ]
        );
        $this->command?->warn('Play Console → App content → App access: enter school code RKS001 first, then these credentials.');
    }

    private function ensureRoyalKingsSchoolCode(): void
    {
        if (! Schema::hasTable('schools_registry')) {
            $this->command?->warn('schools_registry table is missing. Run migrations, then re-seed so RKS001 can resolve.');

            return;
        }

        SchoolRegistry::query()->updateOrCreate(
            ['code' => 'RKS001'],
            [
                'name' => 'Royal Kings Schools',
                'slug' => 'royal-kings',
                'api_base_url' => 'https://erp.royalkingsschools.sc.ke/api',
                'status' => SchoolRegistry::STATUS_ACTIVE,
                'primary_color' => '#004A99',
                'contact_email' => 'sales@edulynk.co.ke',
                'contact_phone' => '+254708225397',
            ]
        );

        $this->command?->info('School code RKS001 is active for Royal Kings Schools.');
    }
}

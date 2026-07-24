<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates a dedicated Google Play Console reviewer account for Royal Kings Admin.
 *
 * For the Users app (Teacher / Parent), use GooglePlayUsersReviewerSeeder instead.
 *
 * Email uses the school domain so reviewers can sign in to Royal Kings Admin.
 * Run on production after deploy:
 *   php artisan db:seed --class=GooglePlayReviewerSeeder
 *
 * Optional overrides in .env:
 *   PLAY_REVIEWER_EMAIL=playreviewer@royalkingsschools.sc.ke
 *   PLAY_REVIEWER_PASSWORD=...
 */
class GooglePlayReviewerSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('PLAY_REVIEWER_EMAIL', 'playreviewer@royalkingsschools.sc.ke');
        $password = (string) env('PLAY_REVIEWER_PASSWORD', 'PlayReview@RKS2026!');
        $name = 'Google Play Reviewer';

        $role = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                // User model casts password as hashed — pass plaintext once
                'password' => $password,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);

        Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'staff_id' => 'PLAY-REVIEWER',
                'first_name' => 'Google Play',
                'last_name' => 'Reviewer',
                'work_email' => $email,
                'phone_number' => '0719396233',
                'status' => 'active',
                'employment_status' => 'active',
            ]
        );

        $this->command?->info('Google Play reviewer account ready.');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Name (Play Console)', 'Google Play reviewer admin account'],
                ['Username / email', $email],
                ['Password', $password],
                ['Role', 'Super Admin'],
                ['must_change_password', 'false'],
            ]
        );
        $this->command?->warn('Paste these credentials into Play Console → App content → Sign-in details.');
    }
}

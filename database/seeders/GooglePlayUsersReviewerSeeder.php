<?php

namespace Database\Seeders;

use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates a dedicated Google Play Console reviewer account for Royal Kings Users.
 *
 * Role is Teacher (Users-app role). If a parent_info with linked students exists,
 * the account is also linked for Home-mode browsing.
 *
 * Run on production after deploy:
 *   php artisan db:seed --class=GooglePlayUsersReviewerSeeder
 *
 * Optional overrides in .env:
 *   PLAY_USERS_REVIEWER_EMAIL=playusers@royalkingsschools.sc.ke
 *   PLAY_USERS_REVIEWER_PASSWORD=...
 */
class GooglePlayUsersReviewerSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('PLAY_USERS_REVIEWER_EMAIL', 'playusers@royalkingsschools.sc.ke');
        $password = (string) env('PLAY_USERS_REVIEWER_PASSWORD', 'UsersReview@RKS2026!');
        $name = 'Play Users Reviewer';

        $role = Role::firstOrCreate(
            ['name' => 'Teacher', 'guard_name' => 'web']
        );

        $parentId = null;
        $studentWithParent = Student::query()
            ->where('archive', 0)
            ->whereNotNull('parent_id')
            ->whereHas('parent')
            ->orderBy('id')
            ->first();

        if ($studentWithParent) {
            $parentId = (int) $studentWithParent->parent_id;
        } else {
            $parent = ParentInfo::query()->orderBy('id')->first();
            if ($parent) {
                $parentId = (int) $parent->id;
            }
        }

        $attrs = [
            'name' => $name,
            // User model casts password as hashed — pass plaintext once
            'password' => $password,
            'must_change_password' => false,
            'email_verified_at' => now(),
            'parent_profile_review_required' => false,
        ];

        if ($parentId) {
            $attrs['parent_id'] = $parentId;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            $attrs
        );

        $user->syncRoles([$role]);

        if ($parentId && class_exists(Role::class)) {
            $parentRole = Role::firstOrCreate(['name' => 'Parent', 'guard_name' => 'web']);
            if (! $user->hasRole($parentRole)) {
                $user->assignRole($parentRole);
            }
        }

        Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'staff_id' => 'PLAY-USERS',
                'first_name' => 'Play Users',
                'last_name' => 'Reviewer',
                'work_email' => $email,
                'phone_number' => '0719396234',
                'status' => 'active',
                'employment_status' => 'active',
            ]
        );

        $this->command?->info('Google Play Users reviewer account ready.');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['App', 'Royal Kings Users'],
                ['Name (Play Console)', 'Google Play reviewer — Users app (Teacher)'],
                ['Username / email', $email],
                ['Password', $password],
                ['Role', 'Teacher'.($parentId ? ' + Parent (dual)' : '')],
                ['parent_id', $parentId ? (string) $parentId : '(none — Work mode only)'],
                ['must_change_password', 'false'],
            ]
        );
        $this->command?->warn('Paste these credentials into Play Console → App content → App access.');
    }
}

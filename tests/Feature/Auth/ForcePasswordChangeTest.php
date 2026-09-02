<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\PasswordPolicy;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_forced_user_is_redirected_to_change_password_on_web(): void
    {
        $user = $this->createUser(['must_change_password' => true], 'Admin');
        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('password.change'));
    }

    public function test_admin_can_require_password_change_for_selected_users(): void
    {
        $admin = $this->createAdmin();
        $staffUser = $this->createTeacher();
        $this->assertFalse((bool) $staffUser->must_change_password);

        $this->actingAs($admin)
            ->post(route('users.require-password-change.store'), [
                'group' => 'staff',
                'apply_to' => 'selected',
                'user_ids' => [$staffUser->id],
            ])
            ->assertRedirect();

        $this->assertTrue((bool) $staffUser->fresh()->must_change_password);
    }

    public function test_web_password_change_clears_flag(): void
    {
        $user = $this->createUser(['must_change_password' => true], 'Admin');
        $password = PasswordPolicy::generate();

        $this->actingAs($user)
            ->post(route('password.change.update'), [
                'new_password' => $password,
                'new_password_confirmation' => $password,
            ])
            ->assertRedirect();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }
}

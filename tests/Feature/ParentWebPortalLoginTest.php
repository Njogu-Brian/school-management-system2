<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentWebPortalLoginTest extends TestCase
{
    public function test_login_page_shows_play_store_and_apk_links(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Get it on Google Play')
            ->assertSee('Download Android APK')
            ->assertSee('the iOS app is under development', false)
            ->assertSee(route('app.play-store', [], false), false)
            ->assertSee(route('app.apk', [], false), false);
    }

    public function test_play_store_page_opens_users_app_listing(): void
    {
        $this->get(route('app.play-store'))
            ->assertOk()
            ->assertSee('com.royalkingsschools.users')
            ->assertSee('https://play.google.com/store/apps/details?id=com.royalkingsschools.users');
    }

    public function test_parent_only_sign_in_stays_guest_and_shows_app_username(): void
    {
        $user = User::factory()->create([
            'email' => 'parent.portal@example.com',
            'phone_number' => '0712345678',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('Parent');

        $this->post('/login', [
            'identifier' => 'parent.portal@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('parent_use_app', true)
            ->assertSessionHas('parent_app_username');

        $this->assertGuest();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Use the Royal Kings Users app')
            ->assertDontSee('Forgot Password?');
    }

    public function test_staff_who_is_also_a_parent_can_still_use_the_portal(): void
    {
        $user = $this->createTeacher();
        $user->assignRole('Parent');
        $user->forceFill(['password' => Hash::make('password')])->save();

        $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->mustUseMobileApp());
    }

    public function test_authenticated_parent_is_kicked_off_web_routes(): void
    {
        $user = User::factory()->create([
            'email' => 'parent.web@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('Parent');

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('login'))
            ->assertSessionHas('parent_use_app', true);

        $this->assertGuest();
    }
}

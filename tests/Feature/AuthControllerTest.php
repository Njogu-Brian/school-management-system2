<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    /** @test */
    public function login_page_can_be_accessed()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function user_can_logout()
    {
        $user = $this->createUser();
        
        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_is_redirected_to_admin_dashboard()
    {
        $admin = $this->createUser([], 'Admin');
        
        $response = $this->actingAs($admin)->post('/login', [
            'email' => $admin->email,
            'password' => 'password'
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function authenticated_teacher_is_redirected_to_teacher_dashboard()
    {
        $teacher = $this->createTeacher();
        
        $response = $this->actingAs($teacher)->post('/login', [
            'email' => $teacher->email,
            'password' => 'password'
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
    }

    /** @test */
    public function login_requires_email()
    {
        $response = $this->post('/login', [
            'password' => 'password'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function login_requires_password()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function login_requires_valid_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password'
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}


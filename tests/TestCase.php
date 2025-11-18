<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure critical tables exist (settings and announcements for login page)
        if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_04_19_005721_create_settings_table.php',
                '--database' => 'testing'
            ]);
        }
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('announcements')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_04_23_204821_create_announcements_table.php',
                '--database' => 'testing'
            ]);
        }
        
        // Seed permissions if needed
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        // Create basic roles
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Teacher']);
        Role::firstOrCreate(['name' => 'Student']);
        Role::firstOrCreate(['name' => 'Parent']);
    }

    protected function createUser(array $attributes = [], string $role = null): User
    {
        $user = User::factory()->create($attributes);
        
        if ($role) {
            $user->assignRole($role);
        }
        
        return $user;
    }

    protected function createAdmin(): User
    {
        return $this->createUser([], 'Admin');
    }

    protected function createTeacher(): User
    {
        $user = $this->createUser([], 'Teacher');
        $staff = Staff::factory()->create(['user_id' => $user->id]);
        return $user;
    }

    protected function createStudent(array $attributes = []): Student
    {
        return Student::factory()->create($attributes);
    }

    protected function createClassroom(array $attributes = []): Classroom
    {
        return Classroom::factory()->create($attributes);
    }

    protected function createAcademicYear(array $attributes = []): AcademicYear
    {
        return AcademicYear::factory()->create($attributes);
    }

    protected function createTerm(array $attributes = []): Term
    {
        return Term::factory()->create($attributes);
    }

    protected function actingAsUser(User $user): self
    {
        return $this->actingAs($user);
    }
}

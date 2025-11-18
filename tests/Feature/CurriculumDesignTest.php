<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CurriculumDesign;
use App\Models\Academics\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CurriculumDesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'curriculum_designs.view']);
        Permission::create(['name' => 'curriculum_designs.create']);
        Permission::create(['name' => 'curriculum_designs.edit']);
        Permission::create(['name' => 'curriculum_designs.delete']);

        // Create roles
        $adminRole = Role::create(['name' => 'Admin']);
        $teacherRole = Role::create(['name' => 'Teacher']);

        // Assign permissions
        $adminRole->givePermissionTo([
            'curriculum_designs.view',
            'curriculum_designs.create',
            'curriculum_designs.edit',
            'curriculum_designs.delete',
        ]);

        $teacherRole->givePermissionTo([
            'curriculum_designs.view',
        ]);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole($teacherRole);
    }

    /** @test */
    public function admin_can_view_curriculum_designs_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('academics.curriculum-designs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('academics.curriculum_designs.index');
    }

    /** @test */
    public function teacher_can_view_curriculum_designs_index()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('academics.curriculum-designs.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_create_form()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('academics.curriculum-designs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('academics.curriculum_designs.create');
    }

    /** @test */
    public function teacher_cannot_access_create_form_without_permission()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('academics.curriculum-designs.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_upload_curriculum_design()
    {
        Storage::fake('private');

        $subject = Subject::factory()->create();

        $file = UploadedFile::fake()->create('curriculum.pdf', 100); // 100KB

        $response = $this->actingAs($this->admin)
            ->post(route('academics.curriculum-designs.store'), [
                'title' => 'Test Curriculum Design',
                'subject_id' => $subject->id,
                'class_level' => 'Grade 4',
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('curriculum_designs', [
            'title' => 'Test Curriculum Design',
            'subject_id' => $subject->id,
            'class_level' => 'Grade 4',
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function upload_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('academics.curriculum-designs.store'), []);

        $response->assertSessionHasErrors(['title', 'file']);
    }

    /** @test */
    public function upload_validates_file_type()
    {
        Storage::fake('private');

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($this->admin)
            ->post(route('academics.curriculum-designs.store'), [
                'title' => 'Test',
                'file' => $file,
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    /** @test */
    public function admin_can_view_curriculum_design()
    {
        $design = CurriculumDesign::factory()->create([
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('academics.curriculum-designs.show', $design));

        $response->assertStatus(200);
        $response->assertViewIs('academics.curriculum_designs.show');
    }

    /** @test */
    public function admin_can_edit_curriculum_design()
    {
        $design = CurriculumDesign::factory()->create([
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('academics.curriculum-designs.update', $design), [
                'title' => 'Updated Title',
                'class_level' => 'Grade 5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('curriculum_designs', [
            'id' => $design->id,
            'title' => 'Updated Title',
            'class_level' => 'Grade 5',
        ]);
    }

    /** @test */
    public function admin_can_delete_curriculum_design()
    {
        Storage::fake('private');
        
        $design = CurriculumDesign::factory()->create([
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('academics.curriculum-designs.destroy', $design));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('curriculum_designs', [
            'id' => $design->id,
        ]);
    }

    /** @test */
    public function teacher_cannot_delete_curriculum_design()
    {
        $design = CurriculumDesign::factory()->create([
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('academics.curriculum-designs.destroy', $design));

        $response->assertStatus(403);
    }
}


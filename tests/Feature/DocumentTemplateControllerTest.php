<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DocumentTemplate;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    public function test_admin_can_view_templates_index(): void
    {
        $user = User::factory()->create();
        DocumentTemplate::create([
            'name' => 'Template 1',
            'slug' => 'template-1',
            'type' => 'certificate',
            'template_html' => '<h1>Template 1</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        DocumentTemplate::create([
            'name' => 'Template 2',
            'slug' => 'template-2',
            'type' => 'transcript',
            'template_html' => '<h1>Template 2</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        DocumentTemplate::create([
            'name' => 'Template 3',
            'slug' => 'template-3',
            'type' => 'id_card',
            'template_html' => '<h1>Template 3</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-templates.index'));

        $response->assertStatus(200);
        $response->assertViewIs('documents.templates.index');
    }

    public function test_admin_can_create_template(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('document-templates.create'));

        $response->assertStatus(200);
        $response->assertViewIs('documents.templates.create');
    }

    public function test_admin_can_store_template(): void
    {
        $data = [
            'name' => 'Test Certificate',
            'type' => 'certificate',
            'template_html' => '<h1>Certificate for {{student_name}}</h1>',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('document-templates.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Test Certificate',
            'type' => 'certificate',
        ]);
    }

    public function test_admin_can_view_template(): void
    {
        $user = User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'type' => 'certificate',
            'template_html' => '<h1>Test</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-templates.show', $template));

        $response->assertStatus(200);
        $response->assertViewIs('documents.templates.show');
    }

    public function test_admin_can_update_template(): void
    {
        $user = User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Original Template',
            'slug' => 'original-template',
            'type' => 'certificate',
            'template_html' => '<h1>Original</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $data = [
            'name' => 'Updated Certificate',
            'type' => 'certificate',
            'template_html' => '<h1>Updated Certificate</h1>',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('document-templates.update', $template), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'id' => $template->id,
            'name' => 'Updated Certificate',
        ]);
    }

    public function test_admin_can_delete_template(): void
    {
        $user = User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Delete Template',
            'slug' => 'delete-template',
            'type' => 'certificate',
            'template_html' => '<h1>Delete</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('document-templates.destroy', $template));

        $response->assertRedirect();
        $this->assertSoftDeleted('document_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_admin_can_generate_document_for_student(): void
    {
        $user = User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Generate Template',
            'slug' => 'generate-template',
            'type' => 'certificate',
            'template_html' => '<h1>{{student_name}}</h1>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $classroom = \App\Models\Academics\Classroom::firstOrCreate(['name' => 'Grade 1']);
        $student = Student::create([
            'first_name' => 'Test',
            'last_name' => 'Student',
            'admission_number' => 'ST003',
            'classroom_id' => $classroom->id,
            'gender' => 'male',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-templates.generate.student', [$template, $student]));

        $response->assertRedirect();
        $this->assertDatabaseHas('generated_documents', [
            'template_id' => $template->id,
            'student_id' => $student->id,
        ]);
    }
}


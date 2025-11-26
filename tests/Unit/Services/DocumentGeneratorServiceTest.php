<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\DocumentTemplate;
use App\Models\Student;
use App\Models\Staff;
use App\Services\DocumentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DocumentGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentGeneratorService();
        Storage::fake('public');
    }

    public function test_can_replace_placeholders_in_template(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'type' => 'certificate',
            'template_html' => '<h1>{{student_name}}</h1><p>{{student_admission_number}}</p>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $classroom = \App\Models\Academics\Classroom::firstOrCreate(['name' => 'Grade 1']);
        $student = Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'ST001',
            'classroom_id' => $classroom->id,
            'gender' => 'male',
        ]);

        $data = [
            'student_name' => $student->full_name,
            'student_admission_number' => $student->admission_number,
        ];

        $html = $this->invokeMethod($this->service, 'replacePlaceholders', [
            $template->template_html,
            $data,
        ]);

        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('ST001', $html);
        $this->assertStringNotContainsString('{{student_name}}', $html);
    }

    public function test_can_generate_document_for_student(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Certificate Template',
            'slug' => 'certificate-template',
            'template_html' => '<html><body><h1>Certificate for {{student_name}}</h1></body></html>',
            'type' => 'certificate',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $classroom = \App\Models\Academics\Classroom::firstOrCreate(['name' => 'Grade 1']);
        $student = Student::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'admission_number' => 'ST002',
            'classroom_id' => $classroom->id,
            'gender' => 'female',
        ]);

        $generated = $this->service->generate($template, [], $student);

        $this->assertInstanceOf(\App\Models\GeneratedDocument::class, $generated);
        $this->assertEquals($template->id, $generated->template_id);
        $this->assertEquals($student->id, $generated->student_id);
        $this->assertNotNull($generated->pdf_path);
        $this->assertNotNull($generated->filename);
    }

    public function test_can_generate_document_for_staff(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'ID Card Template',
            'slug' => 'id-card-template',
            'template_html' => '<html><body><h1>ID Card for {{staff_name}}</h1></body></html>',
            'type' => 'id_card',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $staff = Staff::create([
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'staff_id' => 'STF001',
            'work_email' => 'john@example.com',
        ]);

        $generated = $this->service->generate($template, [], null, $staff);

        $this->assertInstanceOf(\App\Models\GeneratedDocument::class, $generated);
        $this->assertEquals($template->id, $generated->template_id);
        $this->assertEquals($staff->id, $generated->staff_id);
        $this->assertNotNull($generated->pdf_path);
    }

    public function test_removes_unreplaced_placeholders(): void
    {
        $user = \App\Models\User::factory()->create();
        $template = DocumentTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template-2',
            'type' => 'custom',
            'template_html' => '<p>{{student_name}} {{unknown_placeholder}}</p>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $data = ['student_name' => 'Test Student'];

        $html = $this->invokeMethod($this->service, 'replacePlaceholders', [
            $template->template_html,
            $data,
        ]);

        $this->assertStringContainsString('Test Student', $html);
        $this->assertStringNotContainsString('{{unknown_placeholder}}', $html);
    }

    /**
     * Helper method to invoke protected/private methods
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}


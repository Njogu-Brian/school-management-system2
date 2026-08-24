<?php

namespace Tests\Feature\Api;

use App\Models\Academics\Stream;
use App\Models\RequirementTemplate;
use App\Models\RequirementType;
use App\Models\StudentRequirement;
use App\Models\Term;
use App\Services\AcademicCalendarService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTeacherRequirementsTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_templates_for_student_succeed_when_no_collection_row_exists(): void
    {
        $admin = $this->createAdmin();
        [$year, $term] = $this->seedCurrentTerm();

        $classroom = $this->createClassroom();
        $stream = Stream::factory()->create(['classroom_id' => $classroom->id]);
        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'stream_id' => $stream->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        $type = RequirementType::create([
            'name' => 'Exercise books',
            'category' => 'stationery',
            'is_active' => true,
        ]);

        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'brand' => 'Karatasi',
            'quantity_per_student' => 4,
            'unit' => 'piece',
            'student_type' => 'existing',
            'custody_type' => 'parent_custody',
            'is_active' => true,
        ]);

        $this->assertSame(0, StudentRequirement::query()->where('student_id', $student->id)->count());

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/teacher/requirements/students/{$student->id}/templates");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.items.0.template_id', $template->id)
            ->assertJsonPath('data.items.0.requirement_id', null)
            ->assertJsonPath('data.items.0.name', 'Exercise books')
            ->assertJsonPath('data.items.0.status', 'pending')
            ->assertJsonPath('data.items.0.notes', null)
            ->assertJsonPath('data.items.0.quantity_required', 4);
    }

    public function test_templates_include_notes_when_a_collection_row_exists(): void
    {
        $admin = $this->createAdmin();
        [$year, $term] = $this->seedCurrentTerm();

        $classroom = $this->createClassroom();
        $stream = Stream::factory()->create(['classroom_id' => $classroom->id]);
        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'stream_id' => $stream->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        $type = RequirementType::create([
            'name' => 'Pencils',
            'category' => 'stationery',
            'is_active' => true,
        ]);

        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_per_student' => 2,
            'unit' => 'piece',
            'student_type' => 'both',
            'custody_type' => 'parent_custody',
            'is_active' => true,
        ]);

        $requirement = StudentRequirement::create([
            'student_id' => $student->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 2,
            'expected_quantity' => 2,
            'quantity_collected' => 1,
            'status' => 'partial',
            'notes' => 'Brought one pack',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/teacher/requirements/students/{$student->id}/templates")
            ->assertOk()
            ->assertJsonPath('data.items.0.requirement_id', $requirement->id)
            ->assertJsonPath('data.items.0.status', 'partial')
            ->assertJsonPath('data.items.0.notes', 'Brought one pack')
            ->assertJsonPath('data.items.0.quantity_collected', 1);
    }

    /**
     * @return array{0: \App\Models\AcademicYear, 1: Term}
     */
    private function seedCurrentTerm(): array
    {
        $year = $this->createAcademicYear(['year' => (int) now()->year, 'is_active' => true]);
        $term = Term::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Current Term',
            'is_current' => true,
            'opening_date' => now()->subMonth()->toDateString(),
            'closing_date' => now()->addMonth()->toDateString(),
        ]);
        AcademicCalendarService::flush();

        return [$year, $term];
    }
}

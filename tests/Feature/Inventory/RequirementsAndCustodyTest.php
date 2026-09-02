<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\RequirementTemplate;
use App\Models\RequirementType;
use App\Models\StudentRequirement;
use App\Models\Term;
use App\Services\AcademicCalendarService;
use App\Services\Inventory\RequirementCustodyService;
use App\Services\RequirementsFulfilmentReportService;
use Tests\TestCase;

class RequirementsAndCustodyTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_verification_only_receipts_do_not_increase_inventory(): void
    {
        $admin = $this->createAdmin();
        [$year, $term] = $this->seedCurrentTerm();
        $classroom = $this->createClassroom();
        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $type = RequirementType::create(['name' => 'Tissues', 'category' => 'hygiene', 'is_active' => true]);
        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_per_student' => 10,
            'unit' => 'pack',
            'student_type' => 'both',
            'is_verification_only' => true,
            'leave_with_teacher' => false,
            'custody_type' => 'parent_custody',
            'is_active' => true,
        ]);

        $requirement = StudentRequirement::create([
            'student_id' => $student->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 10,
            'expected_quantity' => 10,
            'quantity_collected' => 0,
            'status' => 'pending',
        ]);
        $requirement->load('requirementTemplate.requirementType', 'student.classroom');
        $requirement->recordReceipt(10, $admin->id);

        $this->assertSame(0, InventoryItem::query()->count());
        $this->assertSame(0, InventoryTransaction::query()->count());
        $this->assertSame('complete', $requirement->fresh()->status);
    }

    public function test_switching_collect_to_verify_removes_learner_stock_from_inventory(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        [$year, $term] = $this->seedCurrentTerm();
        $classroom = $this->createClassroom();
        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $type = RequirementType::create(['name' => 'Tissues', 'category' => 'hygiene', 'is_active' => true]);
        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_per_student' => 10,
            'unit' => 'pack',
            'student_type' => 'both',
            'is_verification_only' => false,
            'leave_with_teacher' => true,
            'custody_type' => 'school_custody',
            'is_active' => true,
        ]);

        $requirement = StudentRequirement::create([
            'student_id' => $student->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 10,
            'expected_quantity' => 10,
            'quantity_collected' => 0,
            'status' => 'pending',
        ]);
        $requirement->load('requirementTemplate.requirementType', 'student.classroom');
        $requirement->recordReceipt(10, $admin->id);

        $item = InventoryItem::query()->where('name', 'Tissues')->first();
        $this->assertNotNull($item);
        $this->assertEquals(10, (float) $item->quantity);

        app(RequirementCustodyService::class)->syncTemplate($template, $template->toArray(), true, false);

        $this->assertEquals(0, (float) $item->fresh()->quantity);
        $this->assertTrue($template->fresh()->is_verification_only);
        $this->assertFalse($template->fresh()->addsToSchoolInventory());
    }

    public function test_fulfilment_report_groups_complete_partial_and_none(): void
    {
        [$year, $term] = $this->seedCurrentTerm();
        $classroom = $this->createClassroom();
        $completeStudent = $this->createStudent([
            'classroom_id' => $classroom->id,
            'first_name' => 'Complete',
            'last_name' => 'Learner',
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $partialStudent = $this->createStudent([
            'classroom_id' => $classroom->id,
            'first_name' => 'Partial',
            'last_name' => 'Learner',
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $noneStudent = $this->createStudent([
            'classroom_id' => $classroom->id,
            'first_name' => 'None',
            'last_name' => 'Learner',
            'archive' => 0,
            'is_alumni' => false,
        ]);

        $type = RequirementType::create(['name' => 'Exercise books', 'category' => 'stationery', 'is_active' => true]);
        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_per_student' => 4,
            'unit' => 'piece',
            'student_type' => 'both',
            'is_active' => true,
        ]);

        StudentRequirement::create([
            'student_id' => $completeStudent->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 4,
            'expected_quantity' => 4,
            'quantity_collected' => 4,
            'status' => 'complete',
        ]);
        StudentRequirement::create([
            'student_id' => $partialStudent->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 4,
            'expected_quantity' => 4,
            'quantity_collected' => 1,
            'status' => 'partial',
        ]);

        $report = app(RequirementsFulfilmentReportService::class)->build($year->id, $term->id, $classroom->id, null);

        $this->assertSame(1, $report['summary']['complete']);
        $this->assertSame(1, $report['summary']['partial']);
        $this->assertSame(1, $report['summary']['none']);
        $this->assertSame('Complete Learner', $report['complete'][0]['name']);
        $this->assertNotEmpty($report['partial'][0]['brought_items']);
        $this->assertNotEmpty($report['none'][0]['outstanding_items']);
        $this->assertSame(4.0, $report['none'][0]['outstanding_items'][0]['outstanding']);
    }

    public function test_receipts_report_excludes_learner_stock_after_verify_switch(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        [$year, $term] = $this->seedCurrentTerm();
        $classroom = $this->createClassroom();
        $student = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $type = RequirementType::create(['name' => 'Tissues', 'category' => 'hygiene', 'is_active' => true]);
        $template = RequirementTemplate::create([
            'requirement_type_id' => $type->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_per_student' => 10,
            'unit' => 'pack',
            'student_type' => 'both',
            'is_verification_only' => false,
            'leave_with_teacher' => true,
            'custody_type' => 'school_custody',
            'is_active' => true,
        ]);

        $requirement = StudentRequirement::create([
            'student_id' => $student->id,
            'requirement_template_id' => $template->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'quantity_required' => 10,
            'expected_quantity' => 10,
            'quantity_collected' => 0,
            'status' => 'pending',
        ]);
        $requirement->load('requirementTemplate.requirementType', 'student.classroom');
        $requirement->recordReceipt(10, $admin->id);

        $before = app(\App\Services\InventoryReceiptsReportService::class)->build(
            now()->subDay()->toDateString(),
            now()->toDateString(),
        );
        $this->assertSame(10.0, $before['rows'][0]['from_learners']);

        app(RequirementCustodyService::class)->syncTemplate($template, $template->toArray(), true, false);

        $after = app(\App\Services\InventoryReceiptsReportService::class)->build(
            now()->subDay()->toDateString(),
            now()->toDateString(),
        );
        $this->assertSame([], $after['rows']);
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

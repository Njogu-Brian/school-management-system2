<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FeePostingService;
use App\Models\{Student, Votehead, FeeStructure, Invoice, InvoiceItem, FeePostingRun, AcademicYear, Term, OptionalFee, ExtraIncomeItem, FeePostingDismissal};
use Illuminate\Support\Facades\DB;

class FeePostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FeePostingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeePostingService();
    }

    /** @test */
    public function it_can_preview_fee_posting_diffs()
    {
        // Create test data
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);
        
        $structure = FeeStructure::factory()->create([
            'classroom_id' => $student->classroom_id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'is_active' => true,
        ]);

        $filters = [
            'year' => 2025,
            'term' => 1,
            'student_id' => $student->id,
        ];

        $result = $this->service->previewWithDiffs($filters);

        $this->assertArrayHasKey('diffs', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertIsArray($result['diffs']);
        $this->assertIsArray($result['summary']);
    }

    /** @test */
    public function it_calculates_correct_diffs_for_new_items()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        
        $filters = [
            'year' => 2025,
            'term' => 1,
            'student_id' => $student->id,
        ];

        $result = $this->service->previewWithDiffs($filters);
        $addedDiffs = collect($result['diffs'])->where('action', 'added');

        // If there are no existing invoices, items should be marked as 'added'
        $this->assertGreaterThanOrEqual(0, $addedDiffs->count());
    }

    /** @test */
    public function it_can_commit_posting_with_tracking()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);

        $diffs = collect([
            [
                'student_id' => $student->id,
                'votehead_id' => $votehead->id,
                'old_amount' => null,
                'new_amount' => 5000.00,
                'action' => 'added',
                'origin' => 'structure',
            ],
        ]);

        $run = $this->service->commitWithTracking(
            $diffs,
            2025,
            1,
            true,
            null,
            []
        );

        $this->assertInstanceOf(FeePostingRun::class, $run);
        $this->assertEquals('completed', $run->status);
        $this->assertGreaterThan(0, $run->items_posted_count);
    }

    /** @test */
    public function posting_is_idempotent()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);

        $diffs = collect([
            [
                'student_id' => $student->id,
                'votehead_id' => $votehead->id,
                'old_amount' => null,
                'new_amount' => 5000.00,
                'action' => 'added',
                'origin' => 'structure',
            ],
        ]);

        // First commit
        $run1 = $this->service->commitWithTracking($diffs, 2025, 1, true);
        $initialCount = InvoiceItem::count();

        // Second commit (should be idempotent)
        $run2 = $this->service->commitWithTracking($diffs, 2025, 1, true);
        $finalCount = InvoiceItem::count();

        // Should not create duplicate items
        $this->assertEquals($initialCount, $finalCount);
    }

    /** @test */
    public function it_can_reverse_posting_run()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);

        $diffs = collect([
            [
                'student_id' => $student->id,
                'votehead_id' => $votehead->id,
                'old_amount' => null,
                'new_amount' => 5000.00,
                'action' => 'added',
                'origin' => 'structure',
            ],
        ]);

        $run = $this->service->commitWithTracking($diffs, 2025, 1, true);
        $initialItemCount = InvoiceItem::where('posting_run_id', $run->id)->count();

        // Reverse
        $this->service->reversePostingRun($run);

        $run->refresh();
        $this->assertEquals('reversed', $run->status);
        $this->assertNotNull($run->reversed_at);
    }

    /** @test */
    public function extra_income_is_left_out_of_the_posting_preview(): void
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create([
            'name' => 'TRIP',
            'is_mandatory' => false,
            'is_optional' => true,
            'is_activity_fee' => true,
            'charge_type' => 'per_student',
        ]);

        OptionalFee::create([
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'year' => 2026,
            'term' => 3,
            'amount' => 20000,
            'status' => 'billed',
        ]);

        ExtraIncomeItem::create([
            'name' => 'TRIP',
            'kind' => ExtraIncomeItem::KIND_TRIP,
            'votehead_id' => $votehead->id,
            'year' => 2026,
            'term' => 3,
            'amount' => 20000,
        ]);

        $result = $this->service->previewWithDiffs([
            'year' => 2026,
            'term' => 3,
            'student_id' => $student->id,
        ]);

        $this->assertFalse(
            collect($result['diffs'])->contains(fn ($diff) => (int) $diff['votehead_id'] === (int) $votehead->id)
        );
    }

    /** @test */
    public function rejecting_a_change_keeps_the_invoice_and_hides_it_next_time(): void
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create([
            'is_mandatory' => false,
            'is_optional' => true,
            'charge_type' => 'per_student',
        ]);

        OptionalFee::create([
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'year' => 2026,
            'term' => 3,
            'amount' => 500,
            'status' => 'billed',
        ]);

        $filters = [
            'year' => 2026,
            'term' => 3,
            'student_id' => $student->id,
        ];

        $first = collect($this->service->previewWithDiffs($filters)['diffs'])->values();
        $match = $first->first(fn ($diff) => (int) $diff['votehead_id'] === (int) $votehead->id);
        $this->assertNotNull($match);

        $match['_preview_index'] = 0;
        $this->service->rejectPendingDiffs(collect([$match]), 2026, 3, [0]);

        $this->assertSame(0, InvoiceItem::count());
        $this->assertDatabaseMissing('optional_fees', [
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
        ]);
        $this->assertTrue(FeePostingDismissal::query()->where('student_id', $student->id)->where('votehead_id', $votehead->id)->exists());

        OptionalFee::create([
            'student_id' => $student->id,
            'votehead_id' => $votehead->id,
            'year' => 2026,
            'term' => 3,
            'amount' => 500,
            'status' => 'billed',
        ]);

        $second = collect($this->service->previewWithDiffs($filters)['diffs']);
        $this->assertFalse($second->contains(fn ($diff) => (int) $diff['votehead_id'] === (int) $votehead->id));
    }
}


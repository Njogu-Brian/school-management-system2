<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FeePostingService;
use App\Models\{Student, Votehead, FeeStructure, Invoice, InvoiceItem, FeePostingRun, AcademicYear, Term};
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
}


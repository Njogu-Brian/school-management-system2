<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\StudentCategory;
use App\Models\Votehead;
use App\Services\FeeStructureYearCopyService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeeStructureYearCopyServiceTest extends TestCase
{
    private FeeStructureYearCopyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeeStructureYearCopyService::class);
    }

    #[Test]
    public function it_copies_fee_structures_from_previous_year_as_independent_rows(): void
    {
        $year2026 = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::create(['year' => 2027, 'is_active' => false]);

        $classroom = Classroom::create(['name' => 'GRADE 1']);
        $category = StudentCategory::create(['name' => 'GENERAL']);
        $votehead = Votehead::create([
            'name' => 'TUITION FEES',
            'code' => 'TF001',
            'charge_type' => 'per_student',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        $source = FeeStructure::create([
            'classroom_id' => $classroom->id,
            'student_category_id' => $category->id,
            'academic_year_id' => $year2026->id,
            'year' => 2026,
            'name' => 'GRADE 1 GENERAL',
            'is_active' => true,
            'term_id' => null,
            'stream_id' => null,
        ]);

        FeeCharge::create([
            'fee_structure_id' => $source->id,
            'votehead_id' => $votehead->id,
            'term' => 1,
            'amount' => 15000,
        ]);
        FeeCharge::create([
            'fee_structure_id' => $source->id,
            'votehead_id' => $votehead->id,
            'term' => 2,
            'amount' => 14000,
        ]);
        FeeCharge::create([
            'fee_structure_id' => $source->id,
            'votehead_id' => $votehead->id,
            'term' => 3,
            'amount' => 13000,
        ]);

        $result = $this->service->copyFromPreviousYear($year2027);

        $this->assertSame(2026, $result['from']);
        $this->assertSame(2027, $result['to']);
        $this->assertSame(1, $result['copied']);
        $this->assertSame(0, $result['skipped']);

        $cloned = FeeStructure::query()
            ->where('academic_year_id', $year2027->id)
            ->where('classroom_id', $classroom->id)
            ->where('student_category_id', $category->id)
            ->first();

        $this->assertNotNull($cloned);
        $this->assertNotSame($source->id, $cloned->id);
        $this->assertSame($source->id, $cloned->parent_structure_id);
        $this->assertSame(2027, (int) $cloned->year);
        $this->assertCount(3, $cloned->charges);

        $this->assertEquals(15000, (float) $cloned->charges->firstWhere('term', 1)->amount);
        $this->assertEquals(14000, (float) $cloned->charges->firstWhere('term', 2)->amount);
        $this->assertEquals(13000, (float) $cloned->charges->firstWhere('term', 3)->amount);

        // Independence: editing 2027 must not change 2026.
        $cloned->charges()->where('term', 1)->update(['amount' => 99999]);
        $source->refresh()->load('charges');
        $this->assertEquals(15000, (float) $source->charges->firstWhere('term', 1)->amount);
    }

    #[Test]
    public function it_does_not_overwrite_target_structures_that_already_have_charges(): void
    {
        $year2026 = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::create(['year' => 2027, 'is_active' => false]);

        $classroom = Classroom::create(['name' => 'GRADE 2']);
        $category = StudentCategory::create(['name' => 'STAFF']);
        $votehead = Votehead::create([
            'name' => 'Assessment fee',
            'code' => 'AF001',
            'charge_type' => 'per_student',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        $source = FeeStructure::create([
            'classroom_id' => $classroom->id,
            'student_category_id' => $category->id,
            'academic_year_id' => $year2026->id,
            'year' => 2026,
            'is_active' => true,
        ]);
        FeeCharge::create([
            'fee_structure_id' => $source->id,
            'votehead_id' => $votehead->id,
            'term' => 1,
            'amount' => 10000,
        ]);

        $existing = FeeStructure::create([
            'classroom_id' => $classroom->id,
            'student_category_id' => $category->id,
            'academic_year_id' => $year2027->id,
            'year' => 2027,
            'is_active' => true,
        ]);
        FeeCharge::create([
            'fee_structure_id' => $existing->id,
            'votehead_id' => $votehead->id,
            'term' => 1,
            'amount' => 500,
        ]);

        $result = $this->service->copyYear($year2026, $year2027);

        $this->assertSame(0, $result['copied']);
        $this->assertSame(1, $result['skipped']);
        $this->assertEquals(500, (float) $existing->fresh()->charges()->where('term', 1)->value('amount'));
    }

    #[Test]
    public function it_fills_empty_target_structures_from_source(): void
    {
        $year2026 = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::create(['year' => 2027, 'is_active' => false]);

        $classroom = Classroom::create(['name' => 'GRADE 3']);
        $category = StudentCategory::create(['name' => 'BOARDING']);
        $votehead = Votehead::create([
            'name' => 'Boarding Fee',
            'code' => 'BF001',
            'charge_type' => 'per_student',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        $source = FeeStructure::create([
            'classroom_id' => $classroom->id,
            'student_category_id' => $category->id,
            'academic_year_id' => $year2026->id,
            'year' => 2026,
            'is_active' => true,
        ]);
        FeeCharge::create([
            'fee_structure_id' => $source->id,
            'votehead_id' => $votehead->id,
            'term' => 2,
            'amount' => 8000,
        ]);

        // Blank 2027 structure (what the manage UI shows as 0.00)
        FeeStructure::create([
            'classroom_id' => $classroom->id,
            'student_category_id' => $category->id,
            'academic_year_id' => $year2027->id,
            'year' => 2027,
            'is_active' => true,
        ]);

        $result = $this->service->copyYear($year2026, $year2027);

        $this->assertSame(0, $result['copied']);
        $this->assertSame(1, $result['filled']);

        $target = FeeStructure::query()
            ->where('academic_year_id', $year2027->id)
            ->where('classroom_id', $classroom->id)
            ->where('student_category_id', $category->id)
            ->first();

        $this->assertCount(1, $target->charges);
        $this->assertEquals(8000, (float) $target->charges->first()->amount);
    }

    #[Test]
    public function it_copies_each_student_category_separately(): void
    {
        $year2026 = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::create(['year' => 2027, 'is_active' => false]);
        $classroom = Classroom::create(['name' => 'GRADE 4']);
        $general = StudentCategory::create(['name' => 'GENERAL']);
        $staff = StudentCategory::create(['name' => 'STAFF']);
        $votehead = Votehead::create([
            'name' => 'Tuition',
            'code' => 'TU002',
            'charge_type' => 'per_student',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        foreach ([$general, $staff] as $index => $category) {
            $structure = FeeStructure::create([
                'classroom_id' => $classroom->id,
                'student_category_id' => $category->id,
                'academic_year_id' => $year2026->id,
                'year' => 2026,
                'is_active' => true,
            ]);
            FeeCharge::create([
                'fee_structure_id' => $structure->id,
                'votehead_id' => $votehead->id,
                'term' => 1,
                'amount' => ($index + 1) * 1000,
            ]);
        }

        $this->service->copyFromPreviousYear($year2027);

        $this->assertSame(2, FeeStructure::where('academic_year_id', $year2027->id)->count());
        $this->assertEquals(
            1000,
            (float) FeeStructure::where('academic_year_id', $year2027->id)
                ->where('student_category_id', $general->id)
                ->first()
                ->charges()
                ->value('amount')
        );
        $this->assertEquals(
            2000,
            (float) FeeStructure::where('academic_year_id', $year2027->id)
                ->where('student_category_id', $staff->id)
                ->first()
                ->charges()
                ->value('amount')
        );
    }
}

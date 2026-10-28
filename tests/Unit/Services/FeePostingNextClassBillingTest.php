<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Student;
use App\Services\FeePostingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeePostingNextClassBillingTest extends TestCase
{
    private function makeStudentInClass(Classroom $classroom): Student
    {
        $student = new Student([
            'admission_number' => 'ADM-'.uniqid(),
            'first_name' => 'Next',
            'last_name' => 'Class',
            'classroom_id' => $classroom->id,
            'admission_date' => now()->toDateString(),
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $student->forceFill(['name' => 'Next Class']);
        $student->save();

        return $student->fresh(['classroom.nextClass']);
    }

    #[Test]
    public function future_year_bills_mapped_next_class(): void
    {
        AcademicYear::create(['year' => 2026, 'is_active' => true]);
        AcademicYear::create(['year' => 2027, 'is_active' => false]);

        $grade4 = Classroom::create(['name' => 'GRADE 4', 'is_alumni' => false]);
        $grade3 = Classroom::create(['name' => 'GRADE 3', 'next_class_id' => $grade4->id, 'is_alumni' => false]);
        $student = $this->makeStudentInClass($grade3);

        $service = app(FeePostingService::class);
        $placement = $service->resolveBillingPlacement($student, 2027);

        $this->assertTrue($placement['used_next_class']);
        $this->assertSame((int) $grade4->id, $placement['classroom_id']);
        $this->assertSame('GRADE 4', $placement['classroom_name']);
    }

    #[Test]
    public function current_year_bills_current_class(): void
    {
        AcademicYear::create(['year' => 2026, 'is_active' => true]);

        $grade4 = Classroom::create(['name' => 'GRADE 4', 'is_alumni' => false]);
        $grade3 = Classroom::create(['name' => 'GRADE 3', 'next_class_id' => $grade4->id, 'is_alumni' => false]);
        $student = $this->makeStudentInClass($grade3);

        $service = app(FeePostingService::class);
        $placement = $service->resolveBillingPlacement($student, 2026);

        $this->assertFalse($placement['used_next_class']);
        $this->assertSame((int) $grade3->id, $placement['classroom_id']);
    }

    #[Test]
    public function future_year_errors_when_next_class_is_alumni(): void
    {
        AcademicYear::create(['year' => 2026, 'is_active' => true]);
        AcademicYear::create(['year' => 2027, 'is_active' => false]);

        $alumni = Classroom::create(['name' => 'ALUMNI', 'is_alumni' => true]);
        $grade8 = Classroom::create(['name' => 'GRADE 8', 'next_class_id' => $alumni->id, 'is_alumni' => false]);
        $student = $this->makeStudentInClass($grade8);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alumni');

        app(FeePostingService::class)->resolveBillingPlacement($student, 2027);
    }
}

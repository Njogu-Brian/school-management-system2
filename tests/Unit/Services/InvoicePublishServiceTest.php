<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\InvoicePublishService;
use App\Services\ParentAppNotifyService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePublishServiceTest extends TestCase
{
    private function makeStudent(): Student
    {
        $classroom = Classroom::create(['name' => 'GRADE TEST']);

        $student = new Student([
            'admission_number' => 'ADM-'.uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $student->forceFill(['name' => 'Test Student']);
        $student->save();

        return $student;
    }

    #[Test]
    public function posting_leaves_invoices_unpublished_until_publish_service_runs(): void
    {
        $year = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $student = $this->makeStudent();

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 1,
            'academic_year_id' => $year->id,
            'invoice_number' => 'INV-TEST-1',
            'total' => 1000,
            'paid_amount' => 0,
            'balance' => 1000,
            'status' => 'unpaid',
            'issued_date' => now(),
            'published_at' => null,
        ]);

        $this->assertFalse($invoice->fresh()->isPublished());

        $this->mock(ParentAppNotifyService::class, function ($mock) {
            $mock->shouldReceive('notifyInvoiceGenerated')->once();
        });

        $service = $this->app->make(InvoicePublishService::class);
        $result = $service->publishMany(collect([$invoice]), [], true, false);

        $this->assertSame(1, $result['published']);
        $this->assertNotNull($invoice->fresh()->published_at);
    }

    #[Test]
    public function publish_skips_already_published_invoices(): void
    {
        $year = AcademicYear::create(['year' => 2026, 'is_active' => true]);
        $student = $this->makeStudent();

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 2,
            'academic_year_id' => $year->id,
            'invoice_number' => 'INV-TEST-2',
            'total' => 500,
            'paid_amount' => 0,
            'balance' => 500,
            'status' => 'unpaid',
            'issued_date' => now(),
            'published_at' => now()->subDay(),
        ]);

        $service = $this->app->make(InvoicePublishService::class);
        $result = $service->publishMany(collect([$invoice]), [], false, false);

        $this->assertSame(0, $result['published']);
        $this->assertSame(1, $result['skipped']);
    }
}

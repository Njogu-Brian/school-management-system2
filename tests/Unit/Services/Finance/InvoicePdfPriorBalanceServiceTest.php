<?php

namespace Tests\Unit\Services\Finance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Term;
use App\Models\Votehead;
use App\Services\Finance\InvoicePdfPriorBalanceService;
use App\Services\PaymentAllocationService;
use App\Services\StudentBalanceService;

class InvoicePdfPriorBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePdfPriorBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoicePdfPriorBalanceService();
    }

    /** @test */
    public function term_two_pdf_includes_unpaid_term_one_balance_without_changing_stored_totals(): void
    {
        [$student, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        $term2TotalBefore = (float) $term2->total;
        $term2BalanceBefore = (float) $term2->balance;
        $dashboardBefore = StudentBalanceService::getTotalOutstandingBalance($student);

        $overlay = $this->service->overlayForInvoice($term2);

        $this->assertCount(1, $overlay['lines']);
        $this->assertEquals(5000.0, $overlay['total']);
        $this->assertEquals(13000.0, $overlay['pdf_balance_due']);
        $this->assertStringContainsString('Term 1', $overlay['lines'][0]['label']);
        $this->assertEquals(5000.0, $overlay['lines'][0]['balance']);

        $term2->refresh();
        $this->assertEquals($term2TotalBefore, (float) $term2->total);
        $this->assertEquals($term2BalanceBefore, (float) $term2->balance);
        $this->assertEquals($dashboardBefore, StudentBalanceService::getTotalOutstandingBalance($student));
        $this->assertEquals(13000.0, $dashboardBefore);
    }

    /** @test */
    public function cleared_prior_invoice_is_omitted_from_pdf_overlay(): void
    {
        [$student, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        $term1->update(['paid_amount' => 5000, 'balance' => 0, 'status' => 'paid']);

        $overlay = $this->service->overlayForInvoice($term2->fresh());

        $this->assertSame([], $overlay['lines']);
        $this->assertEquals(0.0, $overlay['total']);
        $this->assertEquals(8000.0, $overlay['pdf_balance_due']);
    }

    /** @test */
    public function partial_prior_payment_shows_only_remaining_prior_balance(): void
    {
        [, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        $term1->update(['paid_amount' => 2000, 'balance' => 3000, 'status' => 'partial']);

        $overlay = $this->service->overlayForInvoice($term2->fresh());

        $this->assertEquals(3000.0, $overlay['total']);
        $this->assertEquals(11000.0, $overlay['pdf_balance_due']);
    }

    /** @test */
    public function overlay_ignores_later_invoices_and_other_students(): void
    {
        [$student, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 3,
            'total' => 9000,
            'paid_amount' => 0,
            'balance' => 9000,
            'status' => 'unpaid',
        ]);

        $other = Student::factory()->create();
        Invoice::factory()->create([
            'student_id' => $other->id,
            'year' => 2026,
            'term' => 1,
            'total' => 4000,
            'paid_amount' => 0,
            'balance' => 4000,
            'status' => 'unpaid',
        ]);

        $overlay = $this->service->overlayForInvoice($term2->fresh());

        $this->assertCount(1, $overlay['lines']);
        $this->assertEquals(5000.0, $overlay['total']);
        $this->assertSame($term1->invoice_number, $overlay['lines'][0]['invoice_number']);
    }

    /** @test */
    public function bulk_export_does_not_repeat_a_prior_invoice_that_is_already_in_the_pdf(): void
    {
        [, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        $overlays = $this->service->overlayForInvoices(collect([$term1, $term2]));

        $this->assertSame([], $overlays[$term1->id]['lines']);
        $this->assertSame([], $overlays[$term2->id]['lines']);
        $this->assertEquals(5000.0, $overlays[$term1->id]['pdf_balance_due']);
        $this->assertEquals(8000.0, $overlays[$term2->id]['pdf_balance_due']);
    }

    /** @test */
    public function pdf_view_shows_prior_balance_but_student_invoice_html_does_not(): void
    {
        [, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        $overlay = $this->service->overlayForInvoice($term2);

        $pdfHtml = view('finance.invoices.pdf.single', [
            'invoice' => $term2->load(['student.classroom', 'student.stream', 'items.votehead', 'items.allocations.payment']),
            'paymentRows' => [],
            'priorBalanceLines' => $overlay['lines'],
            'priorBalanceTotal' => $overlay['total'],
            'pdfBalanceDue' => $overlay['pdf_balance_due'],
            'branding' => ['name' => 'Test School', 'logoBase64' => null],
            'printedBy' => 'Test',
            'printedAt' => now(),
            'invoiceHeader' => '',
            'invoiceFooter' => '',
        ])->render();

        $this->assertStringContainsString('Previous unpaid invoice', $pdfHtml);
        $this->assertStringContainsString('13,000.00', $pdfHtml);
        $this->assertStringContainsString('Shown for this PDF only', $pdfHtml);

        $this->assertEquals(8000.0, (float) $term2->fresh()->balance);
        $this->assertEquals(5000.0, (float) $term1->fresh()->balance);
    }

    /** @test */
    public function payment_clears_previous_term_first_and_removes_it_from_pdf_overlay(): void
    {
        [$student, $term1, $term2] = $this->makeTwoTermInvoices(5000, 8000);

        InvoiceItem::factory()->create([
            'invoice_id' => $term1->id,
            'votehead_id' => Votehead::factory(),
            'amount' => 5000,
            'discount_amount' => 0,
            'status' => 'active',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $term2->id,
            'votehead_id' => Votehead::factory(),
            'amount' => 8000,
            'discount_amount' => 0,
            'status' => 'active',
        ]);
        $term1->recalculate();
        $term2->recalculate();

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
        ]);

        (new PaymentAllocationService())->autoAllocate($payment, $student->id);

        $term1->refresh();
        $term2->refresh();

        $this->assertEquals(0.0, (float) $term1->balance);
        $this->assertEquals(8000.0, (float) $term2->balance);

        $overlay = $this->service->overlayForInvoice($term2);
        $this->assertSame([], $overlay['lines']);
        $this->assertEquals(8000.0, $overlay['pdf_balance_due']);
        $this->assertEquals(8000.0, StudentBalanceService::getTotalOutstandingBalance($student));
    }

    /**
     * @return array{0: Student, 1: Invoice, 2: Invoice}
     */
    private function makeTwoTermInvoices(float $term1Balance, float $term2Balance): array
    {
        $student = Student::factory()->create();
        $year = AcademicYear::query()->firstOrCreate(
            ['year' => '2026'],
            ['is_active' => true]
        );

        $term1Model = Term::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
        ]);
        $term2Model = Term::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
        ]);

        $term1 = Invoice::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term1Model->id,
            'year' => 2026,
            'term' => 1,
            'total' => $term1Balance,
            'paid_amount' => 0,
            'balance' => $term1Balance,
            'status' => 'unpaid',
            'issued_date' => '2026-01-10',
        ]);
        $term2 = Invoice::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term2Model->id,
            'year' => 2026,
            'term' => 2,
            'total' => $term2Balance,
            'paid_amount' => 0,
            'balance' => $term2Balance,
            'status' => 'unpaid',
            'issued_date' => '2026-05-10',
        ]);

        return [$student, $term1, $term2];
    }
}

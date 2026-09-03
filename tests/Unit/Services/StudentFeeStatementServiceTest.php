<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Term;
use App\Models\Votehead;
use App\Services\ReceiptService;
use App\Services\StudentFeeStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFeeStatementServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentFeeStatementService $statements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statements = app(StudentFeeStatementService::class);
        Carbon::setTestNow('2026-09-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function payments_freeze_the_balance_as_at_that_receipt(): void
    {
        $student = Student::factory()->create();
        $this->makeBilledInvoice($student, 20000, '2026-01-10');

        $first = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10000,
            'payment_date' => '2026-08-03',
        ]);
        $second = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
            'payment_date' => '2026-09-10',
        ]);

        $first->refresh();
        $second->refresh();

        $this->assertEquals(10000.0, (float) $first->balance_after);
        $this->assertEquals(5000.0, (float) $second->balance_after);

        $receipts = app(ReceiptService::class);
        $firstReceipt = $receipts->buildReceiptData($first->fresh());
        $secondReceipt = $receipts->buildReceiptData($second->fresh());

        $this->assertEquals(10000.0, (float) $firstReceipt['total_balance_after']);
        $this->assertEquals(5000.0, (float) $secondReceipt['total_balance_after']);
        $this->assertEquals(10000.0, (float) $first->fresh()->balance_after);
    }

    /** @test */
    public function statement_shows_invoice_breakdown_payments_and_term_close(): void
    {
        $student = Student::factory()->create();
        $invoice = $this->makeBilledInvoice($student, 20000, '2026-01-10', 'Term 3', '2026-09-12');
        $item = $invoice->items()->first();

        CreditNote::create([
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $item->id,
            'credit_note_number' => 'CN-TEST-1',
            'amount' => 500,
            'reason' => 'Adjustment',
            'issued_at' => '2026-02-01',
        ]);
        DebitNote::create([
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $item->id,
            'debit_note_number' => 'DN-TEST-1',
            'amount' => 1000,
            'reason' => 'Extra charge',
            'issued_at' => '2026-02-15',
        ]);

        Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10000,
            'payment_date' => '2026-08-03',
        ]);
        Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
            'payment_date' => '2026-09-10',
        ]);

        $pack = $this->statements->forStudent($student->fresh(), 2026);
        $kinds = collect($pack['transactions'])->pluck('kind')->all();

        $this->assertContains('invoice', $kinds);
        $this->assertContains('payment', $kinds);
        $this->assertContains('term_close', $kinds);

        $invoiceRow = collect($pack['transactions'])->firstWhere('kind', 'invoice');
        $this->assertEquals(20000.0, (float) $invoiceRow['debit']);
        $this->assertNotEmpty($invoiceRow['children']);
        $adjustKinds = collect($invoiceRow['adjustments'])->pluck('kind')->all();
        $this->assertContains('credit_note', $adjustKinds);
        $this->assertContains('debit_note', $adjustKinds);

        $payments = collect($pack['transactions'])->where('kind', 'payment')->values();
        $this->assertEquals(10000.0, (float) $payments[0]['balance_after']);
        $this->assertEquals(5000.0, (float) $payments[1]['balance_after']);

        $close = collect($pack['transactions'])->firstWhere('kind', 'term_close');
        $this->assertStringContainsString('closed at', strtolower($close['description']));
        $this->assertEquals(5000.0, (float) $close['balance_after']);
        $this->assertEquals(5000.0, (float) $pack['closing_balance']);
    }

    private function makeBilledInvoice(
        Student $student,
        float $amount,
        string $issued,
        string $termName = 'Term 1',
        ?string $closing = null
    ): Invoice {
        $year = AcademicYear::factory()->create(['year' => 2026, 'is_active' => true]);
        $term = Term::factory()->create([
            'academic_year_id' => $year->id,
            'name' => $termName,
            'opening_date' => '2026-01-08',
            'closing_date' => $closing,
        ]);

        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'year' => 2026,
            'total' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'status' => 'unpaid',
            'issued_date' => $issued,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'votehead_id' => Votehead::factory(),
            'amount' => $amount,
            'discount_amount' => 0,
            'status' => 'active',
        ]);
        $invoice->recalculate();

        return $invoice->fresh(['items', 'term.academicYear']);
    }
}

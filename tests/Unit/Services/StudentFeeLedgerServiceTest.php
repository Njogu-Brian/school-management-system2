<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\StudentBalanceService;
use App\Services\StudentFeeLedgerService;

class StudentFeeLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentFeeLedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(StudentFeeLedgerService::class);
    }

    /** @test */
    public function balance_is_charges_minus_payments(): void
    {
        $student = Student::factory()->create();
        $invoice = $this->makeInvoice($student, 20000);

        $this->assertEquals(20000.0, StudentBalanceService::getTotalOutstandingBalance($student));

        Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10000,
            'allocated_amount' => 0,
            'unallocated_amount' => 0,
        ]);

        $this->assertEquals(10000.0, StudentBalanceService::getTotalOutstandingBalance($student->fresh()));
        $this->assertEquals(10000.0, (float) $invoice->fresh()->balance);
        $this->assertEquals('partial', $invoice->fresh()->status);
    }

    /** @test */
    public function increasing_the_invoice_increases_the_balance(): void
    {
        $student = Student::factory()->create();
        $invoice = $this->makeInvoice($student, 20000);
        Payment::factory()->create(['student_id' => $student->id, 'amount' => 10000]);

        $item = $invoice->items()->first();
        $item->update(['amount' => 21000]);
        $invoice->recalculate();

        $this->assertEquals(11000.0, StudentBalanceService::getTotalOutstandingBalance($student->fresh()));
        $this->assertEquals(11000.0, (float) $invoice->fresh()->balance);
    }

    /** @test */
    public function a_credit_note_style_reduction_lowers_the_balance_without_moving_allocations(): void
    {
        $student = Student::factory()->create();
        $invoice = $this->makeInvoice($student, 20000);
        $tuition = $invoice->items()->first();
        $water = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'votehead_id' => Votehead::factory(),
            'amount' => 1000,
            'discount_amount' => 0,
            'status' => 'active',
        ]);

        $payment = Payment::factory()->create(['student_id' => $student->id, 'amount' => 20000]);
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_item_id' => $tuition->id,
            'amount' => 20000,
        ]);

        $tuition->update(['amount' => 18000]);
        $invoice->recalculate();

        $invoice->refresh();
        $this->assertEquals(19000.0, (float) $invoice->total);
        $this->assertEquals(0.0, (float) $invoice->balance);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(-1000.0, StudentBalanceService::getTotalOutstandingBalance($student->fresh()));
        $this->assertGreaterThan(0, $water->fresh()->getBalance());
    }

    /** @test */
    public function payments_clear_the_oldest_invoice_first(): void
    {
        $student = Student::factory()->create();
        $term1 = $this->makeInvoice($student, 5000, '2026-01-10');
        $term2 = $this->makeInvoice($student, 8000, '2026-05-10');

        Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
            'payment_date' => '2026-06-01',
        ]);

        $this->assertEquals(0.0, (float) $term1->fresh()->balance);
        $this->assertEquals(8000.0, (float) $term2->fresh()->balance);
        $this->assertEquals(8000.0, StudentBalanceService::getTotalOutstandingBalance($student->fresh()));
    }

    /** @test */
    public function overpayment_becomes_credit_on_account(): void
    {
        $student = Student::factory()->create();
        $invoice = $this->makeInvoice($student, 10000);
        $payment = Payment::factory()->create(['student_id' => $student->id, 'amount' => 12000]);

        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(-2000.0, StudentBalanceService::getTotalOutstandingBalance($student->fresh()));
        $this->assertEquals(2000.0, (float) $payment->fresh()->unallocated_amount);
    }

    private function makeInvoice(Student $student, float $amount, string $issued = '2026-01-15'): Invoice
    {
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
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

        return $invoice->fresh();
    }
}

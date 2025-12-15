<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\PaymentAllocationService;
use App\Models\{Payment, Invoice, InvoiceItem, Student, Votehead};
use Illuminate\Support\Facades\DB;

class PaymentAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentAllocationService();
    }

    /** @test */
    public function it_can_allocate_payment_to_invoice_items()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create();
        $invoice = Invoice::factory()->create(['student_id' => $student->id, 'total' => 10000]);
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'votehead_id' => $votehead->id,
            'amount' => 10000,
        ]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
        ]);

        $allocations = [
            [
                'invoice_item_id' => $invoiceItem->id,
                'amount' => 5000,
            ],
        ];

        $result = $this->service->allocatePayment($payment, $allocations);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(5000, $result->allocated_amount);
        $this->assertEquals(5000, $invoiceItem->fresh()->getAllocatedAmount());
    }

    /** @test */
    public function it_prevents_over_allocation()
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create(['student_id' => $student->id]);
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
        ]);

        $allocations = [
            [
                'invoice_item_id' => $invoiceItem->id,
                'amount' => 6000, // Exceeds payment
            ],
        ];

        $this->expectException(\Exception::class);
        $this->service->allocatePayment($payment, $allocations);
    }

    /** @test */
    public function it_can_auto_allocate_payment()
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create(['student_id' => $student->id, 'total' => 10000]);
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 7500,
        ]);

        $result = $this->service->autoAllocate($payment);

        $this->assertGreaterThan(0, $result->allocated_amount);
        $this->assertLessThanOrEqual($payment->amount, $result->allocated_amount);
    }

    /** @test */
    public function it_handles_overpayment()
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10000,
        ]);

        // No invoice items - this is an overpayment
        $result = $this->service->autoAllocate($payment);

        $this->assertEquals(0, $result->allocated_amount);
        $this->assertEquals(10000, $result->unallocated_amount);
    }
}


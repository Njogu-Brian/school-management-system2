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

    /** @test */
    public function auto_allocate_skips_reversed_invoices_and_targets_active_ones()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create();

        $reversed = Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 2,
            'total' => 10000,
            'status' => 'reversed',
            'reversed_at' => now(),
            'issued_date' => '2026-04-01',
        ]);
        $reversedItem = InvoiceItem::factory()->create([
            'invoice_id' => $reversed->id,
            'votehead_id' => $votehead->id,
            'amount' => 10000,
            'status' => 'active',
        ]);

        $active = Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 3,
            'total' => 8000,
            'status' => 'unpaid',
            'issued_date' => '2026-08-01',
        ]);
        $activeItem = InvoiceItem::factory()->create([
            'invoice_id' => $active->id,
            'votehead_id' => $votehead->id,
            'amount' => 8000,
            'status' => 'active',
        ]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 3000,
            'reversed' => false,
        ]);

        $this->service->autoAllocate($payment);

        $this->assertEquals(0.0, (float) $reversedItem->fresh()->getAllocatedAmount());
        $this->assertEquals(3000.0, (float) $activeItem->fresh()->getAllocatedAmount());
    }

    /** @test */
    public function allocate_payment_rejects_reversed_invoice_items()
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'reversed',
            'reversed_at' => now(),
        ]);
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'status' => 'active',
        ]);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 1000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot allocate to a reversed invoice.');
        $this->service->allocatePayment($payment, [
            ['invoice_item_id' => $item->id, 'amount' => 1000],
        ]);
    }
}


<?php

namespace Tests\Unit\Services\Finance;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\User;
use App\Models\Votehead;
use App\Services\Finance\InvoiceReversalService;
use App\Services\InvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceReversalServiceTest extends TestCase
{
    protected InvoiceReversalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->service = app(InvoiceReversalService::class);
    }

    #[Test]
    public function it_reverses_an_unpaid_invoice(): void
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'total' => 10000,
            'paid_amount' => 0,
            'balance' => 10000,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'votehead_id' => Votehead::factory(),
            'amount' => 10000,
        ]);

        $result = $this->service->reverse($invoice, 'Posted accidentally');

        $invoice->refresh();
        $this->assertTrue($invoice->isReversed());
        $this->assertSame('reversed', $invoice->status);
        $this->assertNotNull($invoice->reversed_at);
        $this->assertSame('Posted accidentally', $invoice->reversal_reason);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals(0, (float) $invoice->balance);
        $this->assertSame(0, $result['payments_reversed']);
    }

    #[Test]
    public function it_unallocates_and_reverses_a_payment_only_on_this_invoice(): void
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create(['student_id' => $student->id, 'total' => 10000]);
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'allocated_amount' => 10000,
            'unallocated_amount' => 0,
            'reversed' => false,
        ]);
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_item_id' => $item->id,
            'amount' => 10000,
            'allocated_at' => now(),
        ]);

        $this->service->reverse($invoice, 'Wrong invoice generated');

        $payment->refresh();
        $this->assertTrue((bool) $payment->reversed);
        $this->assertSame(0, $payment->allocations()->count());
        $this->assertNull($payment->invoice_id);
        $this->assertTrue($invoice->fresh()->isReversed());
    }

    #[Test]
    public function it_unallocates_only_this_invoice_when_a_payment_covers_another_invoice(): void
    {
        $student = Student::factory()->create();
        $invoiceA = Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 1,
            'total' => 5000,
        ]);
        $invoiceB = Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 2,
            'total' => 5000,
        ]);
        $itemA = InvoiceItem::factory()->create(['invoice_id' => $invoiceA->id, 'amount' => 5000]);
        $itemB = InvoiceItem::factory()->create(['invoice_id' => $invoiceB->id, 'amount' => 5000]);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 10000,
            'reversed' => false,
        ]);
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_item_id' => $itemA->id,
            'amount' => 5000,
            'allocated_at' => now(),
        ]);
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_item_id' => $itemB->id,
            'amount' => 5000,
            'allocated_at' => now(),
        ]);

        $this->service->reverse($invoiceA, 'Reverse term 1 invoice only');

        $payment->refresh();
        $this->assertFalse((bool) $payment->reversed);
        $this->assertSame(0, PaymentAllocation::where('invoice_item_id', $itemA->id)->count());
        $this->assertSame(1, PaymentAllocation::where('invoice_item_id', $itemB->id)->count());
        $this->assertTrue($invoiceA->fresh()->isReversed());
        $this->assertFalse($invoiceB->fresh()->isReversed());
    }

    #[Test]
    public function it_does_not_reverse_the_same_invoice_twice(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'unpaid']);

        $this->service->reverse($invoice, 'First reverse');

        $this->expectException(\RuntimeException::class);
        $this->service->reverse($invoice->fresh(), 'Second reverse');
    }

    #[Test]
    public function ensure_creates_a_new_invoice_after_reversal(): void
    {
        $student = Student::factory()->create();
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'term' => 1,
        ]);

        $this->service->reverse($invoice, 'Accidental posting');

        $replacement = InvoiceService::ensure((int) $student->id, 2026, 1);

        $this->assertNotSame((int) $invoice->id, (int) $replacement->id);
        $this->assertFalse($replacement->isReversed());
        $this->assertTrue($invoice->fresh()->isReversed());
    }
}

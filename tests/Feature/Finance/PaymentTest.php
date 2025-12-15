<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Student, Payment, Invoice, InvoiceItem, Votehead, PaymentMethod};
use Illuminate\Support\Facades\DB;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    /** @test */
    public function admin_can_access_payments_page()
    {
        $response = $this->get(route('finance.payments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.payments.index');
    }

    /** @test */
    public function admin_can_create_payment()
    {
        $student = Student::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);

        $response = $this->get(route('finance.payments.create'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.payments.create');
    }

    /** @test */
    public function admin_can_store_payment()
    {
        $student = Student::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);

        $response = $this->post(route('finance.payments.store'), [
            'student_id' => $student->id,
            'amount' => 5000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method_id' => $paymentMethod->id,
            'auto_allocate' => false,
        ]);

        $response->assertRedirect(route('finance.payments.index'));
        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'amount' => 5000.00,
        ]);
    }

    /** @test */
    public function admin_can_view_payment_details()
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->create(['student_id' => $student->id]);

        $response = $this->get(route('finance.payments.show', $payment));

        $response->assertStatus(200);
        $response->assertViewIs('finance.payments.show');
        $response->assertViewHas('payment');
    }

    /** @test */
    public function admin_can_allocate_payment()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create();
        $invoice = Invoice::factory()->create(['student_id' => $student->id]);
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'votehead_id' => $votehead->id,
            'amount' => 10000,
        ]);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'amount' => 5000,
        ]);

        $response = $this->post(route('finance.payments.allocate', $payment), [
            'allocations' => [
                [
                    'invoice_item_id' => $invoiceItem->id,
                    'amount' => 5000,
                ],
            ],
        ]);

        $response->assertRedirect(route('finance.payments.show', $payment));
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_item_id' => $invoiceItem->id,
            'amount' => 5000,
        ]);
    }

    /** @test */
    public function admin_can_generate_receipt_pdf()
    {
        $student = Student::factory()->create();
        $payment = Payment::factory()->create(['student_id' => $student->id]);

        $response = $this->get(route('finance.payments.receipt', $payment));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}


<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Finance Officer', 'web');
        Role::findOrCreate('Secretary', 'web');
    }

    /** @test */
    public function finance_officer_can_create_submit_approve_voucher_and_pay_expense(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Finance Officer');
        $this->actingAs($user);

        $category = ExpenseCategory::create(['code' => 'FUEL', 'name' => 'Fuel', 'is_active' => true]);
        $vendor = Vendor::create(['name' => 'Total Fuel', 'is_active' => true]);

        $createResponse = $this->post(route('finance.expenses.store'), [
            'source_type' => 'vendor_bill',
            'vendor_id' => $vendor->id,
            'expense_date' => now()->toDateString(),
            'currency' => 'KES',
            'lines' => [
                [
                    'category_id' => $category->id,
                    'description' => 'Bus fuel refill',
                    'qty' => 10,
                    'unit_cost' => 150,
                    'tax_rate' => 0,
                ],
            ],
        ]);
        $createResponse->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->post(route('finance.expenses.submit', $expense))->assertRedirect();
        $this->post(route('finance.expenses.approvals.store', $expense), ['decision' => 'approved'])->assertRedirect();
        $this->post(route('finance.payment-vouchers.store'), ['expense_id' => $expense->id])->assertRedirect();

        $voucher = $expense->fresh()->vouchers()->firstOrFail();
        $this->post(route('finance.payment-vouchers.pay', $voucher), ['amount' => $voucher->amount])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payment_vouchers', ['id' => $voucher->id, 'status' => 'paid']);
        $this->assertDatabaseCount('ledger_postings', 2);
    }

    /** @test */
    public function secretary_cannot_approve_expense(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Secretary');
        $this->actingAs($user);

        $expense = Expense::create([
            'expense_no' => Expense::generateExpenseNo(),
            'source_type' => 'vendor_bill',
            'requested_by' => $user->id,
            'expense_date' => now()->toDateString(),
            'currency' => 'KES',
            'subtotal' => 1000,
            'tax_total' => 0,
            'total' => 1000,
            'status' => Expense::STATUS_SUBMITTED,
        ]);

        $response = $this->post(route('finance.expenses.approvals.store', $expense), [
            'decision' => 'approved',
        ]);

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ExpenseWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_expense_lifecycle_and_posts_ledger_entries(): void
    {
        $service = app(ExpenseWorkflowService::class);
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $vendor = Vendor::create(['name' => 'Kenya Power', 'is_active' => true]);
        $category = ExpenseCategory::create(['code' => 'UTIL', 'name' => 'Utilities', 'is_active' => true]);

        $expense = Expense::create([
            'source_type' => 'vendor_bill',
            'vendor_id' => $vendor->id,
            'requested_by' => $requester->id,
            'expense_date' => now()->toDateString(),
            'currency' => 'KES',
            'status' => Expense::STATUS_DRAFT,
        ]);

        $expense->lines()->create([
            'category_id' => $category->id,
            'description' => 'Monthly electricity bill',
            'qty' => 1,
            'unit_cost' => 5000,
            'tax_rate' => 16,
        ]);

        $service->submit($expense);
        $service->decide($expense->fresh(), $approver, 'approved');
        $voucher = $service->createVoucher($expense->fresh(), $approver);
        $payment = $service->payVoucher($voucher, $approver, ['amount' => $voucher->amount]);

        $this->assertEquals(Expense::STATUS_PAID, $expense->fresh()->status);
        $this->assertEquals('paid', $voucher->fresh()->status);
        $this->assertEquals(2, \App\Models\LedgerPosting::where('source_id', $payment->id)->count());
    }
}

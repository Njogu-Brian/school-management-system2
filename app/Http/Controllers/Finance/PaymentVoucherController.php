<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpensePaymentRequest;
use App\Http\Requests\Finance\StoreVoucherRequest;
use App\Models\Expense;
use App\Models\PaymentVoucher;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentVoucherController extends Controller
{
    public function index(): View
    {
        $vouchers = PaymentVoucher::with(['expense', 'payments'])->latest()->paginate(20);
        return view('finance.vouchers.index', compact('vouchers'));
    }

    public function store(StoreVoucherRequest $request, ExpenseWorkflowService $workflowService): RedirectResponse
    {
        $expense = Expense::findOrFail((int) $request->validated('expense_id'));
        $this->authorize('pay', $expense);

        $voucher = $workflowService->createVoucher($expense, $request->user(), $request->validated());

        return redirect()->route('finance.payment-vouchers.show', $voucher)->with('success', 'Payment voucher generated.');
    }

    public function show(PaymentVoucher $paymentVoucher): View
    {
        $paymentVoucher->load(['expense.vendor', 'expense.requester', 'payments.recorder']);
        return view('finance.vouchers.show', ['voucher' => $paymentVoucher]);
    }

    public function pay(StoreExpensePaymentRequest $request, PaymentVoucher $paymentVoucher, ExpenseWorkflowService $workflowService): RedirectResponse
    {
        $this->authorize('pay', $paymentVoucher->expense);
        $workflowService->payVoucher($paymentVoucher, $request->user(), $request->validated());

        return redirect()->route('finance.payment-vouchers.show', $paymentVoucher)->with('success', 'Expense payment recorded.');
    }
}

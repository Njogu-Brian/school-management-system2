<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ExpenseApprovalRequest;
use App\Models\Expense;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\RedirectResponse;

class ExpenseApprovalController extends Controller
{
    public function store(ExpenseApprovalRequest $request, Expense $expense, ExpenseWorkflowService $workflowService): RedirectResponse
    {
        $this->authorize('approve', $expense);
        $data = $request->validated();

        try {
            $workflowService->decide(
                $expense,
                $request->user(),
                $data['decision'],
                $data['remarks'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('finance.expenses.show', $expense)
                ->with('error', $e->getMessage() . ' (current status: ' . $expense->status . ')');
        }

        return redirect()->route('finance.expenses.show', $expense)->with('success', 'Approval decision recorded.');
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ExpenseReportController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = Expense::query()->with(['vendor', 'lines.category']);

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('expense_date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('expense_date', '<=', $request->string('to_date'));
        }

        $expenses = (clone $baseQuery)->latest('expense_date')->paginate(30)->withQueryString();

        $categorySummary = (clone $baseQuery)
            ->join('expense_lines', 'expenses.id', '=', 'expense_lines.expense_id')
            ->join('expense_categories', 'expense_lines.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category_name, SUM(expense_lines.line_total) as total_amount')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $vendorSummary = (clone $baseQuery)
            ->leftJoin('vendors', 'expenses.vendor_id', '=', 'vendors.id')
            ->selectRaw("COALESCE(vendors.name, 'No Vendor') as vendor_name, SUM(expenses.total) as total_amount")
            ->groupByRaw("COALESCE(vendors.name, 'No Vendor')")
            ->orderByDesc('total_amount')
            ->get();

        return view('finance.reports.expenses', compact('expenses', 'categorySummary', 'vendorSummary'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $expenses = $this->filteredExpenses($request)->get();

        $filename = 'expense-report-' . now()->format('YmdHis') . '.csv';
        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Expense No', 'Date', 'Status', 'Vendor', 'Total']);
            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->expense_no,
                    optional($expense->expense_date)->format('Y-m-d'),
                    $expense->status,
                    optional($expense->vendor)->name,
                    (float) $expense->total,
                ]);
            }
            fclose($handle);
        }, $filename);
    }

    public function exportPdf(Request $request)
    {
        $expenses = $this->filteredExpenses($request)->get();
        $pdf = Pdf::loadView('finance.reports.expenses_pdf', compact('expenses'));
        return $pdf->download('expense-report-' . now()->format('YmdHis') . '.pdf');
    }

    protected function filteredExpenses(Request $request)
    {
        $query = Expense::with('vendor')->latest('expense_date');
        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->string('to_date'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        return $query;
    }
}

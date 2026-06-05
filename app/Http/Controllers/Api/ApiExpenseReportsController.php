<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ApiExpenseReportsController extends Controller
{
    public function summary(Request $request)
    {
        $baseQuery = Expense::query()->with(['vendor', 'lines.category']);

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('expense_date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('expense_date', '<=', $request->string('to_date'));
        }

        $totalExpenses = (float) (clone $baseQuery)->sum('total');
        $expenseCount = (clone $baseQuery)->count();

        $categorySummary = (clone $baseQuery)
            ->join('expense_lines', 'expenses.id', '=', 'expense_lines.expense_id')
            ->join('expense_categories', 'expense_lines.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category_name, SUM(expense_lines.line_total) as total_amount')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'category_name' => $row->category_name,
                'total_amount' => round((float) $row->total_amount, 2),
            ]);

        $vendorSummary = (clone $baseQuery)
            ->leftJoin('vendors', 'expenses.vendor_id', '=', 'vendors.id')
            ->selectRaw("COALESCE(vendors.name, 'No Vendor') as vendor_name, SUM(expenses.total) as total_amount")
            ->groupByRaw("COALESCE(vendors.name, 'No Vendor')")
            ->orderByDesc('total_amount')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'vendor_name' => $row->vendor_name,
                'total_amount' => round((float) $row->total_amount, 2),
            ]);

        $recent = (clone $baseQuery)
            ->latest('expense_date')
            ->limit(15)
            ->get()
            ->map(fn (Expense $e) => [
                'id' => $e->id,
                'expense_no' => $e->expense_no,
                'expense_date' => $e->expense_date?->format('Y-m-d'),
                'status' => $e->status,
                'vendor_name' => $e->vendor?->name,
                'total' => round((float) $e->total, 2),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_expenses' => round($totalExpenses, 2),
                'expense_count' => $expenseCount,
                'category_summary' => $categorySummary,
                'vendor_summary' => $vendorSummary,
                'recent_expenses' => $recent,
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}

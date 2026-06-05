<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\OnlineAdmission;
use App\Models\Reports\OperationsFacility;
use App\Models\Requisition;
use App\Models\Academics\LessonPlan;
use Illuminate\Http\Request;

/**
 * Composite executive summary for mobile Reports hub — aggregates existing modules.
 */
class ApiBoardPackController extends Controller
{
    public function show(Request $request)
    {
        $finance = app(ApiFinanceSummaryController::class)->show($request)->getData(true)['data'] ?? [];
        $operations = app(ApiOperationsSummaryController::class)->show($request)->getData(true)['data'] ?? [];

        $pendingApprovals = LeaveRequest::where('status', 'pending')->count()
            + LessonPlan::where('submission_status', 'submitted')->count()
            + OnlineAdmission::whereIn('application_status', ['pending', 'under_review', 'waitlisted'])->count()
            + Requisition::where('status', 'pending')->count();

        $expenseMonth = (float) Expense::query()
            ->whereDate('expense_date', '>=', now()->startOfMonth())
            ->sum('total');

        $openFacilityIssues = OperationsFacility::query()
            ->where('resolved', false)
            ->count();

        $lowStockItems = InventoryItem::active()
            ->whereRaw('quantity <= min_stock_level')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'finance' => $finance,
                'operations' => $operations,
                'approvals' => [
                    'pending_total' => $pendingApprovals,
                ],
                'expenses' => [
                    'month_to_date' => round($expenseMonth, 2),
                ],
                'facilities' => [
                    'open_issues' => $openFacilityIssues,
                    'low_stock_items' => $lowStockItems,
                ],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}

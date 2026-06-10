<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\FixedAsset;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\LedgerPosting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLedgerController extends Controller
{
    public function postings(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = LedgerPosting::query()
            ->orderByDesc('posting_date')
            ->orderByDesc('id');

        if ($request->filled('account_code')) {
            $query->where('account_code', $request->string('account_code'));
        }
        if ($request->filled('dr_cr')) {
            $query->where('dr_cr', $request->string('dr_cr'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('posting_date', '>=', $request->string('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('posting_date', '<=', $request->string('date_to'));
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (LedgerPosting $p) => [
            'id' => $p->id,
            'posting_date' => $p->posting_date?->format('Y-m-d'),
            'account_code' => $p->account_code,
            'dr_cr' => $p->dr_cr,
            'amount' => (float) $p->amount,
            'source_type' => $p->source_type,
            'source_id' => $p->source_id,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function trialBalance(Request $request)
    {
        $query = LedgerPosting::query();

        if ($request->filled('date_from')) {
            $query->whereDate('posting_date', '>=', $request->string('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('posting_date', '<=', $request->string('date_to'));
        }

        $rows = $query
            ->select(
                'account_code',
                DB::raw("SUM(CASE WHEN dr_cr = 'dr' THEN amount ELSE 0 END) as total_dr"),
                DB::raw("SUM(CASE WHEN dr_cr = 'cr' THEN amount ELSE 0 END) as total_cr"),
            )
            ->groupBy('account_code')
            ->orderBy('account_code')
            ->get()
            ->map(fn ($row) => [
                'account_code' => $row->account_code,
                'total_dr' => round((float) $row->total_dr, 2),
                'total_cr' => round((float) $row->total_cr, 2),
                'net' => round((float) $row->total_dr - (float) $row->total_cr, 2),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $rows,
                'totals' => [
                    'dr' => round((float) $rows->sum('total_dr'), 2),
                    'cr' => round((float) $rows->sum('total_cr'), 2),
                ],
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Derived balance-sheet style snapshot from operational data.
     */
    public function balanceSheet()
    {
        $collections = round((float) Payment::query()
            ->where(function ($q) {
                $q->whereNull('reversed')->orWhere('reversed', false);
            })
            ->sum('amount'), 2);

        $expensePayments = round((float) ExpensePayment::query()->sum('amount'), 2);

        $cashAndBank = round($collections - $expensePayments, 2);

        $accountsReceivable = round((float) Invoice::query()
            ->whereNull('reversed_at')
            ->sum('balance'), 2);

        $fixedAssets = round((float) FixedAsset::query()
            ->whereIn('status', ['active', 'in_repair'])
            ->sum('purchase_cost'), 2);

        $inventoryValue = round((float) InventoryItem::query()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_cost, 0)), 0) as value')
            ->value('value'), 2);

        $accountsPayable = round((float) Expense::query()
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('total'), 2);

        $totalAssets = round($cashAndBank + $accountsReceivable + $fixedAssets + $inventoryValue, 2);
        $totalLiabilities = $accountsPayable;

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => [
                    ['label' => 'Cash & bank (net collections)', 'amount' => $cashAndBank],
                    ['label' => 'Accounts receivable (outstanding fees)', 'amount' => $accountsReceivable],
                    ['label' => 'Fixed assets (at cost)', 'amount' => $fixedAssets],
                    ['label' => 'Inventory (at cost)', 'amount' => $inventoryValue],
                ],
                'liabilities' => [
                    ['label' => 'Accounts payable (approved unpaid expenses)', 'amount' => $accountsPayable],
                ],
                'totals' => [
                    'assets' => $totalAssets,
                    'liabilities' => $totalLiabilities,
                    'net_position' => round($totalAssets - $totalLiabilities, 2),
                ],
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}

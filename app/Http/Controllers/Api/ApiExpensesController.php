<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ApiExpensesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = Expense::query()
            ->with(['vendor', 'requester'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->string('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (Expense $e) => $this->serializeSummary($e))->values();

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

    public function show(int $id)
    {
        $e = Expense::with(['vendor', 'requester', 'approver', 'lines.category', 'vouchers'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->serializeSummary($e),
                'due_date' => $e->due_date?->format('Y-m-d'),
                'currency' => $e->currency,
                'subtotal' => (float) $e->subtotal,
                'tax_total' => (float) $e->tax_total,
                'requested_by' => $e->requester?->name,
                'approved_by' => $e->approver?->name,
                'approved_at' => $e->approved_at?->toIso8601String(),
                'submitted_at' => $e->submitted_at?->toIso8601String(),
                'notes' => $e->notes,
                'lines' => $e->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'description' => $l->description,
                    'category' => $l->category?->name,
                    'department' => $l->department,
                    'qty' => (float) $l->qty,
                    'unit_cost' => (float) $l->unit_cost,
                    'tax_rate' => (float) $l->tax_rate,
                    'line_total' => (float) $l->line_total,
                ])->values(),
                'vouchers' => $e->vouchers->map(fn ($v) => [
                    'id' => $v->id,
                    'voucher_no' => $v->voucher_no,
                    'status' => $v->status,
                    'amount' => (float) ($v->amount ?? 0),
                    'payment_method' => $v->payment_method,
                    'payment_date' => $v->payment_date?->format('Y-m-d'),
                ])->values(),
            ],
        ]);
    }

    protected function serializeSummary(Expense $e): array
    {
        return [
            'id' => $e->id,
            'expense_no' => $e->expense_no,
            'vendor' => $e->vendor?->name,
            'expense_date' => $e->expense_date?->format('Y-m-d'),
            'total' => (float) $e->total,
            'status' => $e->status,
            'source_type' => $e->source_type,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function show(Request $request, int $id)
    {
        $e = Expense::with(['vendor', 'requester', 'approver', 'lines.category', 'vouchers', 'attachments.uploader'])->findOrFail($id);
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->serializeSummary($e),
                'can_submit' => $user ? $user->can('submit', $e) : false,
                'can_approve' => $user ? $user->can('approve', $e) : false,
                'can_pay' => $user ? $user->can('pay', $e) : false,
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
                'attachments' => $e->attachments->map(fn (ExpenseAttachment $a) => $this->serializeAttachment($a))->values(),
            ],
        ]);
    }

    public function storeAttachment(Request $request, int $id)
    {
        $expense = Expense::findOrFail($id);

        if (! $request->user()?->can('view', $expense)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to modify this expense.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        $file = $request->file('file');
        $path = $file->store('expense-attachments', 'public');

        $attachment = ExpenseAttachment::create([
            'expense_id' => $expense->id,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded.',
            'data' => $this->serializeAttachment($attachment->load('uploader')),
        ], 201);
    }

    public function destroyAttachment(Request $request, int $id, int $attachmentId)
    {
        $expense = Expense::findOrFail($id);

        if (! $request->user()?->can('view', $expense)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to modify this expense.'], 403);
        }

        $attachment = ExpenseAttachment::where('expense_id', $expense->id)->findOrFail($attachmentId);

        if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Attachment removed.']);
    }

    protected function serializeAttachment(ExpenseAttachment $a): array
    {
        return [
            'id' => $a->id,
            'file_name' => basename($a->path ?? ''),
            'mime_type' => $a->mime_type,
            'url' => $a->path ? asset('storage/'.ltrim($a->path, '/')) : null,
            'uploaded_by' => $a->uploader?->name,
            'uploaded_at' => $a->created_at?->toIso8601String(),
        ];
    }

    public function submit(Request $request, int $id, ExpenseWorkflowService $workflow)
    {
        $expense = Expense::findOrFail($id);

        if (! $request->user()?->can('submit', $expense)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to submit this expense.'], 403);
        }

        try {
            $workflow->submit($expense);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense submitted for approval.',
            'data' => $this->serializeSummary($expense->fresh('vendor')),
        ]);
    }

    public function approve(Request $request, int $id, ExpenseWorkflowService $workflow)
    {
        return $this->decide($request, $id, $workflow, 'approved');
    }

    public function reject(Request $request, int $id, ExpenseWorkflowService $workflow)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);

        return $this->decide($request, $id, $workflow, 'rejected');
    }

    public function pay(Request $request, int $id, ExpenseWorkflowService $workflow)
    {
        $expense = Expense::with('vendor')->findOrFail($id);
        $user = $request->user();

        if (! $user?->can('pay', $expense)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to pay this expense.'], 403);
        }

        $data = $request->validate([
            'payment_method' => 'nullable|string|max:100',
            'reference_no' => 'nullable|string|max:100',
        ]);

        try {
            $voucher = $workflow->createVoucher($expense, $user, [
                'payment_method' => $data['payment_method'] ?? null,
                'payment_date' => now()->toDateString(),
            ]);
            $workflow->payVoucher($voucher, $user, [
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => now(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense paid and posted to ledger.',
            'data' => $this->serializeSummary($expense->fresh('vendor')),
        ]);
    }

    protected function decide(Request $request, int $id, ExpenseWorkflowService $workflow, string $decision)
    {
        $expense = Expense::findOrFail($id);
        $user = $request->user();

        if (! $user?->can('approve', $expense)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to review this expense.'], 403);
        }

        try {
            $workflow->decide($expense, $user, $decision, $request->input('remarks'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $decision === 'approved' ? 'Expense approved.' : 'Expense rejected.',
            'data' => $this->serializeSummary($expense->fresh('vendor')),
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

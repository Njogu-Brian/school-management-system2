<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Create a draft expense (amount + date, optional vendor/notes/lines).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user?->can('create', Expense::class)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to create expenses.'], 403);
        }

        $data = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
            'vendor_id' => 'nullable|exists:vendors,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'nullable|string|max:1000',
            'source_type' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'lines' => 'nullable|array|min:1',
            'lines.*.category_id' => 'required_with:lines|exists:expense_categories,id',
            'lines.*.description' => 'required_with:lines|string|max:1000',
            'lines.*.qty' => 'required_with:lines|numeric|min:0.01',
            'lines.*.unit_cost' => 'required_with:lines|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.department' => 'nullable|string|max:255',
            'lines.*.cost_center' => 'nullable|string|max:255',
        ]);

        $lines = $data['lines'] ?? null;
        if (! is_array($lines) || $lines === []) {
            $amount = (float) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Amount is required.'], 422);
            }
            $categoryId = isset($data['category_id'])
                ? (int) $data['category_id']
                : (int) (ExpenseCategory::query()
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->where('is_header', false)->orWhereNull('is_header');
                    })
                    ->orderBy('id')
                    ->value('id') ?? 0);
            if ($categoryId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No expense category is configured. Add a category on the portal first.',
                ], 422);
            }
            $lines = [[
                'category_id' => $categoryId,
                'description' => $data['description'] ?? ($data['notes'] ?: 'Expense'),
                'qty' => 1,
                'unit_cost' => $amount,
                'tax_rate' => 0,
            ]];
        }

        $expense = DB::transaction(function () use ($request, $data, $lines) {
            $expense = Expense::create([
                'source_type' => $data['source_type'] ?? 'vendor_bill',
                'vendor_id' => $data['vendor_id'] ?? null,
                'requested_by' => $request->user()->id,
                'expense_date' => $data['expense_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency'] ?? 'KES'),
                'status' => Expense::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $expense->lines()->create([
                    'category_id' => $line['category_id'],
                    'department' => $line['department'] ?? null,
                    'cost_center' => $line['cost_center'] ?? null,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'tax_rate' => $line['tax_rate'] ?? 0,
                ]);
            }

            $expense->recalculateTotals();
            $expense->save();

            return $expense;
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense draft created.',
            'data' => $this->serializeSummary($expense->fresh('vendor')),
        ], 201);
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
        $disk = config('filesystems.public_disk', 'public');
        $path = $file->store('expense-attachments', $disk);

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

        $disk = config('filesystems.public_disk', 'public');
        if ($attachment->path) {
            if (Storage::disk($disk)->exists($attachment->path)) {
                Storage::disk($disk)->delete($attachment->path);
            } elseif (Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
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
            'url' => storage_public_url($a->path) ?: ($a->path ? asset('storage/'.ltrim($a->path, '/')) : null),
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

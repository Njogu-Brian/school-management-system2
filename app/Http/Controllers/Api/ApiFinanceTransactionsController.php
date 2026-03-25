<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Student;
use App\Services\UnifiedTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ApiFinanceTransactionsController extends Controller
{
    public function __construct(
        protected UnifiedTransactionService $unifiedTransactionService,
    ) {}

    /**
     * Unified bank + M-Pesa C2B transactions (same filters as web "Transactions" view).
     */
    public function index(Request $request)
    {
        $request->validate([
            'view' => 'nullable|string|in:all,auto-assigned,unassigned,duplicate,swimming,manual-assigned,draft,collected,archived',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to view finance transactions.');
        }

        $filters = [
            'view' => $request->input('view', 'all'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = (int) $request->input('per_page', 20);

        $query = $this->unifiedTransactionService->getUnifiedTransactionsQuery($filters);
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items());
        $studentIds = $items->pluck('student_id')->filter()->unique()->values()->all();
        $students = $studentIds === []
            ? collect()
            : Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $data = $items->map(function ($row) use ($students) {
            return $this->formatUnifiedRow($row, $students);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $request->validate([
            'type' => 'required|in:bank,c2b',
        ]);

        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403);
        }

        $type = $request->input('type');
        if ($type === 'bank') {
            $bank = BankStatementTransaction::with(['student', 'family', 'bankAccount', 'payment'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatBankDetail($bank),
            ]);
        }

        $c2b = MpesaC2BTransaction::with(['student', 'payment'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatC2bDetail($c2b),
        ]);
    }

    protected function formatUnifiedRow(object $row, Collection $studentsById): array
    {
        $type = $row->transaction_type ?? null;
        $sid = $row->student_id ? (int) $row->student_id : null;
        $studentName = null;
        if ($sid && $studentsById->has($sid)) {
            $s = $studentsById->get($sid);
            $studentName = trim(($s->first_name ?? '').' '.($s->last_name ?? ''));
        }

        $recordedAt = null;
        if (! empty($row->created_at)) {
            try {
                $recordedAt = \Carbon\Carbon::parse($row->created_at)->toIso8601String();
            } catch (\Throwable) {
                $recordedAt = (string) $row->created_at;
            }
        }

        return [
            'id' => (int) $row->id,
            'transaction_type' => $type,
            'trans_date' => $row->trans_date ?? null,
            'trans_amount' => isset($row->trans_amount) ? (float) $row->trans_amount : null,
            'trans_code' => $row->trans_code ?? null,
            'description' => $row->description ?? null,
            'bill_ref_number' => $row->bill_ref_number ?? null,
            'phone_number' => $row->phone_number ?? null,
            'payer_name' => $row->payer_name ?? null,
            'student_id' => $sid,
            'student_name' => $studentName,
            'match_status' => $row->match_status ?? null,
            'match_confidence' => isset($row->match_confidence) ? (float) $row->match_confidence : null,
            'status' => $row->status ?? null,
            'is_duplicate' => (bool) ($row->is_duplicate ?? false),
            'is_archived' => (bool) ($row->is_archived ?? false),
            'payment_created' => (bool) ($row->payment_created ?? false),
            'is_swimming_transaction' => (bool) ($row->is_swimming_transaction ?? false),
            'recorded_at' => $recordedAt,
        ];
    }

    protected function formatBankDetail(BankStatementTransaction $t): array
    {
        $student = $t->student;

        return [
            'id' => $t->id,
            'transaction_type' => 'bank',
            'transaction_date' => $t->transaction_date?->format('Y-m-d'),
            'amount' => (float) $t->amount,
            'reference_number' => $t->reference_number,
            'description' => $t->description,
            'phone_number' => $t->phone_number,
            'payer_name' => $t->payer_name,
            'bank_type' => $t->bank_type,
            'student_id' => $t->student_id,
            'student_name' => $student ? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : null,
            'match_status' => $t->match_status,
            'match_confidence' => $t->match_confidence !== null ? (float) $t->match_confidence : null,
            'status' => $t->status,
            'is_shared' => (bool) $t->is_shared,
            'shared_allocations' => $t->shared_allocations,
            'payment_created' => (bool) $t->payment_created,
            'payment_id' => $t->payment_id,
            'is_duplicate' => (bool) $t->is_duplicate,
            'is_archived' => (bool) $t->is_archived,
            'is_swimming_transaction' => (bool) ($t->is_swimming_transaction ?? false),
            'swimming_allocated_amount' => isset($t->swimming_allocated_amount) ? (float) $t->swimming_allocated_amount : null,
            'match_notes' => $t->match_notes,
        ];
    }

    protected function formatC2bDetail(MpesaC2BTransaction $t): array
    {
        $student = $t->student;

        return [
            'id' => $t->id,
            'transaction_type' => 'c2b',
            'trans_time' => $t->trans_time?->format('Y-m-d H:i:s'),
            'trans_amount' => (float) $t->trans_amount,
            'trans_id' => $t->trans_id,
            'bill_ref_number' => $t->bill_ref_number,
            'msisdn' => $t->msisdn,
            'first_name' => $t->first_name,
            'last_name' => $t->last_name,
            'student_id' => $t->student_id,
            'student_name' => $student ? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : null,
            'allocation_status' => $t->allocation_status,
            'match_confidence' => $t->match_confidence,
            'status' => $t->status,
            'is_shared' => (bool) $t->is_shared,
            'shared_allocations' => $t->shared_allocations,
            'payment_id' => $t->payment_id,
            'is_duplicate' => (bool) $t->is_duplicate,
            'is_swimming_transaction' => (bool) ($t->is_swimming_transaction ?? false),
            'match_reason' => $t->match_reason,
        ];
    }
}

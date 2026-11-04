<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\PaymentGateways\MpesaGateway;
use App\Services\StudentBalanceService;
use App\Services\StudentFeeLedgerService;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Parent family fee summary + family-level M-Pesa STK (oldest invoice first across children).
 */
class ApiParentFamilyFeesController extends Controller
{
    public function __construct(
        protected MpesaGateway $mpesaGateway,
    ) {}

    protected function assertParent(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Parent', 'Guardian'])) {
            // Staff with finance may inspect; parents required for payment.
            if ($user && ($user->can('finance.view') || $user->canViewStudentFeeAmounts())) {
                return;
            }
            abort(403, 'Parent access required.');
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Student>
     */
    protected function linkedStudents(Request $request)
    {
        $user = $request->user();
        $ids = method_exists($user, 'accessibleStudentIds')
            ? $user->accessibleStudentIds()
            : [];

        return Student::query()
            ->whereIn('id', $ids ?: [0])
            ->with(['classroom:id,name'])
            ->orderBy('first_name')
            ->get();
    }

    public function summary(Request $request)
    {
        $this->assertParent($request);
        $user = $request->user();
        if (! $user->canViewStudentFeeAmounts() && ! $user->hasAnyRole(['Parent', 'Guardian'])) {
            abort(403);
        }

        $students = $this->linkedStudents($request);
        $ledger = app(StudentFeeLedgerService::class);
        $today = now()->toDateString();
        $children = [];
        $familyOutstanding = 0.0;
        $familyOverdue = 0.0;
        $familyUpcoming = 0.0;

        foreach ($students as $student) {
            $outstanding = round((float) StudentBalanceService::getTotalOutstandingBalance($student, false), 2);
            $overdue = round((float) StudentBalanceService::getTotalOutstandingBalance($student, true), 2);
            $upcoming = round(max(0.0, $outstanding - $overdue), 2);

            $invoices = Invoice::query()
                ->where('student_id', $student->id)
                ->notReversed()
                ->published()
                ->where('balance', '>', 0)
                ->whereIn('status', ['unpaid', 'partial'])
                ->with(['items' => fn ($q) => $q->where('status', 'active')->with('votehead:id,name')])
                ->orderBy('issued_date')
                ->orderBy('id')
                ->get();

            $invoiceRows = [];
            foreach ($invoices as $inv) {
                $isDue = $ledger->invoiceIsDue($inv, $today);
                $termModel = $inv->academicTerm();
                $invoiceRows[] = [
                    'id' => (int) $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'issue_date' => $inv->issued_date?->toDateString(),
                    'due_date' => $inv->due_date?->toDateString(),
                    'term' => $inv->term,
                    'term_name' => $termModel?->name,
                    'year' => $inv->year,
                    'total' => round((float) $inv->total, 2),
                    'paid_amount' => round((float) $inv->paid_amount, 2),
                    'balance' => round((float) $inv->balance, 2),
                    'status' => $isDue ? 'overdue' : 'upcoming',
                    'items' => $inv->items
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'name' => $item->votehead->name ?? 'Fee',
                            'amount' => round((float) $item->amount - (float) ($item->discount_amount ?? 0), 2),
                        ])
                        ->values()
                        ->all(),
                ];
            }

            $familyOutstanding += $outstanding;
            $familyOverdue += $overdue;
            $familyUpcoming += $upcoming;

            $children[] = [
                'student_id' => (int) $student->id,
                'full_name' => trim($student->first_name . ' ' . $student->last_name),
                'admission_number' => $student->admission_number,
                'class_name' => $student->classroom->name ?? null,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'upcoming' => $upcoming,
                'invoices' => $invoiceRows,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'family' => [
                    'outstanding' => round($familyOutstanding, 2),
                    'overdue' => round($familyOverdue, 2),
                    'upcoming' => round($familyUpcoming, 2),
                ],
                'children' => $children,
            ],
        ]);
    }

    /**
     * Initiate family STK; on success callback, allocate oldest invoice first across linked children.
     */
    public function prompt(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Parent', 'Guardian'])) {
            abort(403, 'Only parents can initiate family fee payments.');
        }

        $data = $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $phoneNumber = trim($data['phone_number']);
        if (! MpesaGateway::isValidKenyanPhone($phoneNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Use a valid Kenyan mobile number (e.g. 0712345678 or 254712345678).',
            ], 422);
        }

        $students = $this->linkedStudents($request);
        if ($students->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No linked children found.'], 422);
        }

        $familyOutstanding = 0.0;
        foreach ($students as $student) {
            $familyOutstanding += (float) StudentBalanceService::getTotalOutstandingBalance($student, false);
        }
        $familyOutstanding = round($familyOutstanding, 2);
        $amount = round((float) $data['amount'], 2);

        if ($amount > $familyOutstanding + 0.009) {
            return response()->json([
                'success' => false,
                'message' => 'Amount cannot exceed the family outstanding balance of KES ' . number_format($familyOutstanding, 2) . '.',
            ], 422);
        }

        // Anchor STK on first child with outstanding; metadata flags family oldest-first allocation.
        $anchor = $students->first(function (Student $s) {
            return StudentBalanceService::getTotalOutstandingBalance($s, false) > 0.009;
        }) ?? $students->first();

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: (int) $anchor->id,
                phoneNumber: $phoneNumber,
                amount: $amount,
                invoiceId: null,
                adminId: Auth::id(),
                notes: 'Family fees payment (oldest invoice first across children)',
                isSwimming: false,
                sharedAllocations: null
            );
        } catch (\Throwable $e) {
            Log::error('Family fee STK failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate STK Push.',
                'transaction_id' => $result['transaction_id'] ?? null,
            ], 422);
        }

        $transactionId = (int) ($result['transaction_id'] ?? 0);
        if ($transactionId > 0) {
            $tx = PaymentTransaction::find($transactionId);
            if ($tx) {
                $payload = [
                    'family_payment' => true,
                    'allocate_oldest_across_children' => true,
                    'parent_user_id' => $user->id,
                    'student_ids' => $students->pluck('id')->values()->all(),
                    'idempotency_key' => $data['idempotency_key'] ?? null,
                ];
                $tx->admin_notes = 'FAMILY_OLDEST_FIRST:' . json_encode($payload);
                $tx->save();
            }
        }

        $transaction = PaymentTransaction::find($transactionId);

        return response()->json([
            'success' => true,
            'message' => 'STK Push sent. Complete the prompt on your phone.',
            'data' => [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'family_outstanding' => $familyOutstanding,
                'waiting_url' => $transaction ? route('payment.link.waiting', $transaction) : null,
                'status_poll_url' => $transaction ? route('payment.link.transaction.status', $transaction) : null,
            ],
        ]);
    }

    /**
     * Allocate a posted payment oldest-invoice-first across the parent's linked children.
     * Called from M-Pesa completion hooks when family_payment metadata is set; also usable by staff.
     */
    public function allocateFamilyPayment(Payment $payment, array $studentIds): Payment
    {
        return DB::transaction(function () use ($payment, $studentIds) {
            $invoices = Invoice::query()
                ->whereIn('student_id', $studentIds)
                ->notReversed()
                ->published()
                ->where('balance', '>', 0)
                ->orderBy('issued_date')
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            app(\App\Services\OldestInvoiceFirstAllocator::class)
                ->allocatePaymentAcrossInvoices($payment, $invoices);

            return $payment->fresh();
        });
    }
}

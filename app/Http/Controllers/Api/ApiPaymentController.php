<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use App\Services\PaymentAllocationService;
use App\Services\ReceiptService;
use App\Services\StudentBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiPaymentController extends Controller
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
        protected ReceiptService $receiptService,
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $this->assertFinanceOrViewStudent($user, (int) $request->student_id);

        $perPage = (int) $request->input('per_page', 30);

        $paginated = Payment::with(['paymentMethod'])
            ->where('student_id', (int) $request->student_id)
            ->whereNull('deleted_at')
            ->where('reversed', false)
            ->orderByDesc('payment_date')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(function (Payment $p) {
            return [
                'id' => $p->id,
                'receipt_number' => $p->receipt_number,
                'student_id' => $p->student_id,
                'amount' => (float) $p->amount,
                'payment_method' => strtolower(str_replace('_', '_', $p->paymentMethod->code ?? 'cash')),
                'payment_date' => $p->payment_date?->format('Y-m-d'),
                'reference_number' => $p->transaction_code,
                'notes' => $p->narration,
                'status' => 'completed',
                'created_at' => $p->created_at->toIso8601String(),
                'updated_at' => $p->updated_at->toIso8601String(),
            ];
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

    /**
     * Record a payment from the mobile app (maps string payment_method to PaymentMethod code).
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to record payments.');
        }

        $student = Student::findOrFail((int) $request->student_id);
        $paymentMethodId = $this->resolvePaymentMethodId($request->payment_method);

        $transactionCode = $request->filled('reference_number')
            ? trim((string) $request->reference_number)
            : 'MOB-'.strtoupper(uniqid());

        if (Payment::where('student_id', $student->id)->where('transaction_code', $transactionCode)->exists()) {
            $transactionCode .= '-'.random_int(100, 999);
        }

        $balance = StudentBalanceService::getTotalOutstandingBalance($student);
        $amount = (float) $request->amount;
        if (($student->is_alumni || $student->archive) && $amount > $balance + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Payment exceeds outstanding balance for this student.',
            ], 422);
        }

        $payment = null;

        DB::transaction(function () use ($request, $student, $paymentMethodId, $transactionCode, &$payment) {
            $payment = Payment::create([
                'student_id' => $student->id,
                'family_id' => $student->family_id,
                'invoice_id' => null,
                'amount' => $request->amount,
                'payment_method_id' => $paymentMethodId,
                'narration' => $request->notes,
                'transaction_code' => $transactionCode,
                'payment_date' => $request->payment_date,
                'payer_name' => null,
                'payer_type' => 'parent',
                'reversed' => false,
            ]);

            try {
                if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                    $this->allocationService->autoAllocateWithInstallments($payment);
                } else {
                    $this->allocationService->autoAllocate($payment);
                }
            } catch (\Throwable $e) {
                Log::warning('API payment auto-allocation failed: '.$e->getMessage());
            }

            try {
                $this->receiptService->generateReceipt($payment, ['save' => true]);
            } catch (\Throwable $e) {
                Log::warning('API receipt generation failed: '.$e->getMessage());
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount' => (float) $payment->amount,
                'payment_date' => $payment->payment_date?->format('Y-m-d'),
                'transaction_code' => $payment->transaction_code,
            ],
            'message' => 'Payment recorded.',
        ], 201);
    }

    protected function resolvePaymentMethodId(string $slug): int
    {
        $map = [
            'cash' => 'CASH',
            'mpesa' => 'MPESA',
            'bank_transfer' => 'BANK_TRANSFER',
            'cheque' => 'CHEQUE',
            'card' => 'STRIPE',
        ];
        $code = $map[strtolower($slug)] ?? strtoupper($slug);
        $pm = PaymentMethod::active()->where('code', $code)->first();
        if ($pm) {
            return $pm->id;
        }

        return PaymentMethod::active()->orderBy('display_order')->firstOrFail()->id;
    }

    protected function assertFinanceOrViewStudent($user, int $studentId): void
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            return;
        }
        $student = Student::findOrFail($studentId);
        if ($user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $query = Student::where('id', $student->id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403);
            }

            return;
        }
        abort(403);
    }
}

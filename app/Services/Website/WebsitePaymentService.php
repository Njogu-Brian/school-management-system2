<?php

namespace App\Services\Website;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\Website\PaymentPlanRequest;
use App\Services\PaymentGateways\MpesaGateway;
use Illuminate\Support\Facades\Auth;

class WebsitePaymentService
{
    public function __construct(
        protected MpesaGateway $mpesaGateway
    ) {}

    public function initiateMpesaForParent(int $studentId, string $phone, float $amount, ?int $invoiceId = null): array
    {
        $user = Auth::user();
        abort_unless($user && $user->canAccessStudent($studentId), 403);

        if (! MpesaGateway::isValidKenyanPhone($phone)) {
            return ['success' => false, 'message' => 'Invalid Kenyan mobile number.'];
        }

        if (! $invoiceId) {
            $invoice = Invoice::where('student_id', $studentId)
                ->where(function ($q) {
                    $q->where('balance', '>', 0)
                        ->orWhereRaw('(COALESCE(total,0) - COALESCE(paid_amount,0)) > 0');
                })
                ->orderBy('due_date')
                ->first();
            $invoiceId = $invoice?->id;
        }

        $result = $this->mpesaGateway->initiateAdminPromptedPayment(
            studentId: $studentId,
            phoneNumber: $phone,
            amount: $amount,
            invoiceId: $invoiceId,
            adminId: $user->id,
            notes: 'Parent portal self-service payment',
        );

        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'M-Pesa prompt failed.',
            ];
        }

        $transactionId = (int) ($result['transaction_id'] ?? 0);
        $transaction = PaymentTransaction::find($transactionId);

        return [
            'success' => true,
            'message' => 'STK Push sent to your phone. Enter your M-Pesa PIN to complete payment.',
            'data' => [
                'transaction_id' => $transactionId,
                'status_poll_url' => $transaction ? route('payment.link.transaction.status', $transaction) : null,
            ],
        ];
    }

    public function paymentLinkForStudent(int $studentId): array
    {
        return app(\App\Http\Controllers\Api\ApiMpesaPaymentController::class)
            ->paymentLinkUrl(request()->merge([]), $studentId)
            ->getData(true);
    }

    public function requestPaymentPlan(int $studentId, array $data): PaymentPlanRequest
    {
        $user = Auth::user();
        abort_unless($user && $user->canAccessStudent($studentId), 403);

        return PaymentPlanRequest::create([
            'parent_user_id' => $user->id,
            'student_id' => $studentId,
            'requested_amount' => $data['requested_amount'] ?? null,
            'installment_count' => $data['installment_count'] ?? 3,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function recentReceipts(int $studentId, int $limit = 10): array
    {
        abort_unless(Auth::user()?->canAccessStudent($studentId), 403);

        return Payment::query()
            ->where('student_id', $studentId)
            ->where(function ($q) {
                $q->whereNull('reversed')->orWhere('reversed', false);
            })
            ->latest('payment_date')
            ->limit($limit)
            ->get(['id', 'amount', 'payment_date', 'receipt_number', 'payment_method'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'date' => $p->payment_date?->toDateString(),
                'receipt_number' => $p->receipt_number,
                'method' => $p->payment_method,
                'receipt_url' => $p->public_token ? url('/receipt/'.$p->public_token) : null,
            ])
            ->all();
    }

    public function outstandingBalance(int $studentId): float
    {
        abort_unless(Auth::user()?->canAccessStudent($studentId), 403);

        return round((float) Invoice::where('student_id', $studentId)
            ->whereNull('reversed_at')
            ->sum('balance'), 2);
    }

    /**
     * Available payment channels for parent portal (reuses ERP payment link + M-Pesa + bank accounts).
     */
    public function paymentOptions(int $studentId): array
    {
        abort_unless(Auth::user()?->canAccessStudent($studentId), 403);

        $student = Student::findOrFail($studentId);
        $linkData = $this->paymentLinkForStudent($studentId);
        $payUrl = $linkData['data']['url'] ?? null;

        $banks = BankAccount::active()
            ->get(['bank_name', 'account_number', 'branch', 'account_type', 'currency'])
            ->map(fn ($b) => [
                'bank_name' => $b->bank_name,
                'account_number' => $b->account_number,
                'branch' => $b->branch,
                'account_type' => $b->account_type,
                'currency' => $b->currency,
                'reference' => $student->admission_number,
            ])
            ->all();

        return [
            'outstanding' => $this->outstandingBalance($studentId),
            'methods' => [
                [
                    'id' => 'mpesa_stk',
                    'label' => 'M-Pesa STK Push',
                    'description' => 'Instant prompt on your phone',
                ],
                [
                    'id' => 'mpesa_link',
                    'label' => 'M-Pesa / Card via Payment Link',
                    'description' => 'Pay online via secure family payment link',
                    'url' => $payUrl,
                ],
                [
                    'id' => 'bank_transfer',
                    'label' => 'Bank Transfer',
                    'description' => 'Use admission number as payment reference',
                    'accounts' => $banks,
                ],
            ],
        ];
    }

    public function receiptUrl(int $paymentId, int $studentId): ?string
    {
        abort_unless(Auth::user()?->canAccessStudent($studentId), 403);

        $payment = Payment::where('student_id', $studentId)->find($paymentId);
        if (! $payment || ! $payment->public_token) {
            return null;
        }

        return url('/receipt/'.$payment->public_token);
    }
}

<?php

namespace App\Services\Website;

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
}

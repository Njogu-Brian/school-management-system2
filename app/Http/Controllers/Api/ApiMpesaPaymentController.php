<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Services\PaymentGateways\MpesaGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile API: reuse web M-Pesa STK prompt and family payment links (public pay / waiting URLs).
 */
class ApiMpesaPaymentController extends Controller
{
    public function __construct(
        protected MpesaGateway $mpesaGateway
    ) {}

    protected function assertFinanceStaff(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to initiate M-PESA payments.');
        }
    }

    /**
     * Initiate admin STK push (same rules as web finance.mpesa.prompt-payment).
     */
    public function prompt(Request $request, int $id)
    {
        $this->assertFinanceStaff($request);

        $request->merge(['student_id' => $id]);

        $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'nullable|exists:invoices,id',
            'notes' => 'nullable|string|max:500',
            'is_swimming' => 'nullable|boolean',
            'share_with_siblings' => 'nullable|boolean',
            'sibling_allocations' => 'nullable|array',
            'sibling_allocations.*.student_id' => 'required_with:sibling_allocations|exists:students,id',
            'sibling_allocations.*.amount' => 'required_with:sibling_allocations|numeric|min:0',
        ]);

        $phoneNumber = trim($request->phone_number);

        if (! MpesaGateway::isValidKenyanPhone($phoneNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Use a valid Kenyan mobile number (e.g. 0712345678 or 254712345678).',
            ], 422);
        }

        $isShared = $request->boolean('share_with_siblings') && $request->filled('sibling_allocations');
        $sharedAllocations = null;
        $amount = (float) $request->amount;
        $invoiceId = $request->invoice_id ? (int) $request->invoice_id : null;

        if ($isShared && is_array($request->sibling_allocations)) {
            $sharedAllocations = [];
            foreach ($request->sibling_allocations as $a) {
                $am = (float) ($a['amount'] ?? 0);
                if ($am > 0 && isset($a['student_id'])) {
                    $sharedAllocations[] = ['student_id' => (int) $a['student_id'], 'amount' => $am];
                }
            }
            if (empty($sharedAllocations)) {
                return response()->json([
                    'success' => false,
                    'message' => 'When sharing with siblings, at least one sibling must have an amount greater than 0.',
                ], 422);
            }
            $amount = array_sum(array_column($sharedAllocations, 'amount'));
            $invoiceId = null;
        } else {
            if (! $request->boolean('is_swimming', false) && empty($invoiceId)) {
                $firstOutstanding = Invoice::where('student_id', $id)
                    ->where(function ($q) {
                        $q->where('balance', '>', 0)
                            ->orWhereRaw('(COALESCE(total,0) - COALESCE(paid_amount,0)) > 0');
                    })
                    ->orderBy('due_date')
                    ->orderBy('issued_date')
                    ->first();
                if ($firstOutstanding) {
                    $invoiceId = $firstOutstanding->id;
                }
            }
        }

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: $id,
                phoneNumber: $phoneNumber,
                amount: $amount,
                invoiceId: $invoiceId,
                adminId: Auth::id(),
                notes: $request->notes,
                isSwimming: $request->boolean('is_swimming', false),
                sharedAllocations: $sharedAllocations
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate STK Push.',
                'transaction_id' => $result['transaction_id'] ?? null,
            ], 422);
        }

        $transactionId = (int) ($result['transaction_id'] ?? 0);
        $transaction = PaymentTransaction::find($transactionId);

        return response()->json([
            'success' => true,
            'message' => 'STK Push sent. Ask the parent to complete the prompt on their phone.',
            'data' => [
                'transaction_id' => $transactionId,
                'waiting_url' => $transaction ? route('payment.link.waiting', $transaction) : null,
                'status_poll_url' => $transaction ? route('payment.link.transaction.status', $transaction) : null,
            ],
        ]);
    }

    /**
     * Resolve or create an active payment link for the student’s family (or per-student) and return public URLs.
     */
    public function paymentLinkUrl(Request $request, int $id)
    {
        $user = $request->user();
        $student = Student::findOrFail($id);

        if ($user->hasAnyRole(['Parent', 'Guardian'])) {
            if (! $user->canAccessStudent($student->id)) {
                abort(403, 'You do not have access to this student.');
            }
        } elseif (! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to load payment links.');
        }

        if ($student->family_id) {
            $link = ensure_family_payment_link($student->family_id);
        } else {
            $link = PaymentLink::active()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->first();

            if (! $link) {
                $balance = Invoice::where('student_id', $student->id)->get()->sum(fn ($inv) => max(0, (float) $inv->balance));
                $balance = round($balance > 0 ? $balance : 0, 2);

                $link = PaymentLink::create([
                    'student_id' => $student->id,
                    'invoice_id' => null,
                    'family_id' => null,
                    'amount' => $balance,
                    'currency' => 'KES',
                    'description' => 'School fee payment',
                    'status' => 'active',
                    'expires_at' => now()->addDays(90),
                    'max_uses' => 999,
                    'created_by' => Auth::id(),
                    'metadata' => ['source' => 'api_mobile'],
                ]);
            }
        }

        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create a payment link for this student.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_link_id' => $link->id,
                'url' => $link->getPaymentUrl(),
                'short_url' => $link->getShortUrl(),
            ],
        ]);
    }
}

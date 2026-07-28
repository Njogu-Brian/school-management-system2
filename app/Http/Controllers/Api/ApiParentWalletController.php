<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ParentWallet;
use App\Models\ParentWalletSavingPlan;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Services\ParentWalletService;
use App\Services\PaymentGateways\MpesaGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiParentWalletController extends Controller
{
    public function __construct(
        protected ParentWalletService $walletService,
        protected MpesaGateway $mpesaGateway
    ) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);
        $wallet = $this->walletService->getOrCreate((int) $user->parent_id);

        $ledger = $wallet->ledgerEntries()
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'type' => $row->type,
                'amount' => (float) $row->amount,
                'balance_after' => (float) $row->balance_after,
                'meta' => $row->meta,
                'created_at' => optional($row->created_at)?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_info_id' => (int) $wallet->parent_info_id,
                'balance' => (float) $wallet->balance,
                'total_credited' => (float) $wallet->total_credited,
                'total_debited' => (float) $wallet->total_debited,
                'ledger' => $ledger,
            ],
        ]);
    }

    public function topUp(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);

        $validated = $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'saving_plan_id' => 'nullable|integer|exists:parent_wallet_saving_plans,id',
        ]);

        $phone = trim($validated['phone_number']);
        if (! MpesaGateway::isValidKenyanPhone($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Use a valid Kenyan mobile number.',
            ], 422);
        }

        $wallet = $this->walletService->getOrCreate((int) $user->parent_id);
        $student = Student::where('parent_id', $user->parent_id)->where('archive', 0)->orderBy('id')->first();
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'No linked child found for wallet top-up.',
            ], 422);
        }

        $amount = (float) $validated['amount'];
        $purpose = ! empty($validated['saving_plan_id']) ? 'wallet_saving' : 'wallet_topup';
        $accountReference = 'WALLET-'.$wallet->parent_info_id;
        $reference = PaymentTransaction::generateReference();

        $transaction = PaymentTransaction::create([
            'student_id' => $student->id,
            'invoice_id' => null,
            'parent_wallet_id' => $wallet->id,
            'purpose' => $purpose,
            'gateway' => 'mpesa',
            'reference' => $reference,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => 'pending',
            'initiated_by' => $user->id,
            'admin_notes' => $purpose === 'wallet_saving'
                ? 'Parent wallet saving plan #'.($validated['saving_plan_id'] ?? '')
                : 'Parent wallet top-up',
            'phone_number' => $phone,
            'account_reference' => $accountReference,
        ]);

        try {
            $result = $this->mpesaGateway->initiatePayment($transaction, [
                'phone_number' => $phone,
            ]);
        } catch (\Throwable $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ], 500);
        }

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate STK Push.',
                'transaction_id' => $transaction->id,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'STK Push sent. Complete the prompt on your phone.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'purpose' => $purpose,
            ],
        ]);
    }

    public function pay(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $wallet = $this->walletService->getOrCreate((int) $user->parent_id);
        $amount = (float) $validated['amount'];

        try {
            if (! empty($validated['invoice_id'])) {
                $invoice = Invoice::findOrFail($validated['invoice_id']);
                if (! $user->canAccessStudent((int) $invoice->student_id)) {
                    abort(403, 'You do not have access to this invoice.');
                }
                $payment = $this->walletService->payInvoiceFromWallet($wallet, $invoice, $amount, $user->id);
            } else {
                $studentId = (int) ($validated['student_id'] ?? 0);
                if ($studentId <= 0 || ! $user->canAccessStudent($studentId)) {
                    throw ValidationException::withMessages([
                        'student_id' => ['Select a child to pay for.'],
                    ]);
                }
                $student = Student::findOrFail($studentId);
                $this->walletService->payStudentFeesFromWallet($wallet, $student, $amount, null, $user->id, false);
                $payment = null;
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment applied from wallet.',
            'data' => [
                'wallet_balance' => (float) $wallet->fresh()->balance,
                'payment_id' => $payment?->id,
            ],
        ]);
    }

    public function listSavingPlans(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);

        $plans = ParentWalletSavingPlan::where('parent_info_id', $user->parent_id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($p) => $this->formatPlan($p));

        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function storeSavingPlan(Request $request)
    {
        $user = $request->user();
        $this->assertParent($user);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'day_of_week' => 'required|integer|min:0|max:6',
            'remind_at_time' => 'required|date_format:H:i',
            'timezone' => 'nullable|string|max:64',
            'label' => 'nullable|string|max:120',
            'active' => 'nullable|boolean',
        ]);

        $tz = $validated['timezone'] ?? 'Africa/Nairobi';
        $plan = ParentWalletSavingPlan::create([
            'parent_info_id' => $user->parent_id,
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'frequency' => 'weekly',
            'day_of_week' => $validated['day_of_week'],
            'remind_at_time' => $validated['remind_at_time'].':00',
            'timezone' => $tz,
            'next_remind_at' => $this->computeNextRemindAt(
                (int) $validated['day_of_week'],
                $validated['remind_at_time'],
                $tz
            ),
            'active' => $validated['active'] ?? true,
            'label' => $validated['label'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $this->formatPlan($plan)], 201);
    }

    public function updateSavingPlan(Request $request, int $id)
    {
        $user = $request->user();
        $this->assertParent($user);
        $plan = ParentWalletSavingPlan::where('parent_info_id', $user->parent_id)->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:1',
            'day_of_week' => 'sometimes|integer|min:0|max:6',
            'remind_at_time' => 'sometimes|date_format:H:i',
            'timezone' => 'nullable|string|max:64',
            'label' => 'nullable|string|max:120',
            'active' => 'nullable|boolean',
        ]);

        if (isset($validated['remind_at_time']) && ! str_contains($validated['remind_at_time'], ':')) {
            // no-op
        }

        $plan->fill([
            'amount' => $validated['amount'] ?? $plan->amount,
            'day_of_week' => $validated['day_of_week'] ?? $plan->day_of_week,
            'remind_at_time' => isset($validated['remind_at_time'])
                ? $validated['remind_at_time'].':00'
                : $plan->remind_at_time,
            'timezone' => $validated['timezone'] ?? $plan->timezone,
            'label' => array_key_exists('label', $validated) ? $validated['label'] : $plan->label,
            'active' => array_key_exists('active', $validated) ? (bool) $validated['active'] : $plan->active,
        ]);

        $time = substr((string) $plan->remind_at_time, 0, 5);
        $plan->next_remind_at = $this->computeNextRemindAt((int) $plan->day_of_week, $time, $plan->timezone);
        $plan->save();

        return response()->json(['success' => true, 'data' => $this->formatPlan($plan->fresh())]);
    }

    public function destroySavingPlan(Request $request, int $id)
    {
        $user = $request->user();
        $this->assertParent($user);
        $plan = ParentWalletSavingPlan::where('parent_info_id', $user->parent_id)->findOrFail($id);
        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Saving plan deleted.']);
    }

    public function paySavingPlanNow(Request $request, int $id)
    {
        $user = $request->user();
        $this->assertParent($user);
        $plan = ParentWalletSavingPlan::where('parent_info_id', $user->parent_id)->findOrFail($id);

        $request->merge([
            'amount' => (float) $plan->amount,
            'saving_plan_id' => $plan->id,
            'phone_number' => $request->input('phone_number'),
        ]);

        return $this->topUp($request);
    }

    protected function assertParent($user): void
    {
        // Any account linked to parent_info (Parent/Guardian, or staff in Home mode).
        if (! $user || ! $user->parent_id) {
            abort(403, 'Parent wallet is only available to linked parent accounts.');
        }
    }

    protected function formatPlan(ParentWalletSavingPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'amount' => (float) $plan->amount,
            'frequency' => $plan->frequency,
            'day_of_week' => (int) $plan->day_of_week,
            'remind_at_time' => substr((string) $plan->remind_at_time, 0, 5),
            'timezone' => $plan->timezone,
            'next_remind_at' => optional($plan->next_remind_at)?->toIso8601String(),
            'active' => (bool) $plan->active,
            'label' => $plan->label,
        ];
    }

    protected function computeNextRemindAt(int $dayOfWeek, string $timeHi, string $tz): Carbon
    {
        $now = Carbon::now($tz);
        $candidate = $now->copy()->startOfDay()->setTimeFromTimeString(strlen($timeHi) === 5 ? $timeHi.':00' : $timeHi);
        // Carbon: 0 = Sunday
        while ((int) $candidate->dayOfWeek !== $dayOfWeek || $candidate->lessThanOrEqualTo($now)) {
            $candidate->addDay();
            $candidate->setTimeFromTimeString(strlen($timeHi) === 5 ? $timeHi.':00' : $timeHi);
        }

        return $candidate->utc();
    }
}

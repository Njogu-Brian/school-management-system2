<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiMpesaPaymentController;
use App\Services\Website\WebsitePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentPaymentApiController extends Controller
{
    public function __construct(
        protected WebsitePaymentService $payments
    ) {}

    public function summary(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return response()->json([
            'success' => true,
            'data' => [
                'outstanding' => $this->payments->outstandingBalance($student),
                'recent_receipts' => $this->payments->recentReceipts($student),
                'payment_options' => $this->payments->paymentOptions($student),
            ],
        ]);
    }

    public function paymentOptions(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return response()->json([
            'success' => true,
            'data' => $this->payments->paymentOptions($student),
        ]);
    }

    public function mpesaPrompt(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        $validated = $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $result = $this->payments->initiateMpesaForParent(
            $student,
            $validated['phone_number'],
            (float) $validated['amount'],
            $validated['invoice_id'] ?? null
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function paymentLink(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return app(ApiMpesaPaymentController::class)->paymentLinkUrl($request, $student);
    }

    public function requestPlan(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        $validated = $request->validate([
            'requested_amount' => 'nullable|numeric|min:1',
            'installment_count' => 'required|integer|min:2|max:12',
            'reason' => 'nullable|string|max:1000',
        ]);

        $planRequest = $this->payments->requestPaymentPlan($student, $validated);

        return response()->json(['success' => true, 'data' => $planRequest], 201);
    }

    public function receipts(Request $request, int $student): JsonResponse
    {
        $this->assertParent($request);

        return response()->json([
            'success' => true,
            'data' => $this->payments->recentReceipts($student, 25),
        ]);
    }

    protected function assertParent(Request $request): void
    {
        abort_unless(
            $request->user()?->hasAnyRole(['Parent', 'Guardian', 'parent']),
            403,
            'Parent access required.'
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JengaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiJengaController extends Controller
{
    public function __construct(private readonly JengaService $jengaService)
    {
    }

    public function token(Request $request)
    {
        $this->authorizeFinance($request);

        $token = $this->jengaService->getAccessToken($request->boolean('force_refresh'));

        return response()->json([
            'success' => true,
            'message' => 'Jenga token retrieved successfully.',
            'data' => [
                'token_present' => $token !== '',
            ],
        ]);
    }

    public function accountBalance(Request $request, string $countryCode, string $accountId)
    {
        $this->authorizeFinance($request);

        $result = $this->jengaService->accountBalance(strtoupper($countryCode), $accountId);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function accountInquiry(Request $request, string $countryCode, string $accountNumber)
    {
        $this->authorizeFinance($request);

        $result = $this->jengaService->accountInquiry(strtoupper($countryCode), $accountNumber);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function miniStatement(Request $request, string $countryCode, string $accountNumber)
    {
        $this->authorizeFinance($request);

        $result = $this->jengaService->accountMiniStatement(strtoupper($countryCode), $accountNumber);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function fullStatement(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'countryCode' => 'required|string|size:2',
            'accountNumber' => 'required|string|max:50',
            'fromDate' => 'required|date_format:Y-m-d',
            'toDate' => 'required|date_format:Y-m-d|after_or_equal:fromDate',
            'limit' => 'nullable|integer|min:1|max:500',
            'reference' => 'nullable|string|max:100',
            'serial' => 'nullable|string|max:50',
            'postedDateTime' => 'nullable|string|max:100',
            'date' => 'nullable|string|max:30',
            'runningBalance' => 'nullable|array',
            'runningBalance.currency' => 'nullable|string|size:3',
            'runningBalance.amount' => 'nullable|numeric',
        ]);

        $result = $this->jengaService->accountFullStatement($validated);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function disburseMobile(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'source.countryCode' => 'required|string|size:2',
            'source.name' => 'required|string|max:255',
            'source.accountNumber' => 'required|string|max:50',
            'destination.type' => ['required', Rule::in(['mobile'])],
            'destination.countryCode' => 'required|string|size:2',
            'destination.name' => 'required|string|max:255',
            'destination.mobileNumber' => 'required|string|max:20',
            'destination.walletName' => 'required|string|max:100',
            'transfer.type' => 'required|string|max:50',
            'transfer.amount' => 'required|string|max:20',
            'transfer.currencyCode' => 'required|string|size:3',
            'transfer.reference' => 'required|string|max:60',
            'transfer.date' => 'required|date_format:Y-m-d',
            'transfer.description' => 'required|string|max:255',
            'transfer.callbackUrl' => 'required|url|max:1000',
        ]);

        $result = $this->jengaService->disburseToMobileWallet($validated);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function disburseWithinEquity(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'source.countryCode' => 'required|string|size:2',
            'source.name' => 'required|string|max:255',
            'source.accountNumber' => 'required|string|max:50',
            'destination.type' => ['required', Rule::in(['bank'])],
            'destination.countryCode' => 'required|string|size:2',
            'destination.name' => 'required|string|max:255',
            'destination.accountNumber' => 'required|string|max:50',
            'transfer.type' => 'required|string|max:50',
            'transfer.amount' => 'required|string|max:20',
            'transfer.currencyCode' => 'required|string|size:3',
            'transfer.reference' => 'required|string|max:60',
            'transfer.date' => 'required|date_format:Y-m-d',
            'transfer.description' => 'required|string|max:255',
        ]);

        $result = $this->jengaService->disburseWithinEquity($validated);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function disburseRtgs(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'source.countryCode' => 'required|string|size:2',
            'source.currency' => 'required|string|size:3',
            'source.name' => 'required|string|max:255',
            'source.accountNumber' => 'required|string|max:50',
            'destination.type' => ['required', Rule::in(['bank'])],
            'destination.countryCode' => 'required|string|size:2',
            'destination.name' => 'required|string|max:255',
            'destination.bankCode' => 'required|string|max:20',
            'destination.accountNumber' => 'required|string|max:50',
            'transfer.type' => 'required|string|max:50',
            'transfer.amount' => 'required|string|max:20',
            'transfer.currencyCode' => 'required|string|size:3',
            'transfer.reference' => 'required|string|max:60',
            'transfer.date' => 'required|date_format:Y-m-d',
            'transfer.description' => 'required|string|max:255',
            'transfer.purposeOfPaymentCode' => 'required|string|max:20',
        ]);

        $result = $this->jengaService->disburseRtgs($validated);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function rtgsPaymentPurposes(Request $request)
    {
        $this->authorizeFinance($request);
        $validated = $request->validate([
            'signature_string' => 'nullable|string|max:2000',
        ]);
        $result = $this->jengaService->getRtgsPaymentPurposes($validated['signature_string'] ?? null);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function stkUssdPush(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'merchant.countryCode' => 'required|string|size:2',
            'merchant.accountNumber' => 'required|string|max:50',
            'merchant.name' => 'required|string|max:255',
            'payment.ref' => 'required|string|max:60',
            'payment.mobileNumber' => 'required|string|max:20',
            'payment.telco' => 'required|string|max:50',
            'payment.amount' => 'required|string|max:20',
            'payment.currency' => 'required|string|size:3',
            'payment.date' => 'required|date_format:Y-m-d',
            'payment.callBackUrl' => 'required|url|max:1000',
            'payment.pushType' => ['required', Rule::in(['STK', 'USSD'])],
        ]);

        $result = $this->jengaService->initiateStkUssdPush($validated);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function queryTransactionDetails(Request $request, string $reference)
    {
        $this->authorizeFinance($request);
        $result = $this->jengaService->queryTransactionDetails($reference);

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function billers(Request $request)
    {
        $this->authorizeFinance($request);
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:500',
            'page' => 'nullable|integer|min:1',
            'signature_string' => 'nullable|string|max:2000',
        ]);

        $result = $this->jengaService->getBillers(
            $validated['per_page'] ?? null,
            $validated['page'] ?? null,
            $validated['signature_string'] ?? null
        );

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function merchants(Request $request)
    {
        $this->authorizeFinance($request);
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:500',
            'page' => 'nullable|integer|min:1',
            'signature_string' => 'nullable|string|max:2000',
        ]);

        $result = $this->jengaService->getMerchants(
            $validated['per_page'] ?? null,
            $validated['page'] ?? null,
            $validated['signature_string'] ?? null
        );

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    public function signedProxy(Request $request)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'method' => ['required', Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])],
            'endpoint_path' => 'required|string|max:500',
            'signature_string' => 'required|string|max:2000',
            'payload' => 'sometimes|array',
        ]);

        $result = $this->jengaService->signedRequest(
            $validated['method'],
            $validated['endpoint_path'],
            $validated['signature_string'],
            $validated['payload'] ?? []
        );

        return response()->json([
            'success' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
        ], $result['ok'] ? 200 : $result['status']);
    }

    protected function authorizeFinance(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to use Jenga financial APIs.');
        }
    }
}


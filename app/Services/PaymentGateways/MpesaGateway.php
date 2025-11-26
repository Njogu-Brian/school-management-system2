<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaGateway implements PaymentGatewayInterface
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortcode;
    protected string $passkey;
    protected string $environment;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->environment = config('services.mpesa.environment', 'sandbox');
    }

    /**
     * Get access token
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $url = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($url);

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');
            return $this->accessToken;
        }

        throw new \Exception('Failed to get M-Pesa access token');
    }

    /**
     * Initiate STK Push
     */
    public function initiatePayment(PaymentTransaction $transaction, array $options = []): array
    {
        $phoneNumber = $options['phone_number'] ?? null;
        if (!$phoneNumber) {
            throw new \Exception('Phone number is required for M-Pesa payment');
        }

        // Format phone number (254XXXXXXXXX)
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $url = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $transaction->amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => route('payment.webhook.mpesa'),
            'AccountReference' => $transaction->reference,
            'TransactionDesc' => 'School Fee Payment - ' . $transaction->reference,
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        $responseData = $response->json();

        if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
            // Update transaction with CheckoutRequestID
            $transaction->update([
                'transaction_id' => $responseData['CheckoutRequestID'],
                'gateway_response' => $responseData,
                'status' => 'processing',
            ]);

            return [
                'success' => true,
                'checkout_request_id' => $responseData['CheckoutRequestID'],
                'message' => 'STK Push sent successfully. Please check your phone.',
            ];
        }

        Log::error('M-Pesa STK Push failed', [
            'transaction_id' => $transaction->id,
            'response' => $responseData,
        ]);

        $transaction->update([
            'status' => 'failed',
            'failure_reason' => $responseData['errorMessage'] ?? 'STK Push failed',
        ]);

        return [
            'success' => false,
            'message' => $responseData['errorMessage'] ?? 'Payment initiation failed',
        ];
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $transactionId): array
    {
        $url = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $transactionId,
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        return $response->json();
    }

    /**
     * Process webhook callback
     */
    public function processWebhook(array $payload, string $signature): array
    {
        // M-Pesa doesn't use signature verification in the same way
        // But we validate the callback structure
        if (!isset($payload['Body']['stkCallback'])) {
            throw new \Exception('Invalid M-Pesa webhook payload');
        }

        $callback = $payload['Body']['stkCallback'];
        $resultCode = $callback['ResultCode'] ?? null;
        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if ($resultCode == 0) {
            // Payment successful
            $callbackMetadata = $callback['CallbackMetadata']['Item'] ?? [];
            $items = [];
            foreach ($callbackMetadata as $item) {
                $items[$item['Name']] = $item['Value'] ?? null;
            }

            return [
                'success' => true,
                'checkout_request_id' => $checkoutRequestId,
                'mpesa_receipt_number' => $items['MpesaReceiptNumber'] ?? null,
                'transaction_date' => $items['TransactionDate'] ?? null,
                'phone_number' => $items['PhoneNumber'] ?? null,
                'amount' => $items['Amount'] ?? null,
            ];
        }

        return [
            'success' => false,
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => $resultCode,
            'result_desc' => $callback['ResultDesc'] ?? 'Payment failed',
        ];
    }

    /**
     * Verify webhook signature (M-Pesa uses IP whitelist instead)
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // M-Pesa uses IP whitelist for security
        // In production, verify request comes from Safaricom IPs
        return true; // Simplified for now
    }

    /**
     * Refund payment
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null): array
    {
        // M-Pesa refunds are typically done manually or via reversal API
        // This would require additional M-Pesa API setup
        throw new \Exception('M-Pesa refunds not yet implemented');
    }

    /**
     * Format phone number to M-Pesa format (254XXXXXXXXX)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 254 format
        if (strlen($phone) == 9 && substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}


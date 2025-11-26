<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Services\PaymentGateways\MpesaGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    /**
     * Handle M-Pesa webhook
     */
    public function handleMpesa(Request $request)
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Mpesa-Signature', '');

            // Log webhook
            $webhook = PaymentWebhook::create([
                'gateway' => 'mpesa',
                'event_type' => 'stk_callback',
                'event_id' => $payload['Body']['stkCallback']['CheckoutRequestID'] ?? uniqid(),
                'payload' => $payload,
                'signature' => $signature,
            ]);

            // Process webhook
            $gateway = new MpesaGateway();
            $result = $gateway->processWebhook($payload, $signature);

            if ($result['success']) {
                // Find transaction by checkout request ID
                $transaction = PaymentTransaction::where('transaction_id', $result['checkout_request_id'])
                    ->where('gateway', 'mpesa')
                    ->first();

                if ($transaction) {
                    DB::transaction(function () use ($transaction, $result, $webhook) {
                        // Update transaction
                        $transaction->update([
                            'status' => 'completed',
                            'transaction_id' => $result['mpesa_receipt_number'] ?? $transaction->transaction_id,
                            'webhook_data' => $result,
                            'paid_at' => now(),
                        ]);

                        // Update invoice if exists
                        if ($transaction->invoice_id) {
                            $invoice = $transaction->invoice;
                            $invoice->increment('paid_amount', $transaction->amount);
                            $invoice->update(['balance' => $invoice->total_amount - $invoice->paid_amount]);
                            
                            if ($invoice->balance <= 0) {
                                $invoice->update(['status' => 'paid']);
                            }
                        }

                        // Create payment record
                        \App\Models\Payment::create([
                            'student_id' => $transaction->student_id,
                            'invoice_id' => $transaction->invoice_id,
                            'amount' => $transaction->amount,
                            'payment_method' => 'mpesa',
                            'reference' => $result['mpesa_receipt_number'] ?? $transaction->reference,
                            'payment_date' => now(),
                        ]);
                    });

                    $webhook->markAsProcessed();
                } else {
                    $webhook->markAsProcessed('Transaction not found');
                    Log::warning('M-Pesa webhook: Transaction not found', [
                        'checkout_request_id' => $result['checkout_request_id'],
                    ]);
                }
            } else {
                // Payment failed
                $transaction = PaymentTransaction::where('transaction_id', $result['checkout_request_id'])
                    ->where('gateway', 'mpesa')
                    ->first();

                if ($transaction) {
                    $transaction->update([
                        'status' => 'failed',
                        'failure_reason' => $result['result_desc'] ?? 'Payment failed',
                        'webhook_data' => $result,
                    ]);
                }

                $webhook->markAsProcessed();
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('M-Pesa webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripe(Request $request)
    {
        // TODO: Implement Stripe webhook handling
        return response()->json(['message' => 'Stripe webhook not yet implemented'], 501);
    }

    /**
     * Handle PayPal webhook
     */
    public function handlePaypal(Request $request)
    {
        // TODO: Implement PayPal webhook handling
        return response()->json(['message' => 'PayPal webhook not yet implemented'], 501);
    }
}


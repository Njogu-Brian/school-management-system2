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
     * Supports both GET (validation/testing) and POST (actual callbacks)
     */
    public function handleMpesa(Request $request)
    {
        // Log ALL requests to this endpoint
        Log::info('M-PESA Webhook Endpoint Accessed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'query_params' => $request->query->all(),
            'payload' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Handle GET requests (validation, testing, or browser access)
        if ($request->isMethod('GET')) {
            Log::info('M-PESA Webhook GET Request', [
                'message' => 'GET request received - this is normal for validation or testing',
                'query_params' => $request->query->all(),
            ]);

            // Return success response for GET requests (M-PESA validation)
            return response()->json([
                'status' => 'ok',
                'message' => 'M-PESA webhook endpoint is active',
                'method' => 'GET',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }

        // Handle POST requests (actual webhook callbacks)
        try {
            $payload = $request->all();
            $signature = $request->header('X-Mpesa-Signature', '');

            Log::info('M-PESA Webhook POST Request Received', [
                'payload' => $payload,
                'signature' => $signature ? 'present' : 'missing',
                'content_length' => $request->header('Content-Length'),
                'content_type' => $request->header('Content-Type'),
            ]);

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

            Log::info('M-PESA Webhook processed', [
                'success' => $result['success'] ?? false,
                'checkout_request_id' => $result['checkout_request_id'] ?? null,
            ]);

            if ($result['success']) {
                // Find transaction by checkout request ID
                $checkoutRequestId = $result['checkout_request_id'] ?? null;
                
                if (!$checkoutRequestId) {
                    Log::warning('M-PESA webhook: Missing checkout_request_id', [
                        'result' => $result,
                    ]);
                    $webhook->markAsProcessed('Missing checkout_request_id');
                    return response()->json(['success' => false, 'message' => 'Missing checkout_request_id'], 400);
                }

                $transaction = PaymentTransaction::where('transaction_id', $checkoutRequestId)
                    ->where('gateway', 'mpesa')
                    ->first();

                Log::info('M-PESA webhook: Transaction lookup', [
                    'checkout_request_id' => $checkoutRequestId,
                    'transaction_found' => $transaction !== null,
                    'transaction_id' => $transaction?->id,
                ]);

                if ($transaction) {
                    // Skip if already processed
                    if ($transaction->status === 'completed') {
                        Log::info('M-Pesa webhook: Transaction already processed', [
                            'transaction_id' => $transaction->id,
                            'checkout_request_id' => $result['checkout_request_id'],
                        ]);
                        $webhook->markAsProcessed('Already processed');
                        return response()->json(['success' => true], 200);
                    }

                    DB::transaction(function () use ($transaction, $result, $webhook) {
                        // Update transaction with M-PESA receipt details
                        $transaction->update([
                            'status' => 'completed',
                            'mpesa_receipt_number' => $result['mpesa_receipt_number'] ?? null,
                            'webhook_data' => $result,
                            'paid_at' => now(),
                            'mpesa_transaction_date' => isset($result['transaction_date']) 
                                ? \Carbon\Carbon::parse($result['transaction_date']) 
                                : now(),
                        ]);

                        // Reload transaction to get fresh relationships
                        $transaction->refresh();

                        // Determine payment channel
                        $paymentChannel = 'stk_push';
                        if ($transaction->payment_link_id) {
                            $paymentChannel = 'payment_link';
                        } elseif ($transaction->initiated_by) {
                            $paymentChannel = 'stk_push'; // Admin-prompted
                        }

                        // Get or create M-PESA payment method
                        $mpesaMethod = \App\Models\PaymentMethod::firstOrCreate(
                            ['code' => 'mpesa'],
                            [
                                'name' => 'M-PESA',
                                'is_online' => true,
                                'is_active' => true,
                                'requires_reference' => true,
                            ]
                        );

                        $allocationService = app(\App\Services\PaymentAllocationService::class);
                        $receiptService = app(\App\Services\ReceiptService::class);
                        $txnCode = $result['mpesa_receipt_number'] ?? $transaction->reference;

                        // Shared payment: create one payment per sibling allocation, allocate, receipt, notify each
                        if ($transaction->is_shared && !empty($transaction->shared_allocations)) {
                            $firstPaymentId = null;
                            foreach ($transaction->shared_allocations as $alloc) {
                                $sid = (int) ($alloc['student_id'] ?? 0);
                                $amt = (float) ($alloc['amount'] ?? 0);
                                if ($sid <= 0 || $amt <= 0) {
                                    continue;
                                }
                                $stu = \App\Models\Student::find($sid);
                                if (!$stu) {
                                    continue;
                                }
                                $payment = \App\Models\Payment::create([
                                    'student_id' => $sid,
                                    'invoice_id' => null,
                                    'payment_link_id' => $transaction->payment_link_id,
                                    'payment_transaction_id' => $transaction->id,
                                    'family_id' => $stu->family_id,
                                    'amount' => $amt,
                                    'payment_method_id' => $mpesaMethod->id,
                                    'payment_method' => 'mpesa',
                                    'payment_channel' => $paymentChannel,
                                    'mpesa_receipt_number' => $result['mpesa_receipt_number'] ?? null,
                                    'mpesa_phone_number' => $result['phone_number'] ?? $transaction->phone_number,
                                    'transaction_code' => $txnCode,
                                    'payer_name' => $stu->full_name ?? trim($stu->first_name . ' ' . $stu->last_name),
                                    'payer_type' => 'parent',
                                    'narration' => 'M-PESA Payment (shared) - ' . $txnCode,
                                    'payment_date' => now(),
                                    'receipt_date' => now(),
                                    'status' => 'approved',
                                    'created_by' => $transaction->initiated_by,
                                ]);
                                if ($firstPaymentId === null) {
                                    $firstPaymentId = $payment->id;
                                }
                                try {
                                    $allocationService->autoAllocate($payment, $sid);
                                } catch (\Exception $e) {
                                    Log::error('Payment allocation failed (shared)', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                                }
                                try {
                                    $pdfPath = $receiptService->generateReceipt($payment, ['save' => true]);
                                    $this->sendPaymentConfirmation($payment, $pdfPath ?? null);
                                } catch (\Exception $e) {
                                    Log::error('Receipt/notify failed (shared)', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                                }
                            }
                            $transaction->update(['payment_id' => $firstPaymentId]);
                            if ($transaction->payment_link_id) {
                                $transaction->paymentLink?->update(['payment_id' => $firstPaymentId]);
                            }
                        } else {
                            // Single payment
                            $payment = \App\Models\Payment::create([
                                'student_id' => $transaction->student_id,
                                'invoice_id' => $transaction->invoice_id,
                                'payment_link_id' => $transaction->payment_link_id,
                                'payment_transaction_id' => $transaction->id,
                                'family_id' => $transaction->student->family_id ?? null,
                                'amount' => $transaction->amount,
                                'payment_method_id' => $mpesaMethod->id,
                                'payment_method' => 'mpesa',
                                'payment_channel' => $paymentChannel,
                                'mpesa_receipt_number' => $result['mpesa_receipt_number'] ?? null,
                                'mpesa_phone_number' => $result['phone_number'] ?? $transaction->phone_number,
                                'transaction_code' => $txnCode,
                                'payer_name' => $transaction->student->getFullNameAttribute(),
                                'payer_type' => 'parent',
                                'narration' => 'M-PESA Payment - ' . $txnCode,
                                'payment_date' => now(),
                                'receipt_date' => now(),
                                'status' => 'approved',
                                'created_by' => $transaction->initiated_by,
                            ]);
                            $transaction->update(['payment_id' => $payment->id]);
                            try {
                                if ($transaction->invoice_id) {
                                    $allocationService->allocateToInvoice($payment, $transaction->invoice);
                                } else {
                                    $allocationService->autoAllocate($payment, $transaction->student_id);
                                }
                            } catch (\Exception $e) {
                                Log::error('Payment allocation failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                            }
                            if ($transaction->payment_link_id) {
                                $transaction->paymentLink?->update(['payment_id' => $payment->id]);
                            }
                            $pdfPath = null;
                            try {
                                $pdfPath = $receiptService->generateReceipt($payment, ['save' => true]);
                            } catch (\Exception $e) {
                                Log::error('Failed to generate receipt for M-PESA payment', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                            }
                            $this->sendPaymentConfirmation($payment, $pdfPath ?? null);
                        }

                        // Handle POS order payment if exists
                        $posOrder = \App\Models\Pos\Order::where('payment_transaction_id', $transaction->id)->first();
                        if ($posOrder) {
                            $this->processPosOrderPayment($posOrder, $transaction);
                        }
                    });

                    $webhook->markAsProcessed();
                } else {
                    $webhook->markAsProcessed('Transaction not found');
                    Log::warning('M-Pesa webhook: Transaction not found', [
                        'checkout_request_id' => $checkoutRequestId,
                        'available_transactions' => PaymentTransaction::where('gateway', 'mpesa')
                            ->where('status', 'processing')
                            ->pluck('transaction_id', 'id')
                            ->toArray(),
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

                    // Notify admin if this was an admin-prompted payment
                    if ($transaction->initiated_by) {
                        $this->notifyAdminOfFailure($transaction);
                    }
                }

                $webhook->markAsProcessed();
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('M-Pesa webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Still return success to M-PESA to avoid retries
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

    /**
     * Process POS order payment
     */
    protected function processPosOrderPayment(\App\Models\Pos\Order $order, PaymentTransaction $transaction)
    {
        // Mark order as paid
        $order->markAsPaid($transaction->gateway, $transaction->transaction_id);
        $order->payment_transaction_id = $transaction->id;
        $order->save();

        // Mark requirements as received for items purchased through POS
        foreach ($order->items as $item) {
            if ($item->requirement_template_id && $order->student_id) {
                $studentRequirements = \App\Models\StudentRequirement::where('pos_order_id', $order->id)
                    ->where('pos_order_item_id', $item->id)
                    ->get();

                foreach ($studentRequirements as $requirement) {
                    $quantityToAdd = min(
                        $item->quantity,
                        $requirement->quantity_required - $requirement->quantity_collected
                    );

                    if ($quantityToAdd > 0) {
                        $requirement->quantity_collected += $quantityToAdd;
                        $requirement->collected_at = now();
                        $requirement->updateStatus();
                        $requirement->save();
                    }
                }
            }

            // Fulfill order items if in stock
            if ($item->product && $item->product->isInStock()) {
                $fulfillQuantity = min($item->quantity, $item->product->stock_quantity);
                if ($fulfillQuantity > 0) {
                    $item->fulfill($fulfillQuantity);

                    // Update stock
                    if ($item->product->track_stock) {
                        if ($item->variant) {
                            $item->variant->stock_quantity = max(0, $item->variant->stock_quantity - $fulfillQuantity);
                            $item->variant->save();
                        } else {
                            $item->product->stock_quantity = max(0, $item->product->stock_quantity - $fulfillQuantity);
                            $item->product->save();
                        }
                    }
                }
            }
        }

        // Send notification to parent
        $this->sendPosOrderConfirmation($order);
    }

    /**
     * Send payment confirmation notification
     */
    protected function sendPaymentConfirmation(\App\Models\Payment $payment, ?string $pdfPath = null)
    {
        try {
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);
            $paymentController->sendPaymentNotifications($payment);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admin of payment failure
     */
    protected function notifyAdminOfFailure(\App\Models\PaymentTransaction $transaction)
    {
        try {
            if ($transaction->initiated_by) {
                $admin = \App\Models\User::find($transaction->initiated_by);
                
                if ($admin && $admin->email) {
                    $student = $transaction->student;
                    
                    $message = "Payment Request Failed\n\n";
                    $message .= "Student: {$student->first_name} {$student->last_name} ({$student->admission_number})\n";
                    $message .= "Amount: KES " . number_format($transaction->amount, 2) . "\n";
                    $message .= "Phone: " . $transaction->phone_number . "\n";
                    $message .= "Reason: " . $transaction->failure_reason . "\n";
                    
                    \Illuminate\Support\Facades\Mail::to($admin->email)
                        ->send(new \App\Mail\GenericMail(
                            'Payment Request Failed',
                            $message
                        ));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admin of payment failure', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send POS order confirmation notification
     */
    protected function sendPosOrderConfirmation(\App\Models\Pos\Order $order)
    {
        if (!$order->parent && !$order->student?->parent) {
            return;
        }

        $parent = $order->parent ?? $order->student->parent;
        $student = $order->student;
        
        try {
            $commService = app(\App\Services\CommunicationService::class);

            // Build order summary
            $orderSummary = "Order #{$order->order_number}\n\n";
            $orderSummary .= "Items Purchased:\n";
            foreach ($order->items as $item) {
                $orderSummary .= "- {$item->product_name}";
                if ($item->variant_name) {
                    $orderSummary .= " ({$item->variant_name})";
                }
                $orderSummary .= " x{$item->quantity} = KES " . number_format($item->total_price, 2) . "\n";
            }
            $orderSummary .= "\nSubtotal: KES " . number_format($order->subtotal, 2) . "\n";
            if ($order->discount_amount > 0) {
                $orderSummary .= "Discount: -KES " . number_format($order->discount_amount, 2) . "\n";
            }
            $orderSummary .= "Total: KES " . number_format($order->total_amount, 2) . "\n";
            $orderSummary .= "Paid: KES " . number_format($order->paid_amount, 2) . "\n";
            
            if ($order->balance > 0) {
                $orderSummary .= "\n⚠️ Balance Remaining: KES " . number_format($order->balance, 2);
            } else {
                $orderSummary .= "\n✅ Payment Complete!";
            }

            // Requirements status
            $requirements = \App\Models\StudentRequirement::where('pos_order_id', $order->id)->get();
            if ($requirements->count() > 0) {
                $orderSummary .= "\n\nRequirements Status:\n";
                foreach ($requirements as $req) {
                    $status = $req->status === 'complete' ? '✅' : ($req->status === 'partial' ? '⚠️' : '❌');
                    $orderSummary .= "{$status} {$req->requirementTemplate->requirementType->name}: ";
                    $orderSummary .= number_format($req->quantity_collected, 2) . "/" . number_format($req->quantity_required, 2) . "\n";
                }
            }

            $message = "Dear Parent,\n\n";
            if ($student) {
                $message .= "Payment confirmed for {$student->first_name} {$student->last_name}:\n\n";
            }
            $message .= $orderSummary;
            $message .= "\n\nThank you for your purchase!";

            // Send SMS
            if ($parent->phone) {
                $commService->sendSMS('parent', $parent->id, $parent->phone, $message, 'Order Payment Confirmation');
            }

            // Send Email
            if ($parent->email) {
                $htmlMessage = nl2br($message);
                $commService->sendEmail('parent', $parent->id, $parent->email, 'Order Payment Confirmation - ' . $order->order_number, $htmlMessage);
            }

            \App\Models\ActivityLog::log('create', $order, "Payment confirmation sent for POS order {$order->order_number}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send POS order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}


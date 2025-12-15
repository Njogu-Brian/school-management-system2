<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Order;
use App\Models\Pos\PublicShopLink;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PosService $posService;

    public function __construct(PaymentService $paymentService, PosService $posService)
    {
        $this->paymentService = $paymentService;
        $this->posService = $posService;
    }

    public function initiatePayment(Request $request, $token, Order $order)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return redirect()->route('pos.shop.order-confirmation', ['token' => $token, 'order' => $order->id])
                ->with('info', 'This order has already been paid.');
        }

        $validated = $request->validate([
            'gateway' => 'required|in:mpesa,stripe,paypal',
            'phone_number' => 'required_if:gateway,mpesa|string',
        ]);

        try {
            $student = $order->student;
            if (!$student) {
                return redirect()->back()
                    ->with('error', 'Order must be linked to a student for online payment.');
            }

            // Create a temporary invoice for the order (or use existing invoice system)
            // For now, we'll create a PaymentTransaction directly
            $transaction = PaymentTransaction::create([
                'student_id' => $student->id,
                'invoice_id' => null, // POS orders don't use invoices
                'gateway' => $validated['gateway'],
                'reference' => PaymentTransaction::generateReference(),
                'amount' => $order->balance > 0 ? $order->balance : $order->total_amount,
                'currency' => 'KES',
                'status' => 'pending',
                'transaction_id' => 'POS-' . $order->order_number,
            ]);

            // Link transaction to order
            $order->payment_transaction_id = $transaction->id;
            $order->save();

            // Initiate payment
            $options = [];
            if ($validated['gateway'] === 'mpesa' && isset($validated['phone_number'])) {
                $options['phone_number'] = $validated['phone_number'];
            }

            $gatewayInstance = $this->paymentService->getGateway($validated['gateway']);
            $result = $gatewayInstance->initiatePayment($transaction, $options);

            if (!$result['success']) {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $result['message'] ?? 'Payment initiation failed',
                ]);

                return redirect()->back()
                    ->with('error', 'Payment initiation failed: ' . ($result['message'] ?? 'Unknown error'));
            }

            return redirect()->route('pos.shop.payment-status', ['token' => $token, 'order' => $order->id])
                ->with('success', 'Payment initiated. Please complete the payment on your device.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Payment initiation failed: ' . $e->getMessage());
        }
    }

    public function paymentStatus($token, Order $order)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();
        $order->load('paymentTransaction');

        return view('pos.shop.payment-status', compact('link', 'order'));
    }

    public function verifyPayment(Request $request, $token, Order $order)
    {
        if (!$order->payment_transaction_id) {
            return redirect()->back()
                ->with('error', 'No payment transaction found for this order.');
        }

        $transaction = PaymentTransaction::findOrFail($order->payment_transaction_id);

        try {
            $result = $this->paymentService->verifyPayment($transaction);

            if ($transaction->isSuccessful()) {
                $this->processSuccessfulPayment($order, $transaction);
            }

            return redirect()->back()
                ->with('success', 'Payment status updated.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    protected function processSuccessfulPayment(Order $order, PaymentTransaction $transaction)
    {
        DB::transaction(function () use ($order, $transaction) {
            // Mark order as paid
            $order->markAsPaid($transaction->gateway, $transaction->transaction_id);
            $order->payment_transaction_id = $transaction->id;
            $order->save();

            // Mark requirements as received for items purchased through POS
            foreach ($order->items as $item) {
                if ($item->requirement_template_id && $order->student_id) {
                    $this->markRequirementAsReceived($order, $item);
                }
            }

            // Fulfill order items if in stock
            foreach ($order->items as $item) {
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
            $this->sendPaymentConfirmation($order);
        });
    }

    protected function markRequirementAsReceived(Order $order, $orderItem)
    {
        $studentRequirements = \App\Models\StudentRequirement::where('pos_order_id', $order->id)
            ->where('pos_order_item_id', $orderItem->id)
            ->get();

        foreach ($studentRequirements as $requirement) {
            $quantityToAdd = min(
                $orderItem->quantity,
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

    protected function sendPaymentConfirmation(Order $order)
    {
        if (!$order->parent && !$order->student?->parent) {
            return;
        }

        $parent = $order->parent ?? $order->student->parent;
        $student = $order->student;
        
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

        \App\Models\ActivityLog::log('create', $order, "Payment confirmation sent for order {$order->order_number}");
    }
}


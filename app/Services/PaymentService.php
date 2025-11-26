<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Invoice;
use App\Models\Student;
use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\MpesaGateway;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Get gateway instance
     */
    protected function getGateway(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'mpesa' => new MpesaGateway(),
            'stripe' => throw new \Exception('Stripe gateway not yet implemented'),
            'paypal' => throw new \Exception('PayPal gateway not yet implemented'),
            default => throw new \Exception("Unknown gateway: {$gateway}"),
        };
    }

    /**
     * Initiate online payment
     */
    public function initiatePayment(
        Student $student,
        Invoice $invoice,
        string $gateway,
        array $options = []
    ): PaymentTransaction {
        // Create payment transaction
        $transaction = PaymentTransaction::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'gateway' => $gateway,
            'reference' => PaymentTransaction::generateReference(),
            'amount' => $invoice->balance > 0 ? $invoice->balance : $invoice->total_amount,
            'currency' => 'KES',
            'status' => 'pending',
        ]);

        try {
            // Get gateway and initiate payment
            $gatewayInstance = $this->getGateway($gateway);
            $result = $gatewayInstance->initiatePayment($transaction, $options);

            if (!$result['success']) {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $result['message'] ?? 'Payment initiation failed',
                ]);
            }

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Payment initiation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(PaymentTransaction $transaction): array
    {
        try {
            $gateway = $this->getGateway($transaction->gateway);
            $result = $gateway->verifyPayment($transaction->transaction_id);

            // Update transaction if status changed
            if (isset($result['status']) && $result['status'] !== $transaction->status) {
                $transaction->update([
                    'status' => $result['status'],
                    'gateway_response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}


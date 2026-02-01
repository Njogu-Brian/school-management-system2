<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\BankStatementTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSiblingPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $paymentId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $transactionId, int $paymentId)
    {
        $this->transactionId = $transactionId;
        $this->paymentId = $paymentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $transaction = BankStatementTransaction::find($this->transactionId);
            if (!$transaction) {
                Log::warning('Transaction not found for sibling payments processing', [
                    'transaction_id' => $this->transactionId
                ]);
                return;
            }

            $payment = Payment::find($this->paymentId);
            if (!$payment) {
                Log::warning('Payment not found for sibling payments processing', [
                    'payment_id' => $this->paymentId
                ]);
                return;
            }

            $receiptService = app(\App\Services\ReceiptService::class);
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);

            // Process main payment first (receipt and notifications)
            try {
                $receiptService->generateReceipt($payment, ['save' => true]);
            } catch (\Exception $e) {
                Log::warning('Receipt generation failed for main payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }

            try {
                $paymentController->sendPaymentNotifications($payment);
            } catch (\Exception $e) {
                Log::warning('Payment notification failed for main payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Process sibling payments if transaction is shared
            if ($transaction->is_shared && $transaction->shared_allocations) {
                $sharedReceiptNumber = $payment->shared_receipt_number;
                $siblingPayments = collect();

                if ($sharedReceiptNumber) {
                    $siblingPayments = Payment::where('shared_receipt_number', $sharedReceiptNumber)
                        ->where('id', '!=', $payment->id)
                        ->get();
                } else {
                    foreach ($transaction->shared_allocations as $allocation) {
                        $siblingPayment = Payment::where('student_id', $allocation['student_id'])
                            ->where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                            ->where('id', '!=', $payment->id)
                            ->first();
                        if ($siblingPayment) {
                            $siblingPayments->push($siblingPayment);
                        }
                    }
                }

                foreach ($siblingPayments as $siblingPayment) {
                    try {
                        // Generate receipt for sibling payment
                        $receiptService->generateReceipt($siblingPayment, ['save' => true]);

                        // Send notifications for sibling payment
                        try {
                            $paymentController->sendPaymentNotifications($siblingPayment);
                        } catch (\Exception $e) {
                            Log::warning('Payment notification failed for sibling payment', [
                                'payment_id' => $siblingPayment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Receipt generation failed for sibling payment', [
                            'payment_id' => $siblingPayment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('Sibling payments processed successfully', [
                'transaction_id' => $this->transactionId,
                'payment_id' => $this->paymentId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process sibling payments', [
                'transaction_id' => $this->transactionId,
                'payment_id' => $this->paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Process sibling payments job failed permanently', [
            'transaction_id' => $this->transactionId,
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage()
        ]);
    }
}


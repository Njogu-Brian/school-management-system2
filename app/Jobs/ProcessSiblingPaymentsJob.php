<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
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
            $isC2B = false;
            if (!$transaction) {
                $transaction = MpesaC2BTransaction::find($this->transactionId);
                $isC2B = (bool) $transaction;
            }

            $payment = Payment::find($this->paymentId);
            if (!$payment) {
                Log::warning('Payment not found for sibling payments processing', [
                    'payment_id' => $this->paymentId
                ]);
                return;
            }

            // Swimming payments go to swimming wallets, not fee invoices - skip fee receipts
            $isSwimming = false;
            if ($transaction) {
                $isSwimming = (bool) ($transaction->is_swimming_transaction ?? false);
            }

            $receiptService = app(\App\Services\ReceiptService::class);
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);

            // Process main payment (receipt and notifications) - skip for swimming
            if (!$isSwimming) {
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
            }

            // Process sibling payments if transaction is shared
            if ($transaction && $transaction->is_shared && $transaction->shared_allocations) {
                $sharedReceiptNumber = $payment->shared_receipt_number;
                $siblingPayments = collect();

                if ($sharedReceiptNumber) {
                    $siblingPayments = Payment::where('shared_receipt_number', $sharedReceiptNumber)
                        ->where('reversed', false)
                        ->where('id', '!=', $payment->id)
                        ->get();
                } else {
                    $baseCode = $isC2B ? ($transaction->trans_id ?? null) : ($transaction->reference_number ?? null);
                    if ($baseCode) {
                        $siblingPayments = Payment::where('transaction_code', 'LIKE', $baseCode . '%')
                            ->where('reversed', false)
                            ->where('id', '!=', $payment->id)
                            ->get();
                    } else {
                        foreach ($transaction->shared_allocations as $allocation) {
                            $siblingPayment = Payment::where('student_id', $allocation['student_id'])
                                ->where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                                ->where('reversed', false)
                                ->where('id', '!=', $payment->id)
                                ->first();
                            if ($siblingPayment) {
                                $siblingPayments->push($siblingPayment);
                            }
                        }
                    }
                }

                foreach ($siblingPayments as $siblingPayment) {
                    if ($isSwimming) {
                        continue; // Swimming: no fee receipts or notifications
                    }
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
                'payment_id' => $this->paymentId,
                'transaction_type' => $isC2B ? 'c2b' : 'bank'
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


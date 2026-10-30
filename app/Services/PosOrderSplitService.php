<?php

namespace App\Services;

use App\Models\Pos\Order;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

class PosOrderSplitService
{
    public function __construct(protected PosService $posService)
    {
    }

    /**
     * Apply POS legs of a bank/C2B fee split.
     *
     * @param  array<int, array{student_id:int, pos_order_id:int, amount:float}>  $allocations
     */
    public function apply(object $transaction, bool $isC2B, array $allocations): float
    {
        $ref = $isC2B
            ? (string) ($transaction->trans_id ?? '')
            : (string) ($transaction->reference_number ?? '');

        $total = 0.0;

        foreach ($allocations as $row) {
            $studentId = (int) ($row['student_id'] ?? 0);
            $orderId = (int) ($row['pos_order_id'] ?? 0);
            $amount = round((float) ($row['amount'] ?? 0), 2);

            if ($studentId <= 0 || $orderId <= 0 || $amount <= 0) {
                continue;
            }

            $order = Order::with(['items', 'student'])->findOrFail($orderId);
            if ((int) $order->student_id !== $studentId) {
                throw new \RuntimeException(
                    'POS order #'.$order->order_number.' does not belong to the selected student.'
                );
            }

            if ($order->payment_status === 'paid') {
                throw new \RuntimeException('POS order #'.$order->order_number.' is already paid.');
            }

            $balance = (float) ($order->balance > 0 ? $order->balance : $order->total_amount);
            if ($amount + 0.01 < $balance) {
                throw new \RuntimeException(
                    'POS order #'.$order->order_number.' balance is KES '.number_format($balance, 2)
                    .'; allocate the full remaining balance.'
                );
            }
            if ($amount - $balance > 0.01) {
                throw new \RuntimeException(
                    'POS allocation for order #'.$order->order_number.' exceeds the order balance.'
                );
            }

            Student::findOrFail($studentId);

            $code = $ref !== ''
                ? $ref.'-POS-'.$order->id
                : 'POS-SPLIT-'.$order->id;

            $this->posService->confirmPayment(
                $order,
                $code,
                $isC2B ? 'mpesa_c2b_split' : 'bank_split'
            );
            $total += $balance;

            Log::info('POS order paid via fee split', [
                'order_id' => $order->id,
                'transaction_ref' => $ref,
                'amount' => $balance,
                'is_c2b' => $isC2B,
            ]);
        }

        return round($total, 2);
    }
}

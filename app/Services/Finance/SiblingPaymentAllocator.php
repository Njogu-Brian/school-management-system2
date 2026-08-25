<?php

namespace App\Services\Finance;

/**
 * Smart split of one M-PESA payment across sibling fee balances.
 *
 * Rules:
 * - Exact (or over) the family total → clear every child; extra credits the first.
 * - If the payment can fully settle all but one child, do that and leave the
 *   shortfall on the largest invoice (no odd leftover splits).
 * - Otherwise share equally, rounding each share to the nearest 10.
 */
class SiblingPaymentAllocator
{
    public static function roundToTen(float $amount): float
    {
        return (float) (round($amount / 10) * 10);
    }

    /**
     * @param  array<int|string, float|int>  $balances  keyed by student id
     * @return array<int|string, float>  same keys
     */
    public static function allocate(array $balances, float $payment): array
    {
        $keys = array_keys($balances);
        $values = array_map(static fn ($v) => (float) $v, array_values($balances));
        $n = count($values);
        $alloc = array_fill(0, $n, 0.0);

        if ($n === 0 || $payment <= 0) {
            return $keys === [] ? [] : array_combine($keys, $alloc);
        }

        $total = array_sum($values);
        if ($payment >= $total - 0.009) {
            foreach ($values as $i => $balance) {
                $alloc[$i] = $balance;
            }
            $extra = $payment - $total;
            if ($extra > 0) {
                $alloc[0] += $extra;
            }

            return array_combine($keys, $alloc);
        }

        $max = max($values);
        if ($payment >= $total - $max - 0.009) {
            $shortfall = $total - $payment;
            $absorbIdx = (int) array_search($max, $values, true);
            foreach ($values as $i => $balance) {
                $alloc[$i] = $i === $absorbIdx ? max(0.0, $balance - $shortfall) : $balance;
            }

            return array_combine($keys, self::reconcileToPayment($alloc, $payment, $values));
        }

        $remaining = $payment;
        $open = [];
        foreach ($values as $i => $balance) {
            if ($balance > 0) {
                $open[] = $i;
            }
        }

        while ($remaining >= 5 && $open !== []) {
            $share = self::roundToTen($remaining / count($open));
            if ($share < 10 && $remaining >= 10) {
                $share = 10.0;
            }
            if ($share <= 0) {
                break;
            }

            $nextOpen = [];
            $progressed = false;
            foreach ($open as $i) {
                $room = $values[$i] - $alloc[$i];
                $give = min($share, $room, $remaining);
                $give = self::roundToTen($give);
                if ($give > $remaining) {
                    $give = $remaining;
                }
                if ($give > 0) {
                    $alloc[$i] += $give;
                    $remaining -= $give;
                    $progressed = true;
                }
                if (($values[$i] - $alloc[$i]) >= 5 && $remaining > 0) {
                    $nextOpen[] = $i;
                }
            }

            if (! $progressed) {
                break;
            }
            $open = $nextOpen;
        }

        if ($remaining > 0) {
            foreach ($values as $i => $balance) {
                if ($remaining <= 0) {
                    break;
                }
                $room = $balance - $alloc[$i];
                if ($room <= 0) {
                    continue;
                }
                $give = min($room, $remaining);
                $alloc[$i] += $give;
                $remaining -= $give;
            }
        }

        return array_combine($keys, self::reconcileToPayment($alloc, $payment, $values));
    }

    /**
     * @param  array<int, float>  $alloc
     * @param  array<int, float>  $caps
     * @return array<int, float>
     */
    private static function reconcileToPayment(array $alloc, float $payment, array $caps): array
    {
        $diff = round($payment - array_sum($alloc), 2);
        if (abs($diff) < 0.009) {
            return $alloc;
        }

        foreach ($alloc as $i => $amount) {
            $room = $caps[$i] - $amount;
            if ($diff > 0 && $room >= $diff) {
                $alloc[$i] += $diff;

                return $alloc;
            }
            if ($diff < 0 && $amount >= abs($diff)) {
                $alloc[$i] += $diff;

                return $alloc;
            }
        }

        $alloc[0] = max(0.0, $alloc[0] + $diff);

        return $alloc;
    }
}

<?php

namespace App\Services;

use App\Models\FeePaymentPlan;
use App\Models\Invoice;
use App\Models\PaymentThreshold;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeeClearanceStatusService
{
    public function compute(Student $student, Term $term, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?: Carbon::now();

        $termInvoices = Invoice::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->where('status', '!=', 'reversed')
            ->get();

        $totalFees = (float) $termInvoices->sum('total');
        $totalPaid = (float) $termInvoices->sum('paid_amount');
        $balance = (float) $termInvoices->sum(function ($inv) {
            if (isset($inv->balance)) {
                return max(0, (float) $inv->balance);
            }
            $t = (float) ($inv->total ?? 0);
            $p = (float) ($inv->paid_amount ?? 0);
            return max(0, $t - $p);
        });

        $threshold = PaymentThreshold::where('term_id', $term->id)
            ->where('student_category_id', $student->category_id)
            ->where('is_active', true)
            ->first();

        $minimumPercentage = $threshold ? (float) $threshold->minimum_percentage : null;
        $percentagePaid = ($totalFees > 0) ? (($totalPaid / $totalFees) * 100) : null;

        $finalDeadline = null;
        if ($threshold && $term->opening_date) {
            $finalDeadline = $threshold->calculateFinalDeadlineDate($term->opening_date)->startOfDay();
        }

        $paymentPlan = FeePaymentPlan::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->orderByDesc('id')
            ->first();

        $planStatus = $paymentPlan?->status;
        $hasValidPlan = $paymentPlan && in_array($paymentPlan->status, ['active', 'compliant', 'overdue'], true);

        // Status resolution (privacy-safe: amounts only used internally here)
        $status = 'pending';
        $reason = 'below_threshold';

        if ($totalFees <= 0) {
            $status = 'cleared';
            $reason = 'no_fees';
        } elseif ($balance <= 0) {
            $status = 'cleared';
            $reason = 'fully_paid';
        } elseif ($hasValidPlan) {
            $status = 'cleared';
            $reason = 'payment_plan';
        } elseif (!$threshold) {
            // If no threshold configured, do not block by default.
            $status = 'cleared';
            $reason = 'no_threshold';
        } elseif ($percentagePaid !== null && $minimumPercentage !== null && $percentagePaid >= $minimumPercentage) {
            if ($finalDeadline && $asOf->copy()->startOfDay()->gt($finalDeadline) && $balance > 0) {
                $status = 'pending';
                $reason = 'deadline_passed';
            } else {
                $status = 'cleared';
                $reason = 'above_threshold';
            }
        }

        // No outstanding fees — threshold deadline does not apply.
        $clearanceDeadline = $finalDeadline?->toDateString();
        if (in_array($reason, StudentTermFeeClearance::REASONS_NO_CLEARANCE_DEADLINE, true)) {
            $clearanceDeadline = null;
        }

        return [
            'status' => $status,
            'computed_at' => $asOf,
            'percentage_paid' => $percentagePaid !== null ? round($percentagePaid, 2) : null,
            'minimum_percentage' => $minimumPercentage !== null ? round($minimumPercentage, 2) : null,
            'has_valid_payment_plan' => (bool) $hasValidPlan,
            'payment_plan_id' => $paymentPlan?->id,
            'payment_plan_status' => $planStatus,
            'final_clearance_deadline' => $clearanceDeadline,
            'reason_code' => $reason,
            'meta' => [
                'total_fees' => round($totalFees, 2),
                'total_paid' => round($totalPaid, 2),
                'balance' => round($balance, 2),
            ],
        ];
    }

    public function upsertSnapshot(Student $student, Term $term, ?Carbon $asOf = null): StudentTermFeeClearance
    {
        $payload = $this->compute($student, $term, $asOf);

        return DB::transaction(function () use ($student, $term, $payload) {
            $row = StudentTermFeeClearance::firstOrNew([
                'student_id' => $student->id,
                'term_id' => $term->id,
            ]);

            $row->fill([
                'status' => $payload['status'],
                'computed_at' => $payload['computed_at'],
                'percentage_paid' => $payload['percentage_paid'],
                'minimum_percentage' => $payload['minimum_percentage'],
                'has_valid_payment_plan' => $payload['has_valid_payment_plan'],
                'payment_plan_id' => $payload['payment_plan_id'],
                'payment_plan_status' => $payload['payment_plan_status'],
                'final_clearance_deadline' => $payload['final_clearance_deadline'],
                'reason_code' => $payload['reason_code'],
                'meta' => $payload['meta'],
            ]);

            $row->save();
            return $row;
        });
    }
}


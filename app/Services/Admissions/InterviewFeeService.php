<?php

namespace App\Services\Admissions;

use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\InvoiceItem;
use App\Models\OnlineAdmission;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\PaymentAllocationService;
use App\Services\ReceiptNumberService;
use App\Services\ReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InterviewFeeService
{
    public function resolveAssessmentVotehead(?int $classroomId = null, ?int $year = null): ?Votehead
    {
        $configuredId = Setting::get('admission_assessment_votehead_id');
        if ($configuredId) {
            $vh = Votehead::find((int) $configuredId);
            if ($vh) {
                return $vh;
            }
        }

        $votehead = Votehead::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%assessment%')
                    ->orWhere('name', 'like', '%interview%')
                    ->orWhere('name', 'like', '%admission fee%');
            })
            ->orderByRaw("CASE
                WHEN name LIKE '%interview%' THEN 0
                WHEN name LIKE '%assessment%' THEN 1
                ELSE 2 END")
            ->first();

        return $votehead;
    }

    public function resolveFeeAmount(OnlineAdmission $admission, ?Votehead $votehead = null): ?float
    {
        $votehead = $votehead ?: $this->resolveAssessmentVotehead($admission->preferred_classroom_id);
        if (! $votehead) {
            return null;
        }

        $year = (int) (get_current_academic_year() ?? date('Y'));
        $term = (int) (get_current_term_number() ?? 1);
        $classroomId = $admission->preferred_classroom_id ?: $admission->classroom_id;

        if ($classroomId) {
            $structure = FeeStructure::query()
                ->active()
                ->where('classroom_id', $classroomId)
                ->where('year', $year)
                ->orderByDesc('id')
                ->first();

            if ($structure) {
                $charge = FeeCharge::query()
                    ->where('fee_structure_id', $structure->id)
                    ->where('votehead_id', $votehead->id)
                    ->where(function ($q) use ($term) {
                        $q->where('term', $term)->orWhereNull('term')->orWhere('term', 0);
                    })
                    ->orderByDesc('id')
                    ->first();

                if ($charge) {
                    return (float) $charge->amount;
                }
            }
        }

        return $admission->interview_fee_amount !== null
            ? (float) $admission->interview_fee_amount
            : null;
    }

    public function allocatePayment(OnlineAdmission $admission, array $data, int $userId): OnlineAdmission
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'Already enrolled — allocate via finance payments instead.',
            ]);
        }

        $votehead = $this->resolveAssessmentVotehead($admission->preferred_classroom_id);
        $amount = isset($data['amount']) ? (float) $data['amount'] : $this->resolveFeeAmount($admission, $votehead);

        if ($amount === null || $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Interview/assessment fee amount is missing. Set it in the fee structure or enter an amount.',
            ]);
        }

        $mpesaCode = trim((string) ($data['mpesa_code'] ?? $data['transaction_code'] ?? ''));
        if ($mpesaCode === '') {
            throw ValidationException::withMessages([
                'mpesa_code' => 'Enter the M-Pesa / paybill transaction code.',
            ]);
        }

        return DB::transaction(function () use ($admission, $data, $userId, $votehead, $amount, $mpesaCode) {
            $receiptNumber = $admission->interview_receipt_number
                ?: ('INT-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $mpesaCode), 0, 10)).'-'.$admission->id);

            $admission->update([
                'interview_fee_votehead_id' => $votehead?->id,
                'interview_fee_amount' => $amount,
                'interview_fee_paid' => true,
                'interview_fee_paid_at' => now(),
                'interview_mpesa_code' => $mpesaCode,
                'interview_receipt_number' => $receiptNumber,
                'payment_status' => 'Paid',
                'reviewed_by' => $userId,
            ]);

            $path = $this->generateProvisionalReceipt($admission->fresh(), $data['payer_name'] ?? null);
            $admission->update(['interview_receipt_path' => $path]);

            return $admission->fresh();
        });
    }

    public function generateProvisionalReceipt(OnlineAdmission $admission, ?string $payerName = null): string
    {
        $schoolName = Setting::get('school_name', config('app.name', 'School'));
        $html = view('online_admissions.pdf.interview_receipt', [
            'admission' => $admission,
            'schoolName' => $schoolName,
            'payerName' => $payerName
                ?: ($admission->prospectus_parent_name
                    ?: $admission->father_name
                    ?: $admission->mother_name
                    ?: 'Parent'),
            'childName' => trim(implode(' ', array_filter([
                $admission->first_name,
                $admission->middle_name,
                $admission->last_name,
            ]))),
            'amount' => $admission->interview_fee_amount,
            'receiptNumber' => $admission->interview_receipt_number,
            'mpesaCode' => $admission->interview_mpesa_code,
            'paidAt' => $admission->interview_fee_paid_at,
            'voteheadName' => $admission->interview_fee_votehead_id
                ? optional(Votehead::find($admission->interview_fee_votehead_id))->name
                : 'Interview / Assessment Fee',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        $relative = 'admissions/interview-receipts/'.$admission->id.'_'.time().'.pdf';
        Storage::disk('public')->put($relative, $pdf->output());

        return $relative;
    }

    /**
     * After enrollment fee posting: create a real Payment allocated to the assessment votehead
     * so the student is not left owing the interview fee again.
     */
    public function creditInterviewFeeOnEnroll(OnlineAdmission $admission, Student $student, ?int $year = null, ?int $term = null): void
    {
        if (! $admission->interview_fee_paid || ! $admission->interview_fee_amount) {
            return;
        }

        $voteheadId = $admission->interview_fee_votehead_id
            ?: $this->resolveAssessmentVotehead($student->classroom_id)?->id;

        if (! $voteheadId) {
            Log::warning('Interview fee paid but assessment votehead not found on enroll', [
                'admission_id' => $admission->id,
                'student_id' => $student->id,
            ]);

            return;
        }

        $year = $year ?? (int) (get_current_academic_year() ?? date('Y'));
        $term = $term ?? (int) (get_current_term_number() ?? 1);

        $item = InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($student, $year, $term) {
                $q->where('student_id', $student->id)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->notReversed();
            })
            ->where('votehead_id', $voteheadId)
            ->where('status', 'active')
            ->first();

        if (! $item) {
            // Fee structure did not post assessment — nothing to credit; keep provisional receipt.
            return;
        }

        $balance = method_exists($item, 'getBalance') ? (float) $item->getBalance() : (float) $item->amount;
        if ($balance <= 0) {
            return;
        }

        $amount = min((float) $admission->interview_fee_amount, $balance);

        $methodId = PaymentMethod::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%mpesa%')
                    ->orWhere('name', 'like', '%m-pesa%')
                    ->orWhere('code', 'like', '%mpesa%');
            })
            ->value('id');

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $amount,
            'allocated_amount' => 0,
            'unallocated_amount' => $amount,
            'payment_method_id' => $methodId,
            'payment_method' => 'M-Pesa',
            'payment_channel' => 'mpesa_c2b',
            'mpesa_receipt_number' => $admission->interview_mpesa_code,
            'transaction_code' => $admission->interview_mpesa_code,
            'payer_name' => $admission->prospectus_parent_name ?: $admission->father_name,
            'narration' => 'Interview/assessment fee paid during admission #'.($admission->application_no ?: $admission->id),
            'payment_date' => $admission->interview_fee_paid_at?->toDateString() ?? now()->toDateString(),
            'receipt_number' => $admission->interview_receipt_number ?: ReceiptNumberService::generateForPayment(),
            'receipt_date' => now()->toDateString(),
        ]);

        app(PaymentAllocationService::class)->allocatePayment($payment, [
            ['invoice_item_id' => $item->id, 'amount' => $amount],
        ]);

        try {
            app(ReceiptService::class)->generateReceipt($payment->fresh(), ['save' => true]);
        } catch (\Throwable $e) {
            Log::warning('Failed to generate finance receipt for interview fee credit: '.$e->getMessage());
        }

        $admission->update(['interview_payment_id' => $payment->id]);
    }
}

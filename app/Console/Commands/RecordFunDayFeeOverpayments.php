<?php

namespace App\Console\Commands;

use App\Http\Controllers\Finance\PaymentController;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\FeePaymentPostingService;
use App\Services\InvoiceService;
use App\Services\PaymentAllocationService;
use App\Services\ReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordFunDayFeeOverpayments extends Command
{
    protected $signature = 'finance:record-fun-day-fee-overpayments {--dry-run : Preview without writing}';

    protected $description = 'Record Fun Day trip overpayments as Faulu fee payments and send parent notifications.';

    public function handle(
        PaymentAllocationService $allocationService,
        FeePaymentPostingService $feePostingService,
        ReceiptService $receiptService,
        PaymentController $paymentController,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $rows = [
            [
                'admission_number' => 'RKS762',
                'amount' => 500,
                'payment_date' => '2026-07-10',
                'transaction_code' => 'UGA3FBATLY',
                'payer_name' => 'AMOS GICHUHI NDERI',
            ],
            [
                'admission_number' => 'RKS669',
                'amount' => 500,
                'payment_date' => '2026-07-22',
                'transaction_code' => 'UGM3811R3V',
                'payer_name' => 'PAULINE MUHONJA MULINDI',
            ],
            [
                'admission_number' => 'RKS767',
                'amount' => 500,
                'payment_date' => '2026-07-16',
                'transaction_code' => 'UGG3V05UW6',
                'payer_name' => 'DENNIS MACHARIA KARIRO',
            ],
            [
                'admission_number' => 'RKS739',
                'amount' => 500,
                'payment_date' => '2026-07-10',
                'transaction_code' => 'UGAF9AS0T6',
                'payer_name' => 'ESTHER AUMA ODUOR',
            ],
            [
                'admission_number' => 'RKS667',
                'amount' => 500,
                'payment_date' => '2026-07-11',
                'transaction_code' => 'UGBN2AVQN6',
                'payer_name' => 'KENNEDY NDUNG\'U WAINAINA',
            ],
            [
                'admission_number' => 'RKS500',
                'amount' => 2000,
                'payment_date' => '2026-08-03',
                'transaction_code' => 'UH3GP1QL4H',
                'payer_name' => 'PETER',
            ],
        ];

        $method = $this->resolveFauluMethod();
        $this->info('Payment method: '.$method->name.' (#'.$method->id.')');

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $student = Student::withAlumni()->where('admission_number', $row['admission_number'])->first();
            if (! $student) {
                $this->error("Student {$row['admission_number']} not found.");
                return 1;
            }

            $existing = Payment::query()
                ->where('transaction_code', $row['transaction_code'])
                ->where('student_id', $student->id)
                ->where('reversed', false)
                ->where('amount', $row['amount'])
                ->first();

            $label = trim($student->first_name.' '.$student->middle_name.' '.$student->last_name)
                ." {$row['admission_number']} KES {$row['amount']} {$row['transaction_code']} {$row['payment_date']} payer={$row['payer_name']}";

            if ($existing) {
                $this->warn("SKIP already exists payment #{$existing->id}: {$label}");
                $skipped++;
                continue;
            }

            $codeClash = Payment::query()
                ->where('transaction_code', $row['transaction_code'])
                ->where('student_id', $student->id)
                ->where('reversed', false)
                ->first();
            if ($codeClash) {
                $this->warn("SKIP same code already on this student payment #{$codeClash->id} amount {$codeClash->amount}: {$label}");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("DRY RUN would create: {$label}");
                $created++;
                continue;
            }

            $payment = DB::transaction(function () use ($row, $student, $method, $allocationService) {
                $payment = Payment::create([
                    'student_id' => $student->id,
                    'family_id' => $student->family_id,
                    'amount' => $row['amount'],
                    'payment_method_id' => $method->id,
                    'payment_channel' => 'faulu',
                    'mpesa_receipt_number' => $row['transaction_code'],
                    'payer_name' => $row['payer_name'],
                    'payer_type' => 'parent',
                    'transaction_code' => $row['transaction_code'],
                    'payment_date' => $row['payment_date'],
                    'narration' => 'Fun Day 2026 trip overpayment credited to fees (Faulu ENT BOOK)',
                ]);

                try {
                    if (method_exists($allocationService, 'autoAllocateWithInstallments')) {
                        $allocationService->autoAllocateWithInstallments($payment);
                    } else {
                        $allocationService->autoAllocate($payment);
                    }
                } catch (\Throwable $e) {
                    $this->warn('Allocation failed for payment #'.$payment->id.': '.$e->getMessage());
                }

                if (class_exists(AuditLog::class)) {
                    AuditLog::log('created', $payment, null, [
                        'amount' => $payment->amount,
                        'student_id' => $payment->student_id,
                        'payment_method_id' => $payment->payment_method_id,
                        'transaction_code' => $payment->transaction_code,
                    ], ['payment_recorded', 'fun_day_overpayment']);
                }

                return $payment;
            });

            try {
                InvoiceService::allocateUnallocatedPaymentsForStudent($student->id);
            } catch (\Throwable $e) {
                $this->warn('Student reallocation failed: '.$e->getMessage());
            }

            try {
                $fresh = Payment::with(['allocations.invoiceItem.votehead.account', 'paymentMethod.bankAccount.account'])->find($payment->id);
                if ($fresh) {
                    $feePostingService->post($fresh, User::query()->orderBy('id')->first());
                }
            } catch (\Throwable $e) {
                $this->warn('GL posting failed for payment #'.$payment->id.': '.$e->getMessage());
            }

            try {
                $receiptService->generateReceipt($payment->fresh(), ['save' => true]);
            } catch (\Throwable $e) {
                $this->warn('Receipt PDF failed for payment #'.$payment->id.': '.$e->getMessage());
            }

            try {
                $paymentController->sendPaymentNotifications($payment->fresh(['student.parent', 'paymentMethod']));
                $this->info("Notifications sent for payment #{$payment->id}");
            } catch (\Throwable $e) {
                Log::error('Fun Day overpayment notification failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn('Notification failed for payment #'.$payment->id.': '.$e->getMessage());
                if (function_exists('flash_sms_credit_warning')) {
                    flash_sms_credit_warning($e);
                }
            }

            $this->info("Created payment #{$payment->id} receipt {$payment->receipt_number}: {$label}");
            $created++;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Dry run: would create ' : 'Created ').$created.' payment(s); skipped '.$skipped.'.');
        $this->comment('Harmony (RKS782) skipped: UH1AX1IDHO is Aria’s 3,000. Isaac skipped as requested.');

        return 0;
    }

    protected function resolveFauluMethod(): PaymentMethod
    {
        $method = PaymentMethod::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%faulu%')
                    ->orWhere('code', 'like', '%FAULU%');
            })
            ->first();

        if ($method) {
            return $method;
        }

        $bank = BankAccount::query()
            ->where('account_number', 'like', '%1015518266%')
            ->orWhere('name', 'like', '%faulu%')
            ->first();

        return PaymentMethod::create([
            'name' => 'Faulu',
            'code' => 'FAULU',
            'requires_reference' => true,
            'is_online' => false,
            'is_active' => true,
            'display_order' => 20,
            'description' => 'Faulu Microfinance Bank',
            'bank_account_id' => $bank?->id,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\{Payment, PaymentAllocation, Invoice, Student, InvoiceItem, Votehead};
use App\Services\{PaymentAllocationService, InvoiceService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-allocate a student's payments with BBF (Balance Brought Forward) priority.
 * Use when payments were allocated before BBF-first logic, leaving BBF unpaid.
 */
class ReallocateStudentPaymentsWithBbfPriority extends Command
{
    protected $signature = 'finance:reallocate-student-bbf
                            {student? : Student ID, admission number (e.g. RKS463), or "all" for all students with BBF}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Re-allocate student payments with BBF cleared first (fixes misallocations). Use "all" to fix all students with BBF.';

    public function handle(): int
    {
        $identifier = $this->argument('student') ?? 'all';
        $dryRun = $this->option('dry-run');

        if (strtolower($identifier) === 'all') {
            return $this->reallocateAllStudentsWithBbf($dryRun);
        }

        $student = is_numeric($identifier)
            ? Student::find($identifier)
            : Student::where('admission_number', $identifier)->first();

        if (!$student) {
            $this->error("Student not found: {$identifier}");
            return 1;
        }

        return $this->reallocateStudent($student, $dryRun);
    }

    private function reallocateAllStudentsWithBbf(bool $dryRun): int
    {
        $bbfVotehead = Votehead::where('code', 'BAL_BF')->first();
        if (!$bbfVotehead) {
            $this->error('BAL_BF votehead not found.');
            return 1;
        }

        $studentIds = InvoiceItem::where('votehead_id', $bbfVotehead->id)
            ->where('source', 'balance_brought_forward')
            ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'reversed'))
            ->with('invoice')
            ->get()
            ->pluck('invoice.student_id')
            ->filter()
            ->unique()
            ->values();

        $legacyStudentIds = \App\Models\LegacyStatementTerm::where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->unique()
            ->filter(fn ($id) => Student::where('id', $id)->exists());

        $allStudentIds = $studentIds->merge($legacyStudentIds)->unique()->values();
        $students = Student::whereIn('id', $allStudentIds)->get();

        $this->info("Found {$students->count()} student(s) with BBF.");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        $reallocated = 0;
        foreach ($students as $student) {
            $result = $this->reallocateStudent($student, $dryRun);
            if ($result === 0) {
                $reallocated++;
            }
        }

        if (!$dryRun) {
            $this->info("Re-allocation complete for {$reallocated} student(s).");
        }

        return 0;
    }

    private function reallocateStudent(Student $student, bool $dryRun): int
    {
        $payments = Payment::where('student_id', $student->id)
            ->where('reversed', false)
            ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return 1; // Nothing to do
        }

        $hasAllocations = $payments->contains(fn ($p) => $p->allocations()->exists());
        if (!$hasAllocations) {
            return 1; // Nothing to do
        }

        $allocationService = app(PaymentAllocationService::class);

        DB::transaction(function () use ($payments, $student, $allocationService, $dryRun) {
            foreach ($payments as $payment) {
                $allocations = $payment->allocations()->get();
                if ($allocations->isEmpty()) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  {$student->admission_number}: Would deallocate {$allocations->count()} from payment #{$payment->id} ({$payment->receipt_number})");
                    continue;
                }

                $payment->allocations()->delete();
                $payment->updateAllocationTotals();
            }

            if (!$dryRun) {
                Invoice::where('student_id', $student->id)
                    ->where('status', '!=', 'reversed')
                    ->get()
                    ->each(fn ($inv) => InvoiceService::recalc($inv));

                foreach ($payments as $payment) {
                    $allocationService->autoAllocate($payment, $student->id);
                }
            }
        });

        if (!$dryRun) {
            $this->line("  Re-allocated: {$student->admission_number} {$student->full_name}");
        }

        return 0;
    }
}

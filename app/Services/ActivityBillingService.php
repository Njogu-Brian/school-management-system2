<?php

namespace App\Services;

use App\Models\StudentExtracurricularActivity;
use App\Models\OptionalFee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeStructure;
use App\Models\FeeCharge;
use App\Models\Term;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class ActivityBillingService
{
    /**
     * Create or update optional fee for an activity
     */
    public function billActivity(StudentExtracurricularActivity $activity): void
    {
        if (!$activity->auto_bill || !$activity->votehead_id) {
            return;
        }

        $student = $activity->student;
        if (!$student) {
            return;
        }

        // Get current term and year if not set
        $term = $activity->billing_term ?? $this->getCurrentTerm();
        $year = $activity->billing_year ?? $this->getCurrentYear();

        if (!$term || !$year) {
            return;
        }

        // Get fee amount from activity or fee structure
        $amount = $activity->fee_amount;
        if (!$amount) {
            $amount = $this->getFeeAmountFromStructure($student->classroom_id, $activity->votehead_id, $term, $year);
        }

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($activity, $student, $term, $year, $amount) {
            // Create or update optional fee
            OptionalFee::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'votehead_id' => $activity->votehead_id,
                    'term' => $term,
                    'year' => $year,
                ],
                [
                    'status' => 'billed',
                    'amount' => $amount,
                ]
            );

            // Create or update invoice
            $invoice = Invoice::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'term' => $term,
                    'year' => $year,
                ],
                [
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'total' => 0,
                ]
            );

            // Add invoice item
            InvoiceItem::updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'votehead_id' => $activity->votehead_id,
                ],
                [
                    'amount' => $amount,
                ]
            );

            // Update invoice total
            $invoice->update([
                'total' => $invoice->items()->sum('amount'),
            ]);
        });
    }

    /**
     * Remove optional fee when activity is deleted or auto_bill is disabled
     */
    public function unbillActivity(StudentExtracurricularActivity $activity): void
    {
        if (!$activity->votehead_id) {
            return;
        }

        $term = $activity->billing_term ?? $this->getCurrentTerm();
        $year = $activity->billing_year ?? $this->getCurrentYear();

        if (!$term || !$year) {
            return;
        }

        DB::transaction(function () use ($activity, $term, $year) {
            // Remove optional fee
            OptionalFee::where([
                'student_id' => $activity->student_id,
                'votehead_id' => $activity->votehead_id,
                'term' => $term,
                'year' => $year,
            ])->delete();

            // Remove invoice item
            $invoice = Invoice::where([
                'student_id' => $activity->student_id,
                'term' => $term,
                'year' => $year,
            ])->first();

            if ($invoice) {
                $invoice->items()->where('votehead_id', $activity->votehead_id)->delete();

                // Delete invoice if empty
                if ($invoice->items()->count() === 0) {
                    $invoice->delete();
                } else {
                    $invoice->update([
                        'total' => $invoice->items()->sum('amount'),
                    ]);
                }
            }
        });
    }

    /**
     * Get fee amount from fee structure
     */
    private function getFeeAmountFromStructure($classroomId, $voteheadId, $term, $year): float
    {
        if (!$classroomId) {
            return 0;
        }

        $structure = FeeStructure::where('classroom_id', $classroomId)
            ->where('year', $year)
            ->first();

        if (!$structure) {
            return 0;
        }

        return (float) (FeeCharge::where('fee_structure_id', $structure->id)
            ->where('votehead_id', $voteheadId)
            ->where('term', $term)
            ->value('amount') ?? 0);
    }

    /**
     * Get current term number (1, 2, or 3)
     */
    private function getCurrentTerm(): ?int
    {
        $term = Term::where('is_current', true)->first();
        if (!$term) {
            return null;
        }

        // Extract term number from name (e.g., "Term 1" -> 1)
        if (preg_match('/\d+/', $term->name, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    /**
     * Get current academic year
     */
    private function getCurrentYear(): ?int
    {
        $year = AcademicYear::where('is_active', true)->first();
        return $year ? (int) $year->year : null;
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(): string
    {
        try {
            if (class_exists(\App\Services\DocumentNumberService::class)) {
                $maybe = \App\Services\DocumentNumberService::generate('invoice', 'INV');
                if (!empty($maybe)) {
                    return $maybe;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to fallback
        }

        $next = (int) (Invoice::max('id') ?? 0) + 1;
        return 'INV-' . date('Y') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}


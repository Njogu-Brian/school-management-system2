<?php

namespace App\Services;

use App\Models\OptionalFee;
use App\Models\TransportFee;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Duplicate optional and transport fees from one term to another.
 * When invoice exists for target term: update it. Otherwise create new.
 */
class FeeDuplicationService
{
    /**
     * Duplicate transport fees from source to target term.
     * Scope: student_ids (array), classroom_id, or entire school (when both null).
     *
     * @return array{duplicated: int, updated: int, created: int}
     */
    public static function duplicateTransport(
        int $sourceYear,
        int $sourceTerm,
        int $targetYear,
        int $targetTerm,
        ?array $studentIds = null,
        ?int $classroomId = null
    ): array {
        $students = self::resolveStudents($studentIds, $classroomId);
        $sourceFees = TransportFee::where('year', $sourceYear)
            ->where('term', $sourceTerm)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $duplicated = 0;
        $updated = 0;
        $created = 0;

        foreach ($students as $student) {
            $sourceFee = $sourceFees->get($student->id);
            if (!$sourceFee || (float) $sourceFee->amount <= 0) {
                continue;
            }

            $existingTarget = TransportFee::where('student_id', $student->id)
                ->where('year', $targetYear)
                ->where('term', $targetTerm)
                ->first();

            $fee = TransportFeeService::upsertFee([
                'student_id' => $student->id,
                'year' => $targetYear,
                'term' => $targetTerm,
                'amount' => $sourceFee->amount,
                'drop_off_point_id' => $sourceFee->drop_off_point_id,
                'drop_off_point_name' => $sourceFee->drop_off_point_name,
                'source' => 'duplicate',
                'note' => "Duplicated from {$sourceYear} Term {$sourceTerm}",
                'skip_invoice' => false,
            ]);

            $duplicated++;
            if ($existingTarget) {
                $updated++;
            } else {
                $created++;
            }
        }

        return ['duplicated' => $duplicated, 'updated' => $updated, 'created' => $created];
    }

    /**
     * Duplicate optional fees from source to target term.
     * Scope: student_ids (array), classroom_id, or entire school.
     * When invoice exists for target term: update it. Otherwise create new.
     *
     * @return array{duplicated: int, invoice_items_updated: int, invoice_items_created: int}
     */
    public static function duplicateOptional(
        int $sourceYear,
        int $sourceTerm,
        int $targetYear,
        int $targetTerm,
        ?array $voteheadIds = null,
        ?array $studentIds = null,
        ?int $classroomId = null
    ): array {
        $students = self::resolveStudents($studentIds, $classroomId);
        $academicYear = \App\Models\AcademicYear::where('year', $targetYear)->first();

        $sourceFees = OptionalFee::where('year', $sourceYear)
            ->where('term', $sourceTerm)
            ->where('status', 'billed')
            ->whereIn('student_id', $students->pluck('id'))
            ->when($voteheadIds, fn ($q) => $q->whereIn('votehead_id', $voteheadIds))
            ->get();

        $duplicated = 0;
        $invoiceItemsUpdated = 0;
        $invoiceItemsCreated = 0;

        foreach ($sourceFees as $source) {
            $student = $students->firstWhere('id', $source->student_id);
            if (!$student) {
                continue;
            }

            OptionalFee::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'votehead_id' => $source->votehead_id,
                    'year' => $targetYear,
                    'term' => $targetTerm,
                ],
                [
                    'amount' => $source->amount,
                    'status' => 'billed',
                    'academic_year_id' => $academicYear?->id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]
            );

            DB::transaction(function () use ($student, $source, $targetYear, $targetTerm, &$invoiceItemsUpdated, &$invoiceItemsCreated) {
                $invoice = InvoiceService::ensure($student->id, $targetYear, $targetTerm);
                $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $source->votehead_id)
                    ->where('source', 'optional')
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'amount' => $source->amount,
                        'original_amount' => $source->amount,
                        'status' => 'active',
                    ]);
                    $invoiceItemsUpdated++;
                } else {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $source->votehead_id,
                        'amount' => $source->amount,
                        'original_amount' => $source->amount,
                        'status' => 'active',
                        'source' => 'optional',
                    ]);
                    $invoiceItemsCreated++;
                }
                InvoiceService::recalc($invoice);
            });

            $duplicated++;
        }

        return ['duplicated' => $duplicated, 'updated' => $invoiceItemsUpdated, 'created' => $invoiceItemsCreated];
    }

    private static function resolveStudents(?array $studentIds, ?int $classroomId): Collection
    {
        $query = Student::where('archive', 0)->where('is_alumni', false);

        if ($studentIds && !empty($studentIds)) {
            $query->whereIn('id', array_map('intval', $studentIds));
        } elseif ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        return $query->get();
    }
}

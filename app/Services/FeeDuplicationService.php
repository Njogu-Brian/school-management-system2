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

    /**
     * Build preview list of optional fees to duplicate (no DB writes).
     *
     * @return array<int, array{student_id: int, votehead_id: int, amount: float, student_name: string, votehead_name: string}>
     */
    public static function previewOptional(
        int $sourceYear,
        int $sourceTerm,
        int $targetYear,
        int $targetTerm,
        ?array $voteheadIds = null,
        ?array $studentIds = null,
        ?int $classroomId = null
    ): array {
        $students = self::resolveStudents($studentIds, $classroomId);
        $sourceFees = OptionalFee::where('year', $sourceYear)
            ->where('term', $sourceTerm)
            ->where('status', 'billed')
            ->whereIn('student_id', $students->pluck('id'))
            ->when($voteheadIds, fn ($q) => $q->whereIn('votehead_id', $voteheadIds))
            ->with('votehead')
            ->get();

        $items = [];
        foreach ($sourceFees as $source) {
            $student = $students->firstWhere('id', $source->student_id);
            if (!$student) {
                continue;
            }
            $items[] = [
                'student_id' => $source->student_id,
                'votehead_id' => $source->votehead_id,
                'amount' => (float) $source->amount,
                'student_name' => $student->full_name ?? ($student->first_name . ' ' . $student->last_name),
                'votehead_name' => $source->votehead?->name ?? 'Votehead #' . $source->votehead_id,
            ];
        }
        return $items;
    }

    /**
     * Duplicate only the approved optional fee items.
     *
     * @param array<int, array{student_id: int, votehead_id: int, amount: float}> $items
     * @return array{duplicated: int, updated: int, created: int}
     */
    public static function duplicateOptionalSelected(
        int $targetYear,
        int $targetTerm,
        array $items
    ): array {
        $academicYear = \App\Models\AcademicYear::where('year', $targetYear)->first();
        $duplicated = 0;
        $invoiceItemsUpdated = 0;
        $invoiceItemsCreated = 0;

        foreach ($items as $item) {
            $studentId = (int) ($item['student_id'] ?? 0);
            $voteheadId = (int) ($item['votehead_id'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);
            if (!$studentId || !$voteheadId) {
                continue;
            }

            OptionalFee::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'votehead_id' => $voteheadId,
                    'year' => $targetYear,
                    'term' => $targetTerm,
                ],
                [
                    'amount' => $amount,
                    'status' => 'billed',
                    'academic_year_id' => $academicYear?->id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]
            );

            DB::transaction(function () use ($studentId, $voteheadId, $amount, $targetYear, $targetTerm, &$invoiceItemsUpdated, &$invoiceItemsCreated) {
                $invoice = InvoiceService::ensure($studentId, $targetYear, $targetTerm);
                $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $voteheadId)
                    ->where('source', 'optional')
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'amount' => $amount,
                        'original_amount' => $amount,
                        'status' => 'active',
                    ]);
                    $invoiceItemsUpdated++;
                } else {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $voteheadId,
                        'amount' => $amount,
                        'original_amount' => $amount,
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

    /**
     * Build preview list of transport fees to duplicate (no DB writes).
     *
     * @return array<int, array{student_id: int, amount: float, drop_off_point_id: ?int, drop_off_point_name: ?string, student_name: string}>
     */
    public static function previewTransport(
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

        $items = [];
        foreach ($students as $student) {
            $sourceFee = $sourceFees->get($student->id);
            if (!$sourceFee || (float) $sourceFee->amount <= 0) {
                continue;
            }
            $items[] = [
                'student_id' => $student->id,
                'amount' => (float) $sourceFee->amount,
                'drop_off_point_id' => $sourceFee->drop_off_point_id,
                'drop_off_point_name' => $sourceFee->drop_off_point_name,
                'student_name' => $student->full_name ?? ($student->first_name . ' ' . $student->last_name),
            ];
        }
        return $items;
    }

    /**
     * Duplicate only the approved transport fee items.
     *
     * @param array<int, array{student_id: int, amount: float, drop_off_point_id: ?int, drop_off_point_name: ?string}> $items
     * @return array{duplicated: int, updated: int, created: int}
     */
    public static function duplicateTransportSelected(
        int $targetYear,
        int $targetTerm,
        array $items
    ): array {
        $duplicated = 0;
        $updated = 0;
        $created = 0;

        foreach ($items as $item) {
            $studentId = (int) ($item['student_id'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);
            if (!$studentId || $amount <= 0) {
                continue;
            }

            $existingTarget = TransportFee::where('student_id', $studentId)
                ->where('year', $targetYear)
                ->where('term', $targetTerm)
                ->first();

            TransportFeeService::upsertFee([
                'student_id' => $studentId,
                'year' => $targetYear,
                'term' => $targetTerm,
                'amount' => $amount,
                'drop_off_point_id' => $item['drop_off_point_id'] ?? null,
                'drop_off_point_name' => $item['drop_off_point_name'] ?? null,
                'source' => 'duplicate',
                'note' => "Duplicated from source term",
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

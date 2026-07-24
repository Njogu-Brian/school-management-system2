<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DropOffPoint;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Term;
use App\Models\TransportFee;
use App\Models\TransportFeeRevision;
use App\Models\Votehead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransportFeeService
{
    /**
     * Ensure the dedicated transport votehead exists (code: TRANSPORT).
     */
    public static function transportVotehead(): Votehead
    {
        $votehead = Votehead::whereRaw('LOWER(code) = ?', ['transport'])
            ->orWhereRaw('LOWER(name) = ?', ['transport'])
            ->first();

        if ($votehead) {
            return $votehead;
        }

        return Votehead::create([
            'code' => 'TRANSPORT',
            'name' => 'Transport',
            'description' => 'School transport charges per term',
            'category' => 'Transport',
            'is_mandatory' => true,
            'is_optional' => false,
            'charge_type' => 'per_student',
            'preferred_term' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Resolve current academic year + term defaults.
     */
    public static function resolveYearAndTerm(?int $year = null, ?int $term = null): array
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $yearValue = $year ?? ($academicYear?->year ?? (int) date('Y'));

        $termModel = Term::where('is_current', true)->first();
        $termValue = $term ?? ($termModel ? (int) preg_replace('/[^0-9]/', '', $termModel->name) : 1);
        $termValue = $termValue ?: 1;

        return [$yearValue, $termValue, $academicYear?->id];
    }

    /**
     * Create or update a student's transport fee and sync the invoice item.
     */
    public static function upsertFee(array $data): TransportFee
    {
        [$year, $term, $academicYearId] = self::resolveYearAndTerm($data['year'] ?? null, $data['term'] ?? null);

        $studentId = (int) $data['student_id'];
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $dropOffPointId = $data['drop_off_point_id'] ?? null;
        $dropOffPointName = $data['drop_off_point_name'] ?? null;
        $source = $data['source'] ?? 'manual';
        $userId = $data['user_id'] ?? auth()->id();
        $note = $data['note'] ?? null;
        $skipInvoice = $data['skip_invoice'] ?? false;
        $pricingMode = $data['pricing_mode'] ?? null;
        $pricingBreakdown = $data['pricing_breakdown'] ?? null;

        if (!$dropOffPointName && $dropOffPointId) {
            $dropOffPointName = optional(DropOffPoint::find($dropOffPointId))->name;
        }

        return DB::transaction(function () use (
            $studentId,
            $amount,
            $dropOffPointId,
            $dropOffPointName,
            $source,
            $userId,
            $note,
            $year,
            $term,
            $academicYearId,
            $skipInvoice,
            $pricingMode,
            $pricingBreakdown,
            $data
        ) {
            $existing = TransportFee::where('student_id', $studentId)
                ->where('year', $year)
                ->where('term', $term)
                ->first();

            $payload = [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'year' => $year,
                'term' => $term,
                'drop_off_point_id' => $dropOffPointId,
                'drop_off_point_name' => $dropOffPointName,
                'amount' => $amount,
                'source' => $source,
                'note' => $note,
                'updated_by' => $userId,
            ];

            if (array_key_exists('pricing_mode', $data)) {
                $payload['pricing_mode'] = $data['pricing_mode'];
            } elseif ($pricingMode !== null) {
                $payload['pricing_mode'] = $pricingMode;
            }

            if (array_key_exists('pricing_breakdown', $data)) {
                $payload['pricing_breakdown'] = $data['pricing_breakdown'];
            } elseif ($pricingBreakdown !== null) {
                $payload['pricing_breakdown'] = $pricingBreakdown;
            }

            if ($existing) {
                $oldAmount = $existing->amount;
                $oldDropOffPointId = $existing->drop_off_point_id;
                $oldDropOffPointName = $existing->drop_off_point_name;

                $changed = $oldAmount != $amount
                    || $oldDropOffPointId != $dropOffPointId
                    || $oldDropOffPointName !== $dropOffPointName;

                $existing->fill($payload);
                $existing->save();

                if ($changed) {
                    TransportFeeRevision::create([
                        'transport_fee_id' => $existing->id,
                        'changed_by' => $userId,
                        'source' => $source,
                        'old_amount' => $oldAmount,
                        'new_amount' => $amount,
                        'old_drop_off_point_id' => $oldDropOffPointId,
                        'new_drop_off_point_id' => $dropOffPointId,
                        'old_drop_off_point_name' => $oldDropOffPointName,
                        'new_drop_off_point_name' => $dropOffPointName,
                        'note' => $note,
                    ]);
                }

                $fee = $existing;
            } else {
                $fee = TransportFee::create(array_merge($payload, [
                    'created_by' => $userId,
                    'pricing_mode' => $payload['pricing_mode'] ?? 'calculated',
                ]));

                TransportFeeRevision::create([
                    'transport_fee_id' => $fee->id,
                    'changed_by' => $userId,
                    'source' => $source,
                    'old_amount' => null,
                    'new_amount' => $amount,
                    'old_drop_off_point_id' => null,
                    'new_drop_off_point_id' => $dropOffPointId,
                    'old_drop_off_point_name' => null,
                    'new_drop_off_point_name' => $dropOffPointName,
                    'note' => $note,
                ]);
            }

            // Keep student drop-off info aligned when provided
            if ($dropOffPointId) {
                Student::where('id', $studentId)->update([
                    'drop_off_point_id' => $dropOffPointId,
                    'drop_off_point_other' => $dropOffPointName,
                ]);
            } elseif ($dropOffPointName) {
                Student::where('id', $studentId)->update([
                    'drop_off_point_other' => $dropOffPointName,
                ]);
            }

            // Only create/update invoice item if amount > 0 and not explicitly skipped
            if ($amount > 0 && !$skipInvoice) {
                self::syncInvoice($fee, self::transportVotehead());
            } elseif ($amount == 0 && !$skipInvoice) {
                // If amount is 0 and not skipped, remove any existing transport item immediately.
                // Delete payment allocations first so payments are freed for re-allocation.
                $votehead = self::transportVotehead();
                DB::transaction(function () use ($fee, $votehead) {
                    $invoice = InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);
                    $items = InvoiceItem::where('invoice_id', $invoice->id)
                        ->where('votehead_id', $votehead->id)
                        ->where('source', 'transport')
                        ->get();

                    foreach ($items as $item) {
                        $paymentIds = $item->allocations()->pluck('payment_id')->unique()->filter();
                        $item->allocations()->delete();
                        $item->delete();
                        foreach ($paymentIds as $paymentId) {
                            $payment = \App\Models\Payment::find($paymentId);
                            if ($payment) {
                                $payment->updateAllocationTotals();
                            }
                        }
                    }

                    InvoiceService::recalc($invoice);
                    InvoiceService::allocateUnallocatedPaymentsForStudent($fee->student_id);
                });
            }

            return $fee->fresh();
        });
    }

    /**
     * Recalculate list price from morning/evening assignment points and upsert the fee.
     *
     * @return array{fee: TransportFee|null, result: array, updated: bool}
     */
    public static function recalculateForStudent(
        int $studentId,
        ?int $year = null,
        ?int $term = null,
        bool $skipInvoice = true,
        string $source = 'calculated',
        ?string $note = null
    ): array {
        [$year, $term] = self::resolveYearAndTerm($year, $term);

        $assignment = StudentAssignment::where('student_id', $studentId)->first();
        $result = TransportFeeCalculator::calculateFromAssignment($assignment);

        if (!$result['can_calculate']) {
            return [
                'fee' => TransportFee::where('student_id', $studentId)->where('year', $year)->where('term', $term)->first(),
                'result' => $result,
                'updated' => false,
            ];
        }

        $legacyPointId = null;
        $legacyPointName = null;
        if ($assignment) {
            if ($assignment->evening_drop_off_point_id && !DropOffPoint::nameIsOwnMeans(optional(DropOffPoint::find($assignment->evening_drop_off_point_id))->name)) {
                $legacyPointId = $assignment->evening_drop_off_point_id;
            } elseif ($assignment->morning_drop_off_point_id && !DropOffPoint::nameIsOwnMeans(optional(DropOffPoint::find($assignment->morning_drop_off_point_id))->name)) {
                $legacyPointId = $assignment->morning_drop_off_point_id;
            }
            $legacyPointName = $legacyPointId
                ? optional(DropOffPoint::find($legacyPointId))->name
                : ($result['amount'] == 0 ? DropOffPoint::OWN_MEANS_NAME : null);
        }

        $fee = self::upsertFee([
            'student_id' => $studentId,
            'amount' => $result['amount'] ?? 0,
            'year' => $year,
            'term' => $term,
            'drop_off_point_id' => $legacyPointId,
            'drop_off_point_name' => $legacyPointName,
            'source' => $source,
            'note' => $note ?? ($result['breakdown']['label'] ?? 'Calculated from morning/evening drop-off points'),
            'pricing_mode' => 'calculated',
            'pricing_breakdown' => $result['breakdown'],
            'skip_invoice' => $skipInvoice,
        ]);

        return [
            'fee' => $fee,
            'result' => $result,
            'updated' => true,
        ];
    }

    /**
     * Recalculate fees for many students (e.g. classroom).
     *
     * @param  array<int, int>  $studentIds
     * @return array{updated: int, skipped: int, errors: array<int, string>}
     */
    public static function recalculateForStudents(
        array $studentIds,
        ?int $year = null,
        ?int $term = null,
        bool $skipInvoice = true
    ): array {
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($studentIds as $studentId) {
            $outcome = self::recalculateForStudent((int) $studentId, $year, $term, $skipInvoice, 'calculated');
            if ($outcome['updated']) {
                $updated++;
            } else {
                $skipped++;
                if (!empty($outcome['result']['errors'])) {
                    $errors[(int) $studentId] = implode(' ', $outcome['result']['errors']);
                }
            }
        }

        return compact('updated', 'skipped', 'errors');
    }

    /**
     * Update or create the invoice item for the given transport fee.
     * Existing items use updateItemAmount so amount deltas create CN/DN.
     */
    public static function syncInvoice(TransportFee $fee, ?Votehead $votehead = null): void
    {
        $votehead ??= self::transportVotehead();

        DB::transaction(function () use ($fee, $votehead) {
            $invoice = InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);
            $newAmount = round((float) $fee->amount, 2);

            $existingItem = InvoiceItem::withTrashed()
                ->where('invoice_id', $invoice->id)
                ->where('votehead_id', $votehead->id)
                ->where('source', 'transport')
                ->first();

            if ($existingItem) {
                if ($existingItem->trashed()) {
                    $existingItem->restore();
                }

                if ($newAmount <= 0) {
                    $paymentIds = $existingItem->allocations()->pluck('payment_id')->unique()->filter();
                    $existingItem->allocations()->delete();
                    $existingItem->delete();
                    foreach ($paymentIds as $paymentId) {
                        $payment = \App\Models\Payment::find($paymentId);
                        if ($payment) {
                            $payment->updateAllocationTotals();
                        }
                    }
                    InvoiceService::recalc($invoice);
                    InvoiceService::allocateUnallocatedPaymentsForStudent($fee->student_id);

                    return;
                }

                $oldAmount = round((float) $existingItem->amount, 2);
                if (abs($oldAmount - $newAmount) < 0.01) {
                    $existingItem->update([
                        'status' => 'active',
                        'effective_date' => null,
                        'source' => 'transport',
                        'posted_at' => now(),
                    ]);
                    InvoiceService::recalc($invoice);

                    return;
                }

                $reason = $fee->pricing_mode === 'calculated'
                    ? 'Transport list price recalculated from drop-off points'
                    : 'Transport fee amount updated';

                $notes = $fee->pricing_breakdown['label']
                    ?? $fee->note
                    ?? "Transport fee changed from {$oldAmount} to {$newAmount}";

                InvoiceService::updateItemAmount($existingItem, $newAmount, $reason, $notes);
                $existingItem->refresh();
                $existingItem->update([
                    'status' => 'active',
                    'effective_date' => null,
                    'source' => 'transport',
                    'posted_at' => now(),
                ]);

                return;
            }

            if ($newAmount <= 0) {
                return;
            }

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $votehead->id,
                'amount' => $newAmount,
                'original_amount' => $newAmount,
                'status' => 'active',
                'effective_date' => null,
                'source' => 'transport',
                'posted_at' => now(),
            ]);

            InvoiceService::recalc($invoice);
        });
    }

    /**
     * Helper: find or create drop-off by name (case-insensitive).
     */
    public static function resolveDropOffPoint(?string $name): ?DropOffPoint
    {
        if (!$name) {
            return null;
        }

        $clean = trim($name);
        if ($clean === '') {
            return null;
        }

        if (DropOffPoint::nameIsOwnMeans($clean)) {
            return DropOffPoint::ownMeans();
        }

        $existing = DropOffPoint::whereRaw('LOWER(name) = ?', [Str::lower($clean)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return DropOffPoint::create([
            'name' => $clean,
        ]);
    }

    /**
     * Reverse a transport fee import - deletes invoice items and drop-off assignments
     * but keeps the created drop-off points
     */
    public static function reverseImport(\App\Models\TransportFeeImport $import): array
    {
        return DB::transaction(function () use ($import) {
            $transportFees = TransportFee::where('year', $import->year)
                ->where('term', $import->term)
                ->where('source', 'import')
                ->where('created_at', '>=', $import->imported_at->startOfDay())
                ->where('created_at', '<=', $import->imported_at->endOfDay())
                ->get();

            $itemsDeleted = 0;
            $assignmentsDeleted = 0;
            $votehead = self::transportVotehead();
            $invoiceIds = collect();

            foreach ($transportFees as $fee) {
                $invoice = InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);
                $invoiceIds->push($invoice->id);

                $deleted = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $votehead->id)
                    ->where('source', 'transport')
                    ->delete();

                $itemsDeleted += $deleted;

                $assignment = StudentAssignment::where('student_id', $fee->student_id)->first();
                if ($assignment) {
                    if ($assignment->morning_drop_off_point_id == $fee->drop_off_point_id ||
                        $assignment->evening_drop_off_point_id == $fee->drop_off_point_id) {
                        $updated = false;
                        if ($assignment->morning_drop_off_point_id == $fee->drop_off_point_id) {
                            $assignment->morning_drop_off_point_id = null;
                            $updated = true;
                        }
                        if ($assignment->evening_drop_off_point_id == $fee->drop_off_point_id) {
                            $assignment->evening_drop_off_point_id = null;
                            $updated = true;
                        }
                        if ($updated) {
                            $assignment->save();
                            $assignmentsDeleted++;
                        }
                    }
                }

                $student = Student::find($fee->student_id);
                if ($student && $student->drop_off_point_id == $fee->drop_off_point_id) {
                    $student->update([
                        'drop_off_point_id' => null,
                        'drop_off_point_other' => null,
                    ]);
                }

                $fee->delete();
            }

            foreach ($invoiceIds->unique() as $invoiceId) {
                $invoice = \App\Models\Invoice::find($invoiceId);
                if ($invoice) {
                    InvoiceService::recalc($invoice);
                }
            }

            $import->update([
                'is_reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);

            return [
                'items_deleted' => $itemsDeleted,
                'assignments_deleted' => $assignmentsDeleted,
            ];
        });
    }
}

<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DropOffPoint;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\Term;
use App\Models\TransportFee;
use App\Models\TransportFeeRevision;
use App\Models\Votehead;
use App\Services\InvoiceService;
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
            $skipInvoice
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
                // If amount is 0 and not skipped, remove any existing transport item immediately
                $votehead = self::transportVotehead();
                DB::transaction(function () use ($fee, $votehead) {
                    $invoice = \App\Services\InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);
                    // Only delete transport items with source='transport' - this is safe as it only affects transport items
                    \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                        ->where('votehead_id', $votehead->id)
                        ->where('source', 'transport')
                        ->delete();
                    \App\Services\InvoiceService::recalc($invoice);
                });
            }

            return $fee;
        });
    }

    /**
     * Update or create the invoice item for the given transport fee.
     */
    public static function syncInvoice(TransportFee $fee, ?Votehead $votehead = null): void
    {
        $votehead ??= self::transportVotehead();

        DB::transaction(function () use ($fee, $votehead) {
            $invoice = InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);

            // Check for existing transport invoice items (including soft-deleted ones)
            // This prevents accidentally replacing non-transport items
            $existingItem = InvoiceItem::withTrashed()
                ->where('invoice_id', $invoice->id)
                ->where('votehead_id', $votehead->id)
                ->where('source', 'transport')
                ->first();

            $payload = [
                'amount' => $fee->amount,
                'status' => 'active',
                'effective_date' => null,
                'source' => 'transport',
                'posted_at' => now(),
            ];

            if ($existingItem) {
                // Restore if soft-deleted
                if ($existingItem->trashed()) {
                    $existingItem->restore();
                }
                $payload['original_amount'] = $existingItem->original_amount ?? $existingItem->amount;
                // Update existing item
                $existingItem->update($payload);
                $item = $existingItem;
            } else {
                // Create new item
                $payload['invoice_id'] = $invoice->id;
                $payload['votehead_id'] = $votehead->id;
                $payload['original_amount'] = $fee->amount;
                $item = InvoiceItem::create($payload);
            }

            // Preserve original amount if it existed previously
            if ($item->wasRecentlyCreated === false && $item->original_amount === null) {
                $item->update(['original_amount' => $item->amount]);
            }

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
            // Find all transport fees created by this import
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
                // Delete invoice items for this transport fee
                $invoice = \App\Services\InvoiceService::ensure($fee->student_id, $fee->year, $fee->term);
                $invoiceIds->push($invoice->id);

                $deleted = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $votehead->id)
                    ->where('source', 'transport')
                    ->delete();
                
                $itemsDeleted += $deleted;

                // Delete drop-off point assignments (student assignments)
                $assignment = \App\Models\StudentAssignment::where('student_id', $fee->student_id)->first();
                if ($assignment) {
                    // Only remove if it matches the import
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

                // Clear student drop-off point info if it matches
                $student = \App\Models\Student::find($fee->student_id);
                if ($student && $student->drop_off_point_id == $fee->drop_off_point_id) {
                    $student->update([
                        'drop_off_point_id' => null,
                        'drop_off_point_other' => null,
                    ]);
                }

                // Delete the transport fee record
                $fee->delete();
            }

            // Recalculate affected invoices
            foreach ($invoiceIds->unique() as $invoiceId) {
                $invoice = \App\Models\Invoice::find($invoiceId);
                if ($invoice) {
                    \App\Services\InvoiceService::recalc($invoice);
                }
            }

            // Mark import as reversed
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


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
            $academicYearId
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

            self::syncInvoice($fee, self::transportVotehead());

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

            $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                ->where('votehead_id', $votehead->id)
                ->first();

            $payload = [
                'amount' => $fee->amount,
                'status' => 'active',
                'effective_date' => null,
                'source' => 'transport',
                'posted_at' => now(),
            ];

            if ($existingItem) {
                $payload['original_amount'] = $existingItem->original_amount ?? $existingItem->amount;
            } else {
                $payload['original_amount'] = $fee->amount;
            }

            $item = InvoiceItem::updateOrCreate(
                ['invoice_id' => $invoice->id, 'votehead_id' => $votehead->id],
                $payload
            );

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
}


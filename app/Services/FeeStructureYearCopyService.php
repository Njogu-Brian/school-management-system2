<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Copies fee structures (and votehead/term amounts) from one academic year to another
 * as independent rows. Later edits to either year do not affect the other.
 */
class FeeStructureYearCopyService
{
    /**
     * Copy all active fee structures from the previous academic year into $toYear.
     * Previous = highest year value strictly less than the target year.
     *
     * @return array{copied:int,skipped:int,filled:int,from:?int,to:int}
     */
    public function copyFromPreviousYear(AcademicYear $toYear): array
    {
        $previous = AcademicYear::query()
            ->whereRaw('CAST(year AS UNSIGNED) < ?', [(int) $toYear->year])
            ->orderByRaw('CAST(year AS UNSIGNED) DESC')
            ->first();

        if (!$previous) {
            return [
                'copied' => 0,
                'skipped' => 0,
                'filled' => 0,
                'from' => null,
                'to' => (int) $toYear->year,
            ];
        }

        return $this->copyYear($previous, $toYear);
    }

    /**
     * Copy fee structures from $fromYear to $toYear as independent records.
     * Existing target structures that already have charges are left alone unless $force is true.
     * Empty target structures (no charges) are filled from the source.
     *
     * @return array{copied:int,skipped:int,filled:int,from:int,to:int}
     */
    public function copyYear(AcademicYear $fromYear, AcademicYear $toYear, bool $force = false): array
    {
        $copied = 0;
        $skipped = 0;
        $filled = 0;

        $termMap = $this->buildTermIdMap($fromYear, $toYear);

        $sources = FeeStructure::query()
            ->with('charges')
            ->where('academic_year_id', $fromYear->id)
            ->where('is_active', true)
            ->get();

        DB::transaction(function () use ($sources, $fromYear, $toYear, $termMap, $force, &$copied, &$skipped, &$filled) {
            foreach ($sources as $source) {
                $targetTermId = $this->resolveTargetTermId($source->term_id, $termMap);

                $existing = $this->findMatchingStructure(
                    (int) $source->classroom_id,
                    $toYear->id,
                    $source->student_category_id !== null ? (int) $source->student_category_id : null,
                    $targetTermId,
                    $source->stream_id !== null ? (int) $source->stream_id : null,
                );

                if ($existing) {
                    $existing->loadMissing('charges');
                    $hasCharges = $existing->charges->isNotEmpty();

                    if ($hasCharges && !$force) {
                        $skipped++;
                        continue;
                    }

                    $existing->charges()->delete();
                    $this->copyCharges($source, $existing);
                    $existing->update([
                        'name' => $source->name,
                        'year' => $toYear->year,
                        'parent_structure_id' => $source->id,
                        'is_active' => true,
                    ]);
                    $filled++;
                    continue;
                }

                $newStructure = FeeStructure::create([
                    'name' => $source->name,
                    'classroom_id' => $source->classroom_id,
                    'academic_year_id' => $toYear->id,
                    'term_id' => $targetTermId,
                    'stream_id' => $source->stream_id,
                    'student_category_id' => $source->student_category_id,
                    'year' => $toYear->year,
                    'version' => 1,
                    'parent_structure_id' => $source->id,
                    'is_active' => true,
                    'created_by' => $source->created_by,
                ]);

                $this->copyCharges($source, $newStructure);
                $copied++;
            }
        });

        Log::info('Fee structures copied between academic years', [
            'from_year' => $fromYear->year,
            'to_year' => $toYear->year,
            'copied' => $copied,
            'filled' => $filled,
            'skipped' => $skipped,
            'force' => $force,
        ]);

        return [
            'copied' => $copied,
            'skipped' => $skipped,
            'filled' => $filled,
            'from' => (int) $fromYear->year,
            'to' => (int) $toYear->year,
        ];
    }

    /**
     * @return array<int, int|null> source term_id => target term_id
     */
    private function buildTermIdMap(AcademicYear $fromYear, AcademicYear $toYear): array
    {
        $fromTerms = Term::query()
            ->where('academic_year_id', $fromYear->id)
            ->get();
        $toTerms = Term::query()
            ->where('academic_year_id', $toYear->id)
            ->get();

        $map = [];
        foreach ($fromTerms as $fromTerm) {
            $match = $toTerms->first(function (Term $toTerm) use ($fromTerm) {
                return strcasecmp(trim((string) $toTerm->name), trim((string) $fromTerm->name)) === 0;
            });

            if (!$match) {
                $fromNumber = $this->termNumber($fromTerm->name);
                if ($fromNumber !== null) {
                    $match = $toTerms->first(fn (Term $toTerm) => $this->termNumber($toTerm->name) === $fromNumber);
                }
            }

            $map[(int) $fromTerm->id] = $match?->id;
        }

        return $map;
    }

    private function resolveTargetTermId(?int $sourceTermId, array $termMap): ?int
    {
        if ($sourceTermId === null) {
            return null;
        }

        return $termMap[$sourceTermId] ?? null;
    }

    private function termNumber(?string $name): ?int
    {
        if ($name === null || $name === '') {
            return null;
        }

        if (preg_match('/(\d+)/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function findMatchingStructure(
        int $classroomId,
        int $academicYearId,
        ?int $studentCategoryId,
        ?int $termId,
        ?int $streamId
    ): ?FeeStructure {
        $conditions = ['classroom_id = ?', 'academic_year_id = ?', 'is_active = 1'];
        $bindings = [$classroomId, $academicYearId];

        if ($studentCategoryId === null) {
            $conditions[] = 'student_category_id IS NULL';
        } else {
            $conditions[] = 'student_category_id = ?';
            $bindings[] = $studentCategoryId;
        }

        if ($termId === null) {
            $conditions[] = 'term_id IS NULL';
        } else {
            $conditions[] = 'term_id = ?';
            $bindings[] = $termId;
        }

        if ($streamId === null) {
            $conditions[] = 'stream_id IS NULL';
        } else {
            $conditions[] = 'stream_id = ?';
            $bindings[] = $streamId;
        }

        return FeeStructure::query()
            ->whereRaw(implode(' AND ', $conditions), $bindings)
            ->first();
    }

    private function copyCharges(FeeStructure $source, FeeStructure $target): void
    {
        foreach ($source->charges as $charge) {
            FeeCharge::create([
                'fee_structure_id' => $target->id,
                'votehead_id' => $charge->votehead_id,
                'term' => $charge->term,
                'amount' => $charge->amount,
            ]);
        }
    }
}

<?php

use App\Models\AcademicYear;
use App\Services\FeeStructureYearCopyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * One-time backfill: for each academic year that has no fee charges yet,
 * copy fee structures from the previous year as independent rows.
 * Safe on re-run: structures that already have charges are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!class_exists(FeeStructureYearCopyService::class)) {
            return;
        }

        $service = app(FeeStructureYearCopyService::class);
        $years = AcademicYear::query()
            ->orderByRaw('CAST(year AS UNSIGNED) ASC')
            ->get();

        foreach ($years as $year) {
            $result = $service->copyFromPreviousYear($year);
            if (($result['copied'] + $result['filled']) > 0) {
                Log::info('Migration copied fee structures to academic year', $result);
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: cloned fee structures are operational data and must not be deleted on rollback.
    }
};

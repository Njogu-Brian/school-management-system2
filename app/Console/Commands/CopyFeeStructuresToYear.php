<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Services\FeeStructureYearCopyService;
use Illuminate\Console\Command;

class CopyFeeStructuresToYear extends Command
{
    protected $signature = 'finance:copy-fee-structures
                            {--from= : Source academic year (e.g. 2026)}
                            {--to= : Target academic year (e.g. 2027)}
                            {--force : Overwrite target structures that already have charges}';

    protected $description = 'Copy fee structures (per class + student category) from one academic year to another as independent rows';

    public function handle(FeeStructureYearCopyService $service): int
    {
        $fromYearValue = $this->option('from');
        $toYearValue = $this->option('to');

        if (!$fromYearValue || !$toYearValue) {
            $this->error('Both --from and --to are required (e.g. --from=2026 --to=2027).');
            return self::FAILURE;
        }

        $fromYear = AcademicYear::query()->where('year', $fromYearValue)->first();
        $toYear = AcademicYear::query()->where('year', $toYearValue)->first();

        if (!$fromYear) {
            $this->error("Source academic year {$fromYearValue} not found.");
            return self::FAILURE;
        }

        if (!$toYear) {
            $this->error("Target academic year {$toYearValue} not found.");
            return self::FAILURE;
        }

        if ((int) $fromYear->id === (int) $toYear->id) {
            $this->error('Source and target academic years must be different.');
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $result = $service->copyYear($fromYear, $toYear, $force);

        $this->info(sprintf(
            'Copied fee structures from %s to %s: created %d, filled empty %d, skipped %d%s.',
            $result['from'],
            $result['to'],
            $result['copied'],
            $result['filled'],
            $result['skipped'],
            $force ? ' (force)' : ''
        ));

        return self::SUCCESS;
    }
}

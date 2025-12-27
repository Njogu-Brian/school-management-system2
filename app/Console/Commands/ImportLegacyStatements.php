<?php

namespace App\Console\Commands;

use App\Services\LegacyFinanceImportService;
use Illuminate\Console\Command;

class ImportLegacyStatements extends Command
{
    protected $signature = 'finance:import-legacy {pdf : Path to the legacy statements PDF} {--class= : Optional class/grade label}';

    protected $description = 'Import legacy finance statements from a PDF into legacy staging tables';

    public function handle(LegacyFinanceImportService $service): int
    {
        $pdf = (string) $this->argument('pdf');
        $classLabel = $this->option('class');

        if (!is_file($pdf)) {
            $this->error("PDF not found at {$pdf}");
            return static::FAILURE;
        }

        $this->info("Importing legacy statements from {$pdf}...");

        $result = $service->import($pdf, $classLabel, auth()->id() ?: null);

        $this->table(
            ['Batch ID', 'File', 'Students', 'Terms Imported', 'Terms Draft'],
            [[
                $result['batch_id'],
                $result['file'],
                $result['students_total'],
                $result['terms_imported'],
                $result['terms_draft'],
            ]]
        );

        $this->info('Done.');

        return static::SUCCESS;
    }
}


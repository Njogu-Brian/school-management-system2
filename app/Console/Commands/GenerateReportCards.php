<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportCardBatchService;
use App\Models\Academics\Classroom;

class GenerateReportCards extends Command
{
    protected $signature = 'reports:generate {academic_year_id} {term_id}';
    protected $description = 'Generate report cards for all classes in a given academic year and term';

    public function handle(ReportCardBatchService $service)
    {
        $yearId = $this->argument('academic_year_id');
        $termId = $this->argument('term_id');

        $classes = Classroom::all();
        foreach ($classes as $class) {
            $this->info("Processing {$class->name}...");
            $service->generateForClass($yearId, $termId, $class->id);
        }

        $this->info('All report cards generated successfully!');
    }
}

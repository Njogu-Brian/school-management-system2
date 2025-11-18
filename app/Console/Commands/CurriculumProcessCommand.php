<?php

namespace App\Console\Commands;

use App\Models\CurriculumDesign;
use App\Jobs\ParseCurriculumDesignJob;
use Illuminate\Console\Command;

class CurriculumProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'curriculum:process {id : The ID of the curriculum design to process}';

    /**
     * The console command description.
     */
    protected $description = 'Manually process a curriculum design PDF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        $curriculumDesign = CurriculumDesign::find($id);
        
        if (!$curriculumDesign) {
            $this->error("Curriculum design with ID {$id} not found.");
            return 1;
        }

        $this->info("Processing curriculum design: {$curriculumDesign->title}");
        
        // Dispatch the job
        ParseCurriculumDesignJob::dispatch($curriculumDesign->id);
        
        $this->info("Processing job dispatched. Check queue workers and logs for progress.");
        
        return 0;
    }
}

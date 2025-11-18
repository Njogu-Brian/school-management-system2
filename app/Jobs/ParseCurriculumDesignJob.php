<?php

namespace App\Jobs;

use App\Models\CurriculumDesign;
use App\Services\CurriculumParsingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseCurriculumDesignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour timeout for large PDFs

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $curriculumDesignId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CurriculumParsingService $parsingService): void
    {
        $curriculumDesign = CurriculumDesign::find($this->curriculumDesignId);

        if (!$curriculumDesign) {
            Log::error('Curriculum design not found for parsing', [
                'curriculum_design_id' => $this->curriculumDesignId,
            ]);
            return;
        }

        Log::info('Starting curriculum design parsing', [
            'curriculum_design_id' => $this->curriculumDesignId,
            'title' => $curriculumDesign->title,
        ]);

        $success = $parsingService->parse($curriculumDesign);

        if ($success) {
            Log::info('Curriculum design parsed successfully', [
                'curriculum_design_id' => $this->curriculumDesignId,
            ]);
        } else {
            Log::error('Curriculum design parsing failed', [
                'curriculum_design_id' => $this->curriculumDesignId,
                'error' => $curriculumDesign->error_notes,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $curriculumDesign = CurriculumDesign::find($this->curriculumDesignId);

        if ($curriculumDesign) {
            $curriculumDesign->update([
                'status' => 'failed',
                'error_notes' => $exception->getMessage(),
            ]);
        }

        Log::error('ParseCurriculumDesignJob failed', [
            'curriculum_design_id' => $this->curriculumDesignId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

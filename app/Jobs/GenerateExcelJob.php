<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ExcelExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ExcelGeneratedNotification;
use Illuminate\Support\Collection;

class GenerateExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $headings;
    protected $filename;
    protected $options;
    protected $userId;
    protected $exportType;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $headings, $filename, $options = [], $userId = null, $exportType = 'generic')
    {
        $this->data = $data instanceof Collection ? $data : collect($data);
        $this->headings = $headings;
        $this->filename = $filename;
        $this->options = $options;
        $this->userId = $userId ?? auth()->id();
        $this->exportType = $exportType;
    }

    /**
     * Execute the job.
     */
    public function handle(ExcelExportService $excelService): void
    {
        try {
            // Generate Excel based on export type
            $result = $this->generateExcel($excelService);

            if ($result && (is_array($result) ? ($result['success'] ?? false) : true)) {
                // Get result URL/path for notification
                $resultData = is_array($result) ? $result : [
                    'success' => true,
                    'path' => $result,
                    'url' => $result,
                    'filename' => $this->filename,
                ];

                // Notify user if requested
                if ($this->options['notify'] ?? false) {
                    $user = \App\Models\User::find($this->userId);
                    if ($user) {
                        $user->notify(new ExcelGeneratedNotification($resultData, $this->filename));
                    }
                }

                Log::info('Excel generated successfully', [
                    'path' => is_array($result) ? ($result['path'] ?? $result['url'] ?? '') : $result,
                    'url' => is_array($result) ? ($result['url'] ?? '') : $result,
                    'user_id' => $this->userId,
                    'export_type' => $this->exportType,
                ]);
            } else {
                Log::error('Excel generation failed in job', [
                    'user_id' => $this->userId,
                    'export_type' => $this->exportType,
                    'error' => is_array($result) ? ($result['error'] ?? 'Unknown error') : 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excel generation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
                'export_type' => $this->exportType,
            ]);

            throw $e;
        }
    }

    /**
     * Generate Excel based on export type
     */
    protected function generateExcel(ExcelExportService $excelService)
    {
        switch ($this->exportType) {
            case 'schemes_of_work':
                return $excelService->exportSchemesOfWork($this->data, $this->filename, array_merge($this->options, ['save' => true]));
            case 'lesson_plans':
                return $excelService->exportLessonPlans($this->data, $this->filename, array_merge($this->options, ['save' => true]));
            case 'competencies':
                return $excelService->exportCompetencies($this->data, $this->filename, array_merge($this->options, ['save' => true]));
            default:
                return $excelService->export(
                    $this->data,
                    $this->headings,
                    $this->filename,
                    array_merge($this->options, ['save' => true])
                );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Excel generation job failed permanently', [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'export_type' => $this->exportType,
        ]);
    }
}


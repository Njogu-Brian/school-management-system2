<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PDFExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PDFGeneratedNotification;

class GeneratePDFJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $view;
    protected $data;
    protected $options;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($view, $data, $options = [], $userId = null)
    {
        $this->view = $view;
        $this->data = $data;
        $this->options = $options;
        $this->userId = $userId ?? auth()->id();
    }

    /**
     * Execute the job.
     */
    public function handle(PDFExportService $pdfService): void
    {
        try {
            // Generate PDF
            $result = $pdfService->generatePDF(
                $this->view,
                $this->data,
                array_merge($this->options, ['save' => true])
            );

            if ($result['success']) {
                // Notify user if requested
                if ($this->options['notify'] ?? false) {
                    $user = \App\Models\User::find($this->userId);
                    if ($user) {
                        $user->notify(new PDFGeneratedNotification($result['url'], $this->options['filename'] ?? 'document.pdf'));
                    }
                }

                Log::info('PDF generated successfully', [
                    'path' => $result['path'],
                    'user_id' => $this->userId,
                ]);
            } else {
                Log::error('PDF generation failed in job', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'user_id' => $this->userId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PDF generation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PDF generation job failed permanently', [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
        ]);
    }
}


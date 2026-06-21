<?php

namespace App\Jobs\Website;

use App\Services\Website\ExecutiveIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeExecutiveAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ExecutiveIntelligenceService $service): void
    {
        $service->computePredictiveAlerts();
    }
}

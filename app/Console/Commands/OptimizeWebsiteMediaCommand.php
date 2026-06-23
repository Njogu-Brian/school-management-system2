<?php

namespace App\Console\Commands;

use App\Services\Website\MediaOptimizationService;
use Illuminate\Console\Command;

class OptimizeWebsiteMediaCommand extends Command
{
    protected $signature = 'website:optimize-media';

    protected $description = 'Generate WebP responsive variants for website media library images';

    public function handle(MediaOptimizationService $optimizer): int
    {
        if (! $optimizer->supportsOptimization()) {
            $this->error('GD with WebP support is required. Install php-gd with WebP enabled.');

            return self::FAILURE;
        }

        $count = $optimizer->optimizeAllPending();
        $this->info("Optimized {$count} media item(s).");

        return self::SUCCESS;
    }
}

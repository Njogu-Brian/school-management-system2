<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;

/**
 * One-time cleanup for branding and gallery images uploaded before
 * ImageOptimizer existed — the 3000x3000 school logo being the worst of them.
 *
 * New uploads are optimised on the way in, so this is only needed once per
 * environment (and after restoring an old storage directory).
 */
class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize
                            {--dry-run : Report what would change without writing}
                            {--max-width=1920 : Longest allowed width}
                            {--max-height=1080 : Longest allowed height}
                            {--min-kb=100 : Skip files already smaller than this}';

    protected $description = 'Downscale and recompress images already stored in the public images directory';

    public function handle(ImageOptimizer $optimizer): int
    {
        $dir = public_images_path();

        if (! is_dir($dir)) {
            $this->error("Images directory not found: {$dir}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $maxWidth = (int) $this->option('max-width');
        $maxHeight = (int) $this->option('max-height');
        $minBytes = (int) $this->option('min-kb') * 1024;

        $this->info("Scanning {$dir}");
        $this->line($dryRun ? 'Dry run — nothing will be written.' : 'Writing changes. Files only ever get smaller.');
        $this->newLine();

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        $rows = [];
        $totalBefore = 0;
        $totalAfter = 0;

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                continue;
            }

            $before = $file->getSize();
            if ($before < $minBytes) {
                continue;
            }

            $path = $file->getPathname();
            $dimensions = @getimagesize($path);

            if ($dryRun) {
                // Optimise a copy so the original is untouched but the reported
                // saving is the real one rather than an estimate.
                $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imgopt_' . uniqid() . '.' . $file->getExtension();
                copy($path, $temp);
                $optimizer->optimize($temp, $maxWidth, $maxHeight);
                clearstatcache(true, $temp);
                $after = filesize($temp);
                @unlink($temp);
            } else {
                $optimizer->optimize($path, $maxWidth, $maxHeight);
                clearstatcache(true, $path);
                $after = filesize($path);
            }

            $totalBefore += $before;
            $totalAfter += $after;

            $rows[] = [
                $file->getFilename(),
                sprintf('%sx%s', $dimensions[0] ?? '?', $dimensions[1] ?? '?'),
                number_format($before / 1024) . ' KB',
                number_format($after / 1024) . ' KB',
                $before > 0 ? sprintf('%d%%', (1 - $after / $before) * 100) : '-',
            ];
        }

        if (! $rows) {
            $this->info('Nothing to do — no images above the size threshold.');

            return self::SUCCESS;
        }

        $this->table(['File', 'Dimensions', 'Before', 'After', 'Saved'], $rows);

        $this->info(sprintf(
            '%d file(s): %s KB -> %s KB (%s KB saved, %d%%)',
            count($rows),
            number_format($totalBefore / 1024),
            number_format($totalAfter / 1024),
            number_format(($totalBefore - $totalAfter) / 1024),
            $totalBefore > 0 ? (1 - $totalAfter / $totalBefore) * 100 : 0
        ));

        if ($dryRun) {
            $this->newLine();
            $this->line('Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}

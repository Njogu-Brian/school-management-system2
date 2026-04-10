<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeLocalStorageAndDocuments extends Command
{
    protected $signature = 'storage:purge-local-and-documents
                            {--dry-run : Show what would be deleted}
                            {--yes : Do not prompt for confirmation}
                            {--purge-local : Delete local EC2 copies (public/private local disks)}
                            {--purge-local-backup-bank-statements : Also delete storage/app/private/bank-statements__LOCAL_BACKUP if present}
                            {--purge-documents= : Delete document DB records. Options: missing|all}
                            {--force : Force delete DB records (bypass soft delete)}';

    protected $description = 'Purge local EC2 file copies and/or delete document DB records (useful after migrating to S3).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $assumeYes = (bool) $this->option('yes');

        $purgeLocal = (bool) $this->option('purge-local');
        $purgeLocalBackup = (bool) $this->option('purge-local-backup-bank-statements');
        $purgeDocuments = $this->option('purge-documents'); // missing|all|null
        $force = (bool) $this->option('force');

        if (!$purgeLocal && !$purgeLocalBackup && !$purgeDocuments) {
            $this->error('Nothing to do. Provide --purge-local and/or --purge-documents=missing|all.');
            return 1;
        }

        if ($purgeDocuments && !in_array($purgeDocuments, ['missing', 'all'], true)) {
            $this->error('Invalid --purge-documents value. Use: missing|all');
            return 1;
        }

        $this->line('This command can permanently remove files and/or DB records.');
        $this->line('Recommended: run with --dry-run first.');

        if (!$dryRun && !$assumeYes) {
            if (!$this->confirm('Proceed?', false)) {
                $this->warn('Cancelled.');
                return 0;
            }
        }

        $deletedFileCount = 0;
        $deletedDirCount = 0;
        $deletedDocCount = 0;

        if ($purgeLocal) {
            $targets = [
                ['disk' => 'public', 'dir' => 'receipts'],
                ['disk' => 'public', 'dir' => 'documents'],
                ['disk' => 'public', 'dir' => 'generated_documents'],
                ['disk' => 'public', 'dir' => 'staff_photos'],
                ['disk' => 'public', 'dir' => 'admissions'],
                ['disk' => 'public', 'dir' => 'homeworks'],
                ['disk' => 'public', 'dir' => 'diary_entries'],

                ['disk' => 'private', 'dir' => 'bank-statements'],
                ['disk' => 'private', 'dir' => 'curriculum_designs'],
                ['disk' => 'private', 'dir' => 'admissions/documents'],
                ['disk' => 'private', 'dir' => 'parent_ids'],
            ];

            foreach ($targets as $t) {
                $disk = $t['disk'];
                $dir = $t['dir'];
                try {
                    if (!Storage::disk($disk)->exists($dir)) {
                        continue;
                    }

                    if ($dryRun) {
                        $count = count(Storage::disk($disk)->allFiles($dir));
                        $this->line("Would delete directory: {$disk}:{$dir} ({$count} files)");
                        $deletedFileCount += $count;
                        $deletedDirCount++;
                        continue;
                    }

                    Storage::disk($disk)->deleteDirectory($dir);
                    $this->info("Deleted directory: {$disk}:{$dir}");
                    $deletedDirCount++;
                } catch (\Throwable $e) {
                    $this->error("Failed deleting {$disk}:{$dir} - {$e->getMessage()}");
                }
            }
        }

        if ($purgeLocalBackup) {
            // This folder is not part of Laravel's "private" disk. We still attempt to delete it via the local filesystem disk.
            $backupPath = storage_path('app/private/bank-statements__LOCAL_BACKUP');

            if ($dryRun) {
                $this->line("Would delete local backup folder: {$backupPath}");
            } else {
                try {
                    if (is_dir($backupPath)) {
                        $this->deleteDirectoryRecursive($backupPath);
                        $this->info("Deleted local backup folder: {$backupPath}");
                    }
                } catch (\Throwable $e) {
                    $this->error("Failed deleting backup folder {$backupPath} - {$e->getMessage()}");
                }
            }
        }

        if ($purgeDocuments) {
            if ($purgeDocuments === 'all') {
                $query = Document::withTrashed();
            } else {
                // missing: delete records whose file is missing everywhere (configured + local fallbacks)
                $query = Document::withTrashed()->whereNotNull('file_path')->where('file_path', '!=', '');
            }

            $query->chunkById(200, function ($docs) use ($purgeDocuments, $dryRun, $force, &$deletedDocCount) {
                foreach ($docs as $doc) {
                    if ($purgeDocuments === 'missing') {
                        $path = $doc->file_path;
                        if (!$this->existsAnywhere($path)) {
                            if ($dryRun) {
                                $this->line("Would delete document record #{$doc->id} (missing file): {$path}");
                                $deletedDocCount++;
                            } else {
                                $force ? $doc->forceDelete() : $doc->delete();
                                $deletedDocCount++;
                            }
                        }
                        continue;
                    }

                    // all
                    if ($dryRun) {
                        $this->line("Would delete document record #{$doc->id}: {$doc->file_path}");
                        $deletedDocCount++;
                    } else {
                        $force ? $doc->forceDelete() : $doc->delete();
                        $deletedDocCount++;
                    }
                }
            });
        }

        $this->newLine();
        $this->info('Done.');
        $this->line('Summary:');
        $this->line(" - Directories deleted: {$deletedDirCount}");
        $this->line(" - File deletions (estimated in dry-run): {$deletedFileCount}");
        $this->line(" - Document records deleted: {$deletedDocCount}");

        return 0;
    }

    protected function existsAnywhere(string $path): bool
    {
        $configuredPublic = config('filesystems.public_disk', 'public');
        $configuredPrivate = config('filesystems.private_disk', 'private');

        $candidates = array_values(array_unique([
            $configuredPublic,
            $configuredPrivate,
            'public',
            'private',
            's3_public',
            's3_private',
        ]));

        foreach ($candidates as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return false;
    }

    protected function deleteDirectoryRecursive(string $dir): void
    {
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}


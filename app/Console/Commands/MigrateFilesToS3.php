<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToS3 extends Command
{
    protected $signature = 'storage:migrate-to-s3 
                            {--dry-run : List files to migrate without copying}
                            {--tables= : Comma-separated table.column pairs, e.g. documents.file_path,bank_statement_transactions.statement_file_path}';

    protected $description = 'Migrate existing local files to S3 object storage. Run after setting FILESYSTEM_PUBLIC_DISK=s3_public and FILESYSTEM_PRIVATE_DISK=s3_private.';

    protected array $defaultMappings = [
        ['table' => 'documents', 'column' => 'file_path', 'disk' => 'public'],
        ['table' => 'staff_documents', 'column' => 'file_path', 'disk' => 'public'],
        ['table' => 'curriculum_designs', 'column' => 'file_path', 'disk' => 'private'],
        ['table' => 'bank_statement_transactions', 'column' => 'statement_file_path', 'disk' => 'private'],
        ['table' => 'generated_documents', 'column' => 'pdf_path', 'disk' => 'public'],
        ['table' => 'homework', 'column' => 'file_path', 'disk' => 'public'],
        ['table' => 'students', 'column' => 'photo_path', 'disk' => 'public'],
        ['table' => 'students', 'column' => 'birth_certificate_path', 'disk' => 'private'],
        ['table' => 'staff', 'column' => 'photo', 'disk' => 'public'],
        ['table' => 'vehicles', 'column' => 'insurance_document', 'disk' => 'public'],
        ['table' => 'vehicles', 'column' => 'logbook_document', 'disk' => 'public'],
        ['table' => 'online_admissions', 'column' => 'passport_photo', 'disk' => 'public'],
        ['table' => 'online_admissions', 'column' => 'birth_certificate', 'disk' => 'private'],
        ['table' => 'online_admissions', 'column' => 'father_id_document', 'disk' => 'private'],
        ['table' => 'online_admissions', 'column' => 'mother_id_document', 'disk' => 'private'],
    ];

    public function handle(): int
    {
        $publicDisk = config('filesystems.public_disk', 'public');
        $privateDisk = config('filesystems.private_disk', 'private');

        if (!in_array($publicDisk, ['s3_public', 's3']) && !in_array($privateDisk, ['s3_private', 's3'])) {
            $this->warn('S3 disks not configured. Set FILESYSTEM_PUBLIC_DISK=s3_public and FILESYSTEM_PRIVATE_DISK=s3_private in .env');
            return 1;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('DRY RUN - no files will be copied');
        }

        $copied = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->defaultMappings as $mapping) {
            if (!Schema::hasTable($mapping['table']) || !Schema::hasColumn($mapping['table'], $mapping['column'])) {
                continue;
            }

            $localDisk = $mapping['disk'] === 'public' ? 'public' : 'private';
            $s3Disk = $mapping['disk'] === 'public' ? 's3_public' : 's3_private';

            $paths = DB::table($mapping['table'])
                ->whereNotNull($mapping['column'])
                ->where($mapping['column'], '!=', '')
                ->pluck($mapping['column'])
                ->unique();

            foreach ($paths as $path) {
                if (!Storage::disk($localDisk)->exists($path)) {
                    $this->line("  Skip (missing): {$path}");
                    $skipped++;
                    continue;
                }

                if (Storage::disk($s3Disk)->exists($path)) {
                    $this->line("  Skip (exists): {$path}");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  Would copy: {$path}");
                    $copied++;
                    continue;
                }

                try {
                    $contents = Storage::disk($localDisk)->get($path);
                    Storage::disk($s3Disk)->put($path, $contents);
                    $this->line("  Copied: {$path}");
                    $copied++;
                } catch (\Throwable $e) {
                    $this->error("  Error: {$path} - " . $e->getMessage());
                    $errors++;
                }
            }
        }

        // Migrate directories (receipts, reports, etc.) - scan storage
        $dirs = [
            ['local' => 'public', 's3' => 's3_public', 'subdirs' => ['receipts', 'documents', 'staff_photos', 'admissions', 'pos/products', 'diary_entries', 'homeworks', 'homework_submissions', 'email_attachments', 'communication_attachments', 'whatsapp_media', 'students/photos']],
            ['local' => 'private', 's3' => 's3_private', 'subdirs' => ['bank-statements', 'curriculum_designs', 'legacy-imports', 'parent_ids', 'admissions/documents']],
        ];

        foreach ($dirs as $dirConfig) {
            foreach ($dirConfig['subdirs'] as $subdir) {
                $files = Storage::disk($dirConfig['local'])->allFiles($subdir);
                foreach ($files as $path) {
                    if (Storage::disk($dirConfig['s3'])->exists($path)) {
                        $skipped++;
                        continue;
                    }
                    if ($dryRun) {
                        $this->line("  Would copy: {$path}");
                        $copied++;
                        continue;
                    }
                    try {
                        $contents = Storage::disk($dirConfig['local'])->get($path);
                        Storage::disk($dirConfig['s3'])->put($path, $contents);
                        $this->line("  Copied: {$path}");
                        $copied++;
                    } catch (\Throwable $e) {
                        $this->error("  Error: {$path} - " . $e->getMessage());
                        $errors++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Done. Copied: {$copied}, Skipped: {$skipped}, Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}

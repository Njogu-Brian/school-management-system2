<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupObjectSize extends Command
{
    protected $signature = 'backup:object-size {key : Object key relative to the backup disk}';

    protected $description = 'Print the byte size of one backup object, or fail if it cannot be read';

    public function handle(): int
    {
        $key = (string) $this->argument('key');
        if ($key === '' || str_contains($key, '..')) {
            $this->error('Refusing an empty or parent-directory object key.');

            return self::FAILURE;
        }

        $disk = (string) config('backup.s3_disk', 's3_private');
        $size = Storage::disk($disk)->size($key);
        if (! is_int($size) || $size < 0) {
            $this->error('Could not read the backup object size.');

            return self::FAILURE;
        }

        $this->output->writeln((string) $size);

        return self::SUCCESS;
    }
}

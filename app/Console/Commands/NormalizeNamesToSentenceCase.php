<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\OnlineAdmission;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Support\NameCase;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class NormalizeNamesToSentenceCase extends Command
{
    protected $signature = 'names:sentence-case
                            {--dry-run : Show changes without writing}
                            {--chunk=500 : Chunk size}';

    protected $description = 'Convert student/staff/parent/guardian/emergency-contact names to sentence case.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));

        /** @var array<class-string<Model>> $models */
        $models = [
            Student::class,
            Staff::class,
            ParentInfo::class,
            Family::class,
            OnlineAdmission::class,
            User::class,
        ];

        $totalChanged = 0;

        foreach ($models as $modelClass) {
            $attrs = method_exists($modelClass, 'nameAttributesToSentenceCase')
                ? $modelClass::nameAttributesToSentenceCase()
                : [];

            if (empty($attrs)) {
                continue;
            }

            $this->line('');
            $this->info($modelClass);

            $modelClass::query()
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use ($modelClass, $attrs, $dryRun, &$totalChanged) {
                    foreach ($rows as $row) {
                        $updates = [];
                        foreach ($attrs as $attr) {
                            $raw = $row->getAttribute($attr);
                            if (!is_string($raw) && $raw !== null) {
                                continue;
                            }
                            $normalized = NameCase::sentence($raw);
                            if ($normalized !== $raw) {
                                $updates[$attr] = $normalized;
                            }
                        }

                        if (empty($updates)) {
                            continue;
                        }

                        $totalChanged++;

                        if ($dryRun) {
                            $this->warn('ID ' . $row->getKey() . ' ' . json_encode($updates, JSON_UNESCAPED_UNICODE));
                            continue;
                        }

                        // Avoid triggering any side effects; we only want to normalize strings.
                        $row->updateQuietly($updates);
                    }
                });
        }

        $this->line('');
        if ($dryRun) {
            $this->info('Dry run complete. Rows that would change: ' . $totalChanged);
        } else {
            $this->info('Normalization complete. Rows changed: ' . $totalChanged);
        }

        return self::SUCCESS;
    }
}


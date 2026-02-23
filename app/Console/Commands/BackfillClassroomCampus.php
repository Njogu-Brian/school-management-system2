<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Academics\Classroom;

class BackfillClassroomCampus extends Command
{
    protected $signature = 'classrooms:backfill-campus
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Update classrooms that already have campus/level_type set}';

    protected $description = 'Backfill campus and level_type on classrooms (required for senior teacher supervision)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $query = Classroom::query();
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('campus')->orWhereNull('level_type');
            });
        }

        $classrooms = $query->get();
        if ($classrooms->isEmpty()) {
            $this->info('No classrooms need backfilling.');
            return Command::SUCCESS;
        }

        $updated = 0;
        $this->info(($dryRun ? '[DRY RUN] Would update ' : 'Updating ') . $classrooms->count() . ' classrooms.');

        foreach ($classrooms as $classroom) {
            $inferred = $this->inferCampusAndLevelType($classroom->name);
            $newCampus = $inferred['campus'] ?? null;
            $newLevelType = $inferred['level_type'] ?? null;

            if (!$newCampus && !$newLevelType) {
                $this->line("  Skip: {$classroom->name} (could not infer campus/level)");
                continue;
            }

            $updates = [];
            if ($newCampus && ($force || $classroom->campus === null)) {
                $updates['campus'] = $newCampus;
            }
            if ($newLevelType && ($force || $classroom->level_type === null)) {
                $updates['level_type'] = $newLevelType;
            }

            if (empty($updates)) {
                continue;
            }

            $campusVal = isset($updates['campus']) ? $updates['campus'] : ($classroom->campus !== null ? $classroom->campus : 'null');
            $levelVal = isset($updates['level_type']) ? $updates['level_type'] : ($classroom->level_type !== null ? $classroom->level_type : 'null');
            $this->line("  {$classroom->name} -> campus={$campusVal}, level_type={$levelVal}");

            if (!$dryRun) {
                $classroom->update($updates);
            }
            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would update ' : 'Updated ') . $updated . ' classrooms.');
        $this->info('Review in Admin > Academics > Classrooms, and edit any that need manual adjustment.');

        return Command::SUCCESS;
    }

    /**
     * Infer campus and level_type from classroom name.
     * Upper Campus: Creche, PP1, PP2, Nursery, KG, Grade 1-3
     * Lower Campus: Grade 4-9
     */
    private function inferCampusAndLevelType(string $name): array
    {
        $name = strtolower(trim($name));

        // Preschool (Upper Campus)
        if (preg_match('/\b(creche|crèche|foundation|nursery|pp1|pp2|pp3|kg1|kg2|pre-primary|preprimary|baby class)\b/i', $name)) {
            return ['campus' => 'upper', 'level_type' => 'preschool'];
        }

        // Grade-based
        if (preg_match('/grade\s*(\d+)/i', $name, $m)) {
            $grade = (int) $m[1];
            if ($grade >= 1 && $grade <= 3) {
                return ['campus' => 'upper', 'level_type' => 'lower_primary'];
            }
            if ($grade >= 4 && $grade <= 6) {
                return ['campus' => 'lower', 'level_type' => 'upper_primary'];
            }
            if ($grade >= 7 && $grade <= 9) {
                return ['campus' => 'lower', 'level_type' => 'junior_high'];
            }
        }

        // Class 1–3, Std 1–3 (Lower primary)
        if (preg_match('/\b(class|std|standard)\s*[1-3]\b/i', $name)) {
            return ['campus' => 'upper', 'level_type' => 'lower_primary'];
        }
        if (preg_match('/\b(class|std|standard)\s*[4-6]\b/i', $name)) {
            return ['campus' => 'lower', 'level_type' => 'upper_primary'];
        }
        if (preg_match('/\b(class|std|standard)\s*[7-9]\b/i', $name)) {
            return ['campus' => 'lower', 'level_type' => 'junior_high'];
        }

        // Form 1–3 (Junior high)
        if (preg_match('/\bform\s*[1-3]\b/i', $name)) {
            return ['campus' => 'lower', 'level_type' => 'junior_high'];
        }

        return [];
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Academics\Subject;
use Illuminate\Support\Facades\DB;

class UpdateSubjectLevelsToLevelTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subjects:update-levels-to-types 
                            {--dry-run : Preview changes without updating the database}
                            {--force : Force update even if level is already a level type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subject levels from grade names (PP1, Grade 1, etc.) to level types (preschool, lower_primary, upper_primary, junior_high)';

    /**
     * Map grade/classroom name to level type
     */
    private function mapGradeToLevelType(string $grade): ?string
    {
        $normalized = strtolower(trim($grade));
        
        $mapping = [
            'pp1' => 'preschool',
            'pp2' => 'preschool',
            'grade 1' => 'lower_primary',
            'grade 2' => 'lower_primary',
            'grade 3' => 'lower_primary',
            'grade 4' => 'upper_primary',
            'grade 5' => 'upper_primary',
            'grade 6' => 'upper_primary',
            'grade 7' => 'junior_high',
            'grade 8' => 'junior_high',
            'grade 9' => 'junior_high',
        ];
        
        return $mapping[$normalized] ?? null;
    }

    /**
     * Check if a level is already a level type
     */
    private function isLevelType(string $level): bool
    {
        $levelTypes = ['preschool', 'lower_primary', 'upper_primary', 'junior_high'];
        return in_array(strtolower(trim($level)), $levelTypes);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('Finding subjects with grade names as levels...');
        $this->newLine();

        // Get all subjects with non-null levels
        $subjects = Subject::whereNotNull('level')->get();

        if ($subjects->isEmpty()) {
            $this->warn('No subjects found with level values.');
            return Command::SUCCESS;
        }

        $this->info("Found {$subjects->count()} subject(s) with level values.");
        $this->newLine();

        $toUpdate = [];
        $alreadyLevelTypes = [];
        $unmapped = [];

        foreach ($subjects as $subject) {
            $currentLevel = $subject->level;
            
            // Check if already a level type
            if ($this->isLevelType($currentLevel)) {
                if ($isForce) {
                    $toUpdate[] = [
                        'subject' => $subject,
                        'current' => $currentLevel,
                        'new' => $currentLevel, // No change needed, but include for force mode
                    ];
                } else {
                    $alreadyLevelTypes[] = [
                        'subject' => $subject,
                        'level' => $currentLevel,
                    ];
                }
                continue;
            }

            // Try to map to level type
            $levelType = $this->mapGradeToLevelType($currentLevel);
            
            if ($levelType) {
                $toUpdate[] = [
                    'subject' => $subject,
                    'current' => $currentLevel,
                    'new' => $levelType,
                ];
            } else {
                $unmapped[] = [
                    'subject' => $subject,
                    'level' => $currentLevel,
                ];
            }
        }

        // Display summary
        $this->info('--- Summary ---');
        $this->info("Subjects to update: " . count($toUpdate));
        $this->info("Already level types: " . count($alreadyLevelTypes));
        $this->info("Unmapped levels: " . count($unmapped));
        $this->newLine();

        // Show unmapped levels
        if (!empty($unmapped)) {
            $this->warn('Subjects with unmapped levels (will be skipped):');
            foreach ($unmapped as $item) {
                $this->line("  - {$item['subject']->code} ({$item['subject']->name}): '{$item['level']}'");
            }
            $this->newLine();
        }

        // Show subjects that are already level types
        if (!empty($alreadyLevelTypes) && !$isForce) {
            $this->info('Subjects already using level types (skipped):');
            foreach (array_slice($alreadyLevelTypes, 0, 10) as $item) {
                $this->line("  - {$item['subject']->code} ({$item['subject']->name}): '{$item['level']}'");
            }
            if (count($alreadyLevelTypes) > 10) {
                $this->line("  ... and " . (count($alreadyLevelTypes) - 10) . " more");
            }
            $this->newLine();
        }

        // Show preview of changes
        if (!empty($toUpdate)) {
            $this->info('Subjects to be updated:');
            foreach (array_slice($toUpdate, 0, 20) as $item) {
                $subject = $item['subject'];
                $current = $item['current'];
                $new = $item['new'];
                
                if ($current === $new) {
                    $this->line("  - {$subject->code} ({$subject->name}): '{$current}' (no change)");
                } else {
                    $this->line("  - {$subject->code} ({$subject->name}): '{$current}' → '{$new}'");
                }
            }
            if (count($toUpdate) > 20) {
                $this->line("  ... and " . (count($toUpdate) - 20) . " more");
            }
            $this->newLine();
        }

        if (empty($toUpdate)) {
            $this->info('No subjects need to be updated.');
            return Command::SUCCESS;
        }

        // Confirm if not dry-run
        if (!$isDryRun) {
            if (!$this->confirm('Do you want to proceed with updating these subjects?', true)) {
                $this->info('Update cancelled.');
                return Command::SUCCESS;
            }
        }

        // Perform updates
        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($toUpdate as $item) {
                $subject = $item['subject'];
                $newLevel = $item['new'];
                
                if ($subject->level === $newLevel && !$isForce) {
                    continue; // Skip if no change needed
                }

                if ($isDryRun) {
                    $this->line("DRY RUN: Would update {$subject->code} ({$subject->name}) level from '{$subject->level}' to '{$newLevel}'");
                } else {
                    $subject->level = $newLevel;
                    $subject->save();
                    $updated++;
                }
            }

            if ($isDryRun) {
                $this->newLine();
                $this->info('DRY RUN: No changes were made. Use without --dry-run to apply changes.');
                DB::rollBack();
            } else {
                DB::commit();
                $this->newLine();
                $this->info("Successfully updated {$updated} subject(s).");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
            $errors++;
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\StudentRequirement;
use App\Models\RequirementTemplate;
use App\Models\RequirementType;
use Database\Seeders\Requirements2026Seeder;

class ReseedRequirementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requirements:reseed {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete existing requirements data and reseed from Requirements2026Seeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('⚠️  WARNING: This will delete all existing requirements data!');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed? This action cannot be undone!', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting requirements reseed process...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // Delete in order of dependencies
            $this->info('Deleting existing data...');
            
            // Delete student requirements (most dependent)
            $studentRequirementCount = StudentRequirement::count();
            StudentRequirement::query()->delete();
            $this->info("  ✓ Deleted {$studentRequirementCount} student requirements");

            // Delete requirement templates
            $templateCount = RequirementTemplate::count();
            RequirementTemplate::query()->delete();
            $this->info("  ✓ Deleted {$templateCount} requirement templates");

            // Delete requirement types (optional - comment out if you want to keep types)
            // $typeCount = RequirementType::count();
            // RequirementType::query()->delete();
            // $this->info("  ✓ Deleted {$typeCount} requirement types");

            DB::commit();

            $this->newLine();
            $this->info('Data deleted successfully. Now seeding...');
            $this->newLine();

            // Run the seeder
            $seeder = new Requirements2026Seeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('✅ Requirements reseed completed successfully!');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during reseed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}

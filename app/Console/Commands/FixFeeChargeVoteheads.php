<?php

namespace App\Console\Commands;

use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\Votehead;
use Illuminate\Console\Command;

class FixFeeChargeVoteheads extends Command
{
    protected $signature = 'fees:fix-charge-voteheads 
                            {--structure-id= : Specific fee structure ID to fix}
                            {--votehead-id= : Specific votehead ID to assign (defaults to first mandatory votehead)}
                            {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix fee charges that have null votehead_id by assigning voteheads';

    public function handle()
    {
        $structureId = $this->option('structure-id');
        $voteheadId = $this->option('votehead-id');
        $dryRun = $this->option('dry-run');

        // Find charges with null votehead_id
        $query = FeeCharge::whereNull('votehead_id');
        
        if ($structureId) {
            $query->where('fee_structure_id', $structureId);
        }

        $charges = $query->get();

        if ($charges->isEmpty()) {
            $this->info('No charges found with null votehead_id.');
            return 0;
        }

        $this->info("Found {$charges->count()} charges with null votehead_id.");

        // Get votehead to assign
        if ($voteheadId) {
            $votehead = Votehead::find($voteheadId);
            if (!$votehead) {
                $this->error("Votehead with ID {$voteheadId} not found.");
                return 1;
            }
        } else {
            // Try to find tuition votehead first
            $votehead = Votehead::where('is_active', true)
                ->where(function($q) {
                    $q->where('name', 'like', '%tuition%')
                      ->orWhere('name', 'like', '%Tuition%');
                })
                ->where('is_mandatory', true)
                ->first();

            // If no tuition, find first mandatory votehead
            if (!$votehead) {
                $votehead = Votehead::where('is_mandatory', true)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->first();
            }

            // Fallback to any active votehead
            if (!$votehead) {
                $votehead = Votehead::where('is_active', true)->first();
            }

            if (!$votehead) {
                $this->error('No votehead found to assign. Please create a votehead first.');
                return 1;
            }
        }

        $this->info("Will assign votehead: {$votehead->name} (ID: {$votehead->id})");

        // Group by structure
        $structures = $charges->groupBy('fee_structure_id');

        foreach ($structures as $structId => $structCharges) {
            $structure = FeeStructure::find($structId);
            $structName = $structure ? $structure->name : "ID {$structId}";
            
            $this->line("\nFee Structure: {$structName} (ID: {$structId})");
            $this->line("  Charges to fix: {$structCharges->count()}");

            foreach ($structCharges as $charge) {
                $this->line("    - Charge ID {$charge->id}: Term {$charge->term}, Amount {$charge->amount}");
                
                if (!$dryRun) {
                    $charge->votehead_id = $votehead->id;
                    $charge->save();
                }
            }
        }

        if ($dryRun) {
            $this->warn("\n[DRY RUN] No changes were made. Run without --dry-run to apply changes.");
        } else {
            $this->info("\n✓ Successfully assigned votehead to {$charges->count()} charges.");
            $this->warn("\n⚠️  Please review the fee structure and assign correct voteheads if needed.");
            $this->info("   You can edit the fee structure at: /finance/fee-structures/manage?classroom_id={$structureId}");
        }

        return 0;
    }
}


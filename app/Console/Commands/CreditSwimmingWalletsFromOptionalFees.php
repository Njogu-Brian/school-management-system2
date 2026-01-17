<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\OptionalFee;
use App\Models\InvoiceItem;
use App\Models\Votehead;
use App\Services\SwimmingWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditSwimmingWalletsFromOptionalFees extends Command
{
    protected $signature = 'swimming:credit-wallets-from-optional-fees 
                            {--student-id= : Process specific student only}
                            {--dry-run : Show what would be done without making changes}';
    
    protected $description = 'Credit swimming wallets for students who have paid their swimming optional fees';

    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        parent::__construct();
        $this->walletService = $walletService;
    }

    public function handle()
    {
        $this->info('Checking for swimming optional fees that need wallet credits...');
        
        // Find swimming votehead
        $swimmingVotehead = Votehead::where(function($q) {
            $q->where('name', 'like', '%swimming%')
              ->orWhere('code', 'like', '%SWIM%');
        })->where('is_mandatory', false)->first();

        if (!$swimmingVotehead) {
            $this->error('Swimming votehead not found. Please ensure a swimming optional fee votehead exists.');
            return 1;
        }

        $this->info("Found swimming votehead: {$swimmingVotehead->name} (ID: {$swimmingVotehead->id})");

        // Get all swimming optional fees
        $query = OptionalFee::where('votehead_id', $swimmingVotehead->id)
            ->where('status', 'billed');

        if ($this->option('student-id')) {
            $query->where('student_id', $this->option('student-id'));
        }

        $optionalFees = $query->with(['student', 'votehead'])->get();

        if ($optionalFees->isEmpty()) {
            $this->info('No swimming optional fees found.');
            return 0;
        }

        $this->info("Found {$optionalFees->count()} swimming optional fees to check.");

        $credited = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($optionalFees as $optionalFee) {
            try {
                $student = $optionalFee->student;
                if (!$student) {
                    $this->warn("Optional fee #{$optionalFee->id} has no student. Skipping.");
                    $skipped++;
                    continue;
                }

                // Check if invoice item for this optional fee is fully paid
                $invoiceItem = InvoiceItem::whereHas('invoice', function($q) use ($student, $optionalFee) {
                    $q->where('student_id', $student->id)
                      ->where('year', $optionalFee->year)
                      ->where('term', $optionalFee->term);
                })
                ->where('votehead_id', $optionalFee->votehead_id)
                ->where('status', 'active')
                ->first();

                if (!$invoiceItem) {
                    $this->warn("Student {$student->admission_number}: No invoice item found for optional fee. Skipping.");
                    $skipped++;
                    continue;
                }

                // Check if invoice item is fully paid
                $balance = $invoiceItem->getBalance();
                if ($balance > 0.01) {
                    $this->info("Student {$student->admission_number}: Invoice item not fully paid (Balance: {$balance}). Skipping.");
                    $skipped++;
                    continue;
                }

                // Check if wallet was already credited for this optional fee
                $ledgerExists = \App\Models\SwimmingLedger::where('student_id', $student->id)
                    ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                    ->where('source_id', $optionalFee->id)
                    ->exists();

                if ($ledgerExists) {
                    $this->info("Student {$student->admission_number}: Wallet already credited for this optional fee. Skipping.");
                    $skipped++;
                    continue;
                }

                // Credit wallet
                if ($this->option('dry-run')) {
                    $this->info("Would credit wallet for {$student->full_name} ({$student->admission_number}): Ksh {$optionalFee->amount}");
                    $credited++;
                } else {
                    $this->walletService->creditFromOptionalFee(
                        $student,
                        $optionalFee,
                        (float) $optionalFee->amount,
                        "Swimming termly fee payment for Term {$optionalFee->term} (backfilled)"
                    );
                    
                    $this->info("✓ Credited wallet for {$student->full_name} ({$student->admission_number}): Ksh {$optionalFee->amount}");
                    $credited++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to credit wallet for optional fee #{$optionalFee->id}: {$e->getMessage()}");
                Log::error('Failed to credit swimming wallet from optional fee', [
                    'optional_fee_id' => $optionalFee->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Credited: {$credited}");
        $this->info("  - Skipped: {$skipped}");
        $this->info("  - Failed: {$failed}");

        if ($this->option('dry-run')) {
            $this->warn("This was a dry run. No changes were made. Run without --dry-run to apply changes.");
        }

        return 0;
    }
}

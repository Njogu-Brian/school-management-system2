<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Services\BioTime\BioTimeClient;
use App\Services\BioTime\BioTimeEmployeeMapper;
use App\Services\BioTime\BioTimeSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BioTimeSyncCommand extends Command
{
    protected $signature = 'biotime:sync
                            {--pull : Fetch transactions from BioTime (requires BIOTIME_BASE_URL)}
                            {--since= : Start time Y-m-d H:i:s (default: last 2 days)}
                            {--seed-codes : Fill empty biometric_emp_code from staff_id digits}';

    protected $description = 'Map BioTime employee codes and sync gate punches into staff attendance.';

    public function handle(BioTimeSyncService $sync, BioTimeEmployeeMapper $mapper): int
    {
        if ($this->option('seed-codes')) {
            $this->seedCodes();
        }

        if (! $this->option('pull')) {
            $unmatched = $sync->unmatchedEmpCodes();
            $this->info('Ingest is push-based. Office PC should POST to /api/integrations/biotime/punches.');
            $this->info('Unmatched emp_codes: '.$unmatched->count());
            foreach ($unmatched as $row) {
                $this->line("  {$row->emp_code}  punches={$row->punches}  last={$row->last_punch}");
            }

            return self::SUCCESS;
        }

        try {
            $client = BioTimeClient::fromConfig();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $since = $this->option('since') ?: Carbon::now()->subDays(2)->startOfDay()->format('Y-m-d H:i:s');
        $this->info("Pulling BioTime transactions since {$since}");
        try {
            $rows = $client->transactions($since);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $result = $sync->ingest($rows);
        $this->info("Imported {$result['imported']} punches, {$result['unmatched']} unmatched, {$result['days']} day rows updated.");
        $mapper->refresh();

        return self::SUCCESS;
    }

    private function seedCodes(): void
    {
        $filled = 0;
        $skipped = 0;
        Staff::query()->orderBy('id')->each(function (Staff $staff) use (&$filled, &$skipped) {
            if (filled($staff->biometric_emp_code)) {
                $skipped++;

                return;
            }
            $code = BioTimeEmployeeMapper::suggestedEmpCode($staff->staff_id);
            if (! $code) {
                $this->warn("No numeric PIN for staff #{$staff->id} {$staff->full_name} ({$staff->staff_id})");

                return;
            }
            if (Staff::where('biometric_emp_code', $code)->where('id', '!=', $staff->id)->exists()) {
                $this->warn("PIN {$code} already used; skip {$staff->staff_id}");

                return;
            }
            $staff->biometric_emp_code = $code;
            $staff->save();
            $filled++;
        });
        $this->info("Seeded {$filled} biometric PINs ({$skipped} already set).");
    }
}

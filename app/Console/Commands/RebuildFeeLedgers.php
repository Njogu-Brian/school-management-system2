<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentFeeLedgerService;
use Illuminate\Console\Command;

class RebuildFeeLedgers extends Command
{
    protected $signature = 'finance:rebuild-fee-ledgers
                            {--student= : Student id or admission number}
                            {--dry-run : Show how many students would be rebuilt without writing}';

    protected $description = 'Rebuild invoice paid/balance and freeze receipt balances as at each payment';

    public function handle(StudentFeeLedgerService $ledger): int
    {
        $query = Student::query()->withoutGlobalScopes();
        $filter = $this->option('student');
        if ($filter) {
            $query->where(function ($q) use ($filter) {
                if (is_numeric($filter)) {
                    $q->where('id', $filter);
                }
                $q->orWhere('admission_number', $filter);
            });
        }

        $ids = $query->orderBy('id')->pluck('id');
        if ($ids->isEmpty()) {
            $this->warn('No students found.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Would rebuild fee ledgers for '.$ids->count().' student(s).');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($ids->count());
        $bar->start();
        foreach ($ids as $id) {
            $ledger->forgetCache((int) $id);
            $ledger->syncStudent((int) $id);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Rebuilt fee ledgers and receipt balance snapshots for '.$ids->count().' student(s).');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\AcademicCalendarService;
use Illuminate\Console\Command;

class SyncCurrentAcademicTerm extends Command
{
    protected $signature = 'academic:sync-current-term';

    protected $description = 'Set the current term to the in-session term, or the next upcoming term during a holiday break.';

    public function handle(AcademicCalendarService $calendar): int
    {
        AcademicCalendarService::flush();

        $term = $calendar->currentTerm(null, true);
        $inSession = $calendar->isSchoolInSession();

        if (! $term) {
            $this->error('No term could be resolved.');

            return self::FAILURE;
        }

        $this->info("Current term: {$term->name} (id {$term->id})");
        $this->info('School in session: '.($inSession ? 'yes' : 'no (holiday / out of session)'));
        if ($term->opening_date) {
            $this->line('Opens: '.$term->opening_date->toDateString());
        }
        if ($term->closing_date) {
            $this->line('Closes: '.$term->closing_date->toDateString());
        }

        return self::SUCCESS;
    }
}

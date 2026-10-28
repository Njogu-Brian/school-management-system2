<?php

namespace App\Console\Commands;

use App\Services\InvoicePublishService;
use Illuminate\Console\Command;

class PublishInvoicesCommand extends Command
{
    protected $signature = 'finance:publish-invoices
                            {--year= : Academic year (e.g. 2026)}
                            {--term= : Term number 1-3}
                            {--class= : Classroom ID}
                            {--stream= : Stream ID}
                            {--student= : Student ID}
                            {--channels=sms,whatsapp : Comma-separated channels (sms,whatsapp). In-app is always sent.}
                            {--dry-run : Preview counts without publishing or sending}
                            {--no-notify : Mark published without SMS/WhatsApp/in-app}';

    protected $description = 'Publish draft fee invoices (created by Post Pending Fees) for the school, a class, or a student, and notify parents';

    public function handle(InvoicePublishService $service): int
    {
        $filters = array_filter([
            'year' => $this->option('year') ? (int) $this->option('year') : null,
            'term' => $this->option('term') ? (int) $this->option('term') : null,
            'class_id' => $this->option('class') ? (int) $this->option('class') : null,
            'stream_id' => $this->option('stream') ? (int) $this->option('stream') : null,
            'student_id' => $this->option('student') ? (int) $this->option('student') : null,
        ], fn ($v) => $v !== null);

        $channels = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->option('channels'))
        )));

        $notify = ! $this->option('no-notify');
        $dryRun = (bool) $this->option('dry-run');

        $pending = $service->queryUnpublished($filters)->count();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Found {$pending} unpublished invoice(s) matching filters.");

        if ($pending === 0) {
            return self::SUCCESS;
        }

        if (! $dryRun && $this->input->isInteractive() && ! $this->confirm("Publish {$pending} invoice(s) and notify parents?", true)) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $result = $service->publishByFilters($filters, $channels, $notify, $dryRun);

        $this->info(sprintf(
            'Published: %d | Notified: %d | Failed: %d | Skipped: %d%s',
            $result['published'],
            $result['notified'],
            $result['failed'],
            $result['skipped'],
            $dryRun ? ' (dry-run)' : ''
        ));

        return self::SUCCESS;
    }
}

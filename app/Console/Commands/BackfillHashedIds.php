<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\FeeReminder;
use App\Models\FeePaymentPlan;

class BackfillHashedIds extends Command
{
    protected $signature = 'finance:backfill-hashed-ids';
    protected $description = 'Generate hashed IDs for existing finance records';

    public function handle()
    {
        $this->info('Backfilling hashed IDs for finance records...');

        // Backfill Payments
        $payments = Payment::whereNull('hashed_id')->get();
        $this->info("Found {$payments->count()} payments without hashed_id");
        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();
        foreach ($payments as $payment) {
            $payment->hashed_id = Payment::generateHashedId();
            $payment->save();
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // Backfill Invoices
        $invoices = Invoice::whereNull('hashed_id')->get();
        $this->info("Found {$invoices->count()} invoices without hashed_id");
        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();
        foreach ($invoices as $invoice) {
            $invoice->hashed_id = Invoice::generateHashedId();
            $invoice->save();
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // Backfill Credit Notes
        if (class_exists(CreditNote::class)) {
            $creditNotes = CreditNote::whereNull('hashed_id')->get();
            $this->info("Found {$creditNotes->count()} credit notes without hashed_id");
            $bar = $this->output->createProgressBar($creditNotes->count());
            $bar->start();
            foreach ($creditNotes as $note) {
                $note->hashed_id = CreditNote::generateHashedId();
                $note->save();
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // Backfill Debit Notes
        if (class_exists(DebitNote::class)) {
            $debitNotes = DebitNote::whereNull('hashed_id')->get();
            $this->info("Found {$debitNotes->count()} debit notes without hashed_id");
            $bar = $this->output->createProgressBar($debitNotes->count());
            $bar->start();
            foreach ($debitNotes as $note) {
                $note->hashed_id = DebitNote::generateHashedId();
                $note->save();
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // Backfill Fee Reminders
        if (class_exists(FeeReminder::class)) {
            $reminders = FeeReminder::whereNull('hashed_id')->get();
            $this->info("Found {$reminders->count()} fee reminders without hashed_id");
            $bar = $this->output->createProgressBar($reminders->count());
            $bar->start();
            foreach ($reminders as $reminder) {
                $reminder->hashed_id = FeeReminder::generateHashedId();
                $reminder->save();
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // Backfill Payment Plans
        if (class_exists(FeePaymentPlan::class)) {
            $plans = FeePaymentPlan::whereNull('hashed_id')->get();
            $this->info("Found {$plans->count()} payment plans without hashed_id");
            $bar = $this->output->createProgressBar($plans->count());
            $bar->start();
            foreach ($plans as $plan) {
                $plan->hashed_id = FeePaymentPlan::generateHashedId();
                $plan->save();
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // Backfill public_token length for payments (regenerate to 10 chars)
        $longTokens = Payment::whereNotNull('public_token')
            ->whereRaw('LENGTH(public_token) > 10')
            ->get();
        $this->info("Found {$longTokens->count()} payments with tokens longer than 10 characters");
        $bar = $this->output->createProgressBar($longTokens->count());
        $bar->start();
        foreach ($longTokens as $payment) {
            $payment->public_token = Payment::generatePublicToken();
            $payment->save();
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Done! All finance records now have hashed IDs.');
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;

class BackfillPaymentTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:backfill-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate public tokens for existing payments that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payments = Payment::whereNull('public_token')->get();
        
        if ($payments->isEmpty()) {
            $this->info('All payments already have public tokens.');
            return 0;
        }

        $this->info("Generating tokens for {$payments->count()} payments...");

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        foreach ($payments as $payment) {
            $payment->public_token = Payment::generatePublicToken();
            $payment->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! All payments now have public tokens.');

        return 0;
    }
}

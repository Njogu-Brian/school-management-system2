<?php

namespace Database\Seeders;

use App\Services\Finance\InvoicePaymentLinker;
use Illuminate\Database\Seeder;

/**
 * Links existing M-Pesa "Pay Bill to 4084687 - AZANET" payments (account 1903)
 * to the seeded Azanet internet invoices so the cost isn't double-counted.
 *
 * The matched payment line is pointed at the invoice expense (expense_id), which
 * makes the submit flow skip it — the invoice stays the single expense, and the
 * payment is recorded as its settlement.
 *
 * Safe to re-run: only unlinked payments and unlinked invoices are matched.
 *
 * Run with:  php artisan db:seed --class=LinkAzanetPaymentsSeeder
 */
class LinkAzanetPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $stats = app(InvoicePaymentLinker::class)->linkAzanet();

        $this->command?->info(sprintf(
            'Azanet payments linked: %d of %d payment line(s) matched to invoices (%d invoices in system).',
            $stats['linked'],
            $stats['payments'],
            $stats['invoices_available'],
        ));
    }
}

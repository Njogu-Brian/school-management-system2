<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Records the Azanet internet invoices (parsed from the e-mailed PDFs by
 * scripts/parse_azanet_emails.py) as submitted expenses under the "Internet"
 * category, vendor "Azanet Solutions Ltd".
 *
 * One expense per INVOICE (the monthly bill). Payment receipts are NOT recorded
 * as separate expenses — they only settle the invoices, so recording them too
 * would double-count.
 *
 * Created as SUBMITTED so you review and approve (which posts to the GL),
 * exactly like statement-analyzer expenses. Idempotent: an invoice already
 * recorded is skipped.
 *
 * Run with:  php artisan db:seed --class=AzanetInternetBillSeeder
 */
class AzanetInternetBillSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/azanet_bills.json');
        if (! is_file($path)) {
            $this->command?->error("Data file not found: {$path}");
            return;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        $invoices = $payload['invoices'] ?? [];
        if (! $invoices) {
            $this->command?->error('No invoices found in data file.');
            return;
        }

        $userId = User::query()->orderBy('id')->value('id');
        if (! $userId) {
            $this->command?->error('No users found to attribute expenses to.');
            return;
        }

        $vendor = Vendor::firstOrCreateByName($payload['vendor'] ?? 'Azanet Solutions Ltd');
        $category = ExpenseCategory::where('code', 'INTERNET')->first()
            ?? ExpenseCategory::where('code', 'COMMUNICATION')->first();
        if (! $category) {
            $this->command?->warn('No INTERNET/COMMUNICATION category found — expenses will be uncategorised.');
        }

        $created = 0;
        $skipped = 0;

        foreach ($invoices as $inv) {
            $number = trim((string) ($inv['number'] ?? ''));
            $amount = round((float) ($inv['amount'] ?? 0), 2);
            if ($number === '' || $amount <= 0) {
                continue;
            }

            $exists = Expense::where('source_type', 'azanet_invoice')
                ->where('notes', 'like', "%{$number}%")
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $date = $this->parseDate($inv['date'] ?? null);
            $plan = trim((string) ($inv['plan'] ?? '')) ?: '45Mbps Home Internet';
            $period = $inv['period'] ?? null;

            $expense = Expense::create([
                'source_type' => 'azanet_invoice',
                'vendor_id' => $vendor?->id,
                'requested_by' => $userId,
                'expense_date' => $date,
                'currency' => 'KES',
                'status' => Expense::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'notes' => "Azanet internet invoice {$number}" . ($period ? " ({$period})" : ''),
            ]);

            $expense->lines()->create([
                'category_id' => $category?->id,
                'description' => trim($plan . ($period ? " — {$period}" : '')),
                'qty' => 1,
                'unit_cost' => $amount,
                'tax_rate' => 0,
            ]);

            $expense->recalculateTotals();
            $expense->save();
            $created++;
        }

        $this->command?->info("Azanet internet bills: {$created} expense(s) created, {$skipped} already existed.");
    }

    private function parseDate(?string $d): string
    {
        if ($d && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $d, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return now()->toDateString();
    }
}

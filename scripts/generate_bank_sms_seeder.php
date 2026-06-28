<?php

/**
 * One-off generator: reads the banking SMS XML, extracts the
 * M-Pesa transaction-code -> vendor-name map (reusing the parsing logic in
 * MatchVendorsFromBankSms) and writes a self-contained seeder so the data can
 * be applied on production without uploading the XML.
 *
 * Usage: php scripts/generate_bank_sms_seeder.php <sms.xml>
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Console\Commands\MatchVendorsFromBankSms;

$xml = $argv[1] ?? null;
if (! $xml || ! is_file($xml)) {
    fwrite(STDERR, "Pass a valid SMS xml path\n");
    exit(1);
}

$cmd = new MatchVendorsFromBankSms();
$ref = new ReflectionMethod($cmd, 'buildCodeVendorMap');
$ref->setAccessible(true);
/** @var array<string,string> $map */
$map = $ref->invoke($cmd, $xml);

// Real M-Pesa receipt codes always contain at least one digit; drop all-letter
// tokens that the fallback regex captured from merchant names.
$map = array_filter($map, fn ($code) => preg_match('/\d/', $code), ARRAY_FILTER_USE_KEY);
ksort($map);

$entries = '';
foreach ($map as $code => $vendor) {
    $code = addcslashes((string) $code, "'\\");
    $vendor = addcslashes((string) $vendor, "'\\");
    $entries .= "        '{$code}' => '{$vendor}',\n";
}

$count = count($map);
$generatedAt = date('Y-m-d H:i');

$seeder = <<<PHP
<?php

namespace Database\Seeders;

use App\Models\ExpenseStatementLine;
use Illuminate\Database\Seeder;

/**
 * Prefills vendor / payee names on UNCATEGORISED (pending) statement
 * transactions, matched by the M-Pesa transaction code, using merchant names
 * extracted from banking SMS (Equity, Family, KCB, etc.).
 *
 * It never changes a transaction's review status, category, or approval — it
 * only fills a blank vendor_name so you can then classify and approve.
 *
 * Generated from SMS export on {$generatedAt} ({$count} codes).
 *
 * Run with:  php artisan db:seed --class=BankSmsVendorSeeder
 */
class BankSmsVendorSeeder extends Seeder
{
    public function run(): void
    {
        \$map = \$this->map();

        \$updated = 0;
        ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
            ->where(fn (\$q) => \$q->whereNull('vendor_name')->orWhere('vendor_name', ''))
            ->whereIn('receipt_no', array_keys(\$map))
            ->chunkById(500, function (\$lines) use (\$map, &\$updated) {
                foreach (\$lines as \$line) {
                    \$code = strtoupper(trim((string) \$line->receipt_no));
                    if (isset(\$map[\$code])) {
                        \$line->vendor_name = \$map[\$code];
                        \$line->save();
                        \$updated++;
                    }
                }
            });

        \$this->command?->info("Prefilled vendor names on {\$updated} pending transaction(s). Now classify & approve them in the Statement Analyzer.");
    }

    /**
     * @return array<string, string> M-Pesa code => vendor name
     */
    protected function map(): array
    {
        return [
{$entries}        ];
    }
}

PHP;

$out = __DIR__ . '/../database/seeders/BankSmsVendorSeeder.php';
file_put_contents($out, $seeder);
echo "Wrote {$out} with {$count} code => vendor entries\n";

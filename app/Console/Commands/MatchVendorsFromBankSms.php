<?php

namespace App\Console\Commands;

use App\Models\ExpenseStatementLine;
use Illuminate\Console\Command;
use XMLReader;

class MatchVendorsFromBankSms extends Command
{
    protected $signature = 'expenses:match-vendors-from-sms
        {sms : Path to the SMS Backup & Restore XML file}
        {--dry-run : Show matches without saving}
        {--overwrite : Replace vendor_name even when one is already set}';

    protected $description = 'Read banking SMS (Equity, Family, Co-op, KCB, NCBA, I&M, SBM, Loop, etc.) and prefill the vendor/payee name on uncategorized statement transactions, matched by the M-Pesa transaction code.';

    /** Sender IDs we treat as banks (matched case-insensitively as substrings of the address). */
    protected array $bankSenders = [
        'equity', 'family', 'co-op', 'coop', 'co op', 'kcb', 'ncba', 'i&m', 'i & m',
        'sbm', 'loop', 'stanbic', 'dtb', 'absa', 'gtbank', 'gulf', 'sidian', 'prime',
    ];

    public function handle(): int
    {
        $path = $this->argument('sms');
        if (! is_file($path)) {
            $this->error("SMS file not found: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        $this->info('Reading bank SMS and building transaction-code → vendor map…');
        $map = $this->buildCodeVendorMap($path);
        $this->info('Indexed ' . number_format(count($map)) . ' bank transactions with a vendor name.');

        if ($map === []) {
            $this->warn('No bank transactions with vendor names were found in the SMS file.');

            return self::SUCCESS;
        }

        $query = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING);

        if (! $overwrite) {
            $query->where(fn ($q) => $q->whereNull('vendor_name')->orWhere('vendor_name', ''));
        }

        $matched = 0;
        $unmatched = 0;
        $rows = [];

        $query->orderBy('id')->chunkById(500, function ($lines) use ($map, $dryRun, &$matched, &$unmatched, &$rows) {
            foreach ($lines as $line) {
                $code = strtoupper(trim((string) $line->receipt_no));
                $vendor = $code !== '' ? ($map[$code] ?? null) : null;

                if ($vendor === null) {
                    $unmatched++;

                    continue;
                }

                $matched++;
                if (count($rows) < 40) {
                    $rows[] = [$code, mb_strimwidth((string) $line->recipient_name, 0, 22, '…'), mb_strimwidth($vendor, 0, 34, '…')];
                }

                if (! $dryRun) {
                    $line->vendor_name = $vendor;
                    $line->save();
                }
            }
        });

        if ($rows !== []) {
            $this->table(['M-Pesa code', 'Statement payee', 'Vendor from SMS'], $rows);
            if ($matched > count($rows)) {
                $this->line('… and ' . ($matched - count($rows)) . ' more.');
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Done.');
        $this->table(['Metric', 'Count'], [
            ['Vendors matched & ' . ($dryRun ? 'would set' : 'set'), $matched],
            ['Pending transactions with no SMS match', $unmatched],
        ]);

        if ($dryRun) {
            $this->comment('Run again without --dry-run to apply. Then categorise & approve them in the Statement Analyzer.');
        } else {
            $this->comment('Now open the Statement Analyzer to mark them business/personal, set a category and approve.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> M-Pesa code (uppercase) => vendor name
     */
    protected function buildCodeVendorMap(string $path): array
    {
        $map = [];
        $reader = new XMLReader();
        $reader->open($path);

        while (@$reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'sms') {
                continue;
            }

            $address = (string) $reader->getAttribute('address');
            if (! $this->isBankSender($address)) {
                continue;
            }

            $body = (string) $reader->getAttribute('body');
            if ($body === '') {
                continue;
            }

            $vendor = $this->extractVendor($body);
            if ($vendor === null) {
                continue;
            }

            foreach ($this->extractCodes($body) as $code) {
                if (! isset($map[$code])) {
                    $map[$code] = $vendor;
                }
            }
        }

        $reader->close();

        return $map;
    }

    protected function isBankSender(string $address): bool
    {
        $address = strtolower($address);
        if ($address === 'mpesa' || $address === 'm-pesa') {
            return false;
        }

        foreach ($this->bankSenders as $needle) {
            if (str_contains($address, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function extractVendor(string $body): ?string
    {
        $patterns = [
            // Equity Buy Goods / Pay Bill: "Payment of KES X to <VENDOR> Till No. ..."
            '/payment of\s+(?:kes|kshs?)\.?\s*[\d,]+(?:\.\d+)?\s+to\s+(.+?)\s+till\s+no\./i',
            // Family Bank: "sent to account <VENDOR>(123456) from Account ..."
            '/sent to account\s+(.+?)\s*\(\d+\)/i',
            '/sent to account\s+(.+?)\s+from account/i',
            // Equity deposit: "deposited ... in favour of <VENDOR> Ref. Number ..."
            '/in favou?r of\s+(.+?)\s+ref\.?\s*number/i',
            // Equity send to phone: "successfully sent to <VENDOR> 07xxxxxxxx. Ref."
            '/successfully sent to\s+(.+?)\s+(?:0|254|\+254)[\d*]{6,}/i',
            // Equity payment to phone: "Your payment of X KES to <VENDOR> 07xxxxxxxx was successful"
            '/payment of\s+[\d,]+(?:\.\d+)?\s*kes to\s+(.+?)\s+(?:0|254|\+254)[\d*]{6,}/i',
            // Generic "to <VENDOR> for account ... ref"
            '/\bto\s+(.+?)\s+for account\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $vendor = $this->cleanVendor($m[1]);
                if ($vendor !== null) {
                    return $vendor;
                }
            }
        }

        return null;
    }

    protected function cleanVendor(string $value): ?string
    {
        $value = preg_replace('/\s+/', ' ', trim($value));

        // Cut off any trailing amount fragment, e.g. "ALI of Ksh. 7,100.00" -> "ALI".
        $value = (string) preg_split('/\s+(?:of\s+)?(?:ksh|kshs|kes)\b/i', $value)[0];
        $value = trim($value, " \t\n\r\0\x0B.,-");

        $lower = strtolower($value);
        if ($value === '' || mb_strlen($value) < 2 || mb_strlen($value) > 80) {
            return null;
        }
        // Reject obvious non-merchant captures.
        if (in_array($lower, ['mpesa', 'm-pesa', 'your mpesa', 'account', 'your account'], true)) {
            return null;
        }
        if (str_contains($lower, 'pay bill') || str_contains($lower, 'paybill account')) {
            return null;
        }
        // Must contain at least one letter (avoid pure numbers/refs).
        if (! preg_match('/[A-Za-z]/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return array<int, string> Uppercase M-Pesa-style transaction codes found in the body.
     */
    protected function extractCodes(string $body): array
    {
        $codes = [];

        // Explicit reference labels first (most reliable).
        $refPatterns = [
            '/ref\.?\s*(?:number\s*|no\.?\s*)?([A-Z]{2}[A-Z0-9]{8})\b/i',
            '/mpesa\s+tran\s+ref\s+([A-Z]{2}[A-Z0-9]{8})\b/i',
            '/m-?pesa\s+(?:ref|receipt(?:\s*number)?)\.?\s*([A-Z]{2}[A-Z0-9]{8})\b/i',
            '/\[([A-Z]{2}[A-Z0-9]{8})\]/',
        ];
        foreach ($refPatterns as $pattern) {
            if (preg_match_all($pattern, $body, $m)) {
                foreach ($m[1] as $code) {
                    $codes[strtoupper($code)] = true;
                }
            }
        }

        // Fallback: any standalone M-Pesa-style token (10 chars, starts with 2+ letters).
        if (preg_match_all('/\b[A-Z]{2}[A-Z0-9]{8}\b/', $body, $m)) {
            foreach ($m[0] as $code) {
                $codes[strtoupper($code)] = true;
            }
        }

        return array_keys($codes);
    }
}

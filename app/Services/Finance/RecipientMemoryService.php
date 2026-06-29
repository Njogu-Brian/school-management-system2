<?php

namespace App\Services\Finance;

use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementLine;

/**
 * Learns how recipients/vendors have already been classified (by phone number,
 * by vendor/recipient name, and by the purpose words you type) and re-applies
 * that knowledge to NEW, still-pending statement lines so they are
 * auto-categorised and grouped — ready for you to submit and approve.
 *
 * It is purely additive and safe:
 *   - learns only from lines you've already classified (confirmed / personal),
 *   - only touches PENDING lines, only fills BLANK fields,
 *   - never submits or posts anything; you still review, submit and approve.
 */
class RecipientMemoryService
{
    /** System-generated descriptions that are NOT user intent and must not train the keyword model. */
    private const GENERIC_DESCRIPTIONS = [
        'card purchase', 'loan repayment', 'salary payment', 'account transfer',
        'utilities', 'tithe', 'paybill / account payment', 'airtel money transfer',
        'ecitizen / government payment', 'ecitizen', 'paybill payment',
    ];

    /** Tokens too generic to imply a category. */
    private const STOP_TOKENS = [
        'card', 'purchase', 'account', 'transfer', 'paybill', 'airtel', 'ecitizen',
        'repayment', 'payment', 'the', 'and', 'for', 'from', 'via', 'acc', 'bank',
        'ref', 'paid', 'sent', 'mpesa', 'jan', 'feb', 'mar', 'apr', 'may', 'jun',
        'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
    ];

    private bool $built = false;

    /** @var array<string, array{category_id:?int, business:bool, vendor:?string, description:?string}> */
    private array $phoneIntent = [];

    /** @var array<string, array{category_id:?int, business:bool, vendor:?string, description:?string}> */
    private array $nameIntent = [];

    /** @var array<string, int>  normalised token => category id */
    private array $tokenCategory = [];

    /** @var array<int, bool> */
    private array $validCategories = [];

    public function build(bool $force = false): void
    {
        if ($this->built && ! $force) {
            return;
        }

        $this->phoneIntent = [];
        $this->nameIntent = [];
        $this->tokenCategory = [];

        $this->validCategories = ExpenseCategory::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $phoneVotes = [];
        $nameVotes = [];
        $tokenVotes = [];

        ExpenseStatementLine::query()
            ->whereIn('review_status', [ExpenseStatementLine::REVIEW_CONFIRMED, ExpenseStatementLine::REVIEW_PERSONAL])
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->select(['recipient_phone', 'recipient_name', 'vendor_name', 'expense_category_id', 'expense_description', 'review_status'])
            ->chunk(1000, function ($lines) use (&$phoneVotes, &$nameVotes, &$tokenVotes) {
                foreach ($lines as $line) {
                    $business = $line->review_status !== ExpenseStatementLine::REVIEW_PERSONAL;
                    $categoryId = $line->expense_category_id ? (int) $line->expense_category_id : null;
                    $vendor = $this->blankToNull($line->vendor_name);
                    $description = $this->blankToNull($line->expense_description);
                    $intentKey = ($categoryId ?? '0') . '|' . ($business ? '1' : '0');

                    $phone = $this->phoneKey($line->recipient_phone);
                    if ($phone) {
                        $this->vote($phoneVotes, $phone, $intentKey, $categoryId, $business, $vendor, $description);
                    }

                    $name = $this->nameKey($vendor ?: $line->recipient_name);
                    if ($name) {
                        $this->vote($nameVotes, $name, $intentKey, $categoryId, $business, $vendor, $description);
                    }

                    // keyword model: only real user notes on business lines with a valid category
                    if ($business && $categoryId && isset($this->validCategories[$categoryId])) {
                        foreach ($this->tokens($description) as $tok) {
                            $tokenVotes[$tok][$categoryId] = ($tokenVotes[$tok][$categoryId] ?? 0) + 1;
                        }
                    }
                }
            });

        $this->phoneIntent = $this->resolveVotes($phoneVotes);
        $this->nameIntent = $this->resolveVotes($nameVotes);

        foreach ($tokenVotes as $tok => $cats) {
            $total = array_sum($cats);
            arsort($cats);
            $topCat = (int) array_key_first($cats);
            $topCount = $cats[$topCat];
            if ($total >= 3 && ($topCount / $total) >= 0.8 && isset($this->validCategories[$topCat])) {
                $this->tokenCategory[$tok] = $topCat;
            }
        }

        $this->built = true;
    }

    /**
     * Apply the learned memory to still-pending lines.
     *
     * @param  array<int, string>  $sources  Optional import sources to limit to (e.g. ['bank']).
     * @return array{confirmed:int, personal:int, by_phone:int, by_name:int, by_keyword:int, scanned:int}
     */
    public function applyToPendingLines(?int $importId = null, array $sources = []): array
    {
        $this->build();

        $stats = ['confirmed' => 0, 'personal' => 0, 'by_phone' => 0, 'by_name' => 0, 'by_keyword' => 0, 'scanned' => 0];

        ExpenseStatementLine::query()
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->when($importId, fn ($q) => $q->where('import_id', $importId))
            ->when($sources, fn ($q) => $q->whereHas('import', fn ($i) => $i->whereIn('source', $sources)))
            ->chunkById(500, function ($lines) use (&$stats) {
                foreach ($lines as $line) {
                    $stats['scanned']++;
                    [$source, $intent] = $this->resolve($line);
                    if (! $intent) {
                        continue;
                    }

                    $changed = $this->applyIntent($line, $intent, $source);
                    if ($changed) {
                        if ($line->review_status === ExpenseStatementLine::REVIEW_CONFIRMED) {
                            $stats['confirmed']++;
                        } elseif ($line->review_status === ExpenseStatementLine::REVIEW_PERSONAL) {
                            $stats['personal']++;
                        }
                        $stats['by_' . $source]++;
                    }
                }
            });

        return $stats;
    }

    /**
     * @return array{0: ?string, 1: ?array{category_id:?int, business:bool, vendor:?string, description:?string}}
     */
    private function resolve(ExpenseStatementLine $line): array
    {
        $phone = $this->phoneKey($line->recipient_phone);
        if ($phone && isset($this->phoneIntent[$phone])) {
            return ['phone', $this->phoneIntent[$phone]];
        }

        $name = $this->nameKey($line->vendor_name ?: $line->recipient_name);
        if ($name && isset($this->nameIntent[$name])) {
            return ['name', $this->nameIntent[$name]];
        }

        $votes = [];
        foreach ($this->tokens($line->expense_description) as $tok) {
            if (isset($this->tokenCategory[$tok])) {
                $cat = $this->tokenCategory[$tok];
                $votes[$cat] = ($votes[$cat] ?? 0) + 1;
            }
        }
        if ($votes) {
            arsort($votes);
            $cat = (int) array_key_first($votes);
            return ['keyword', ['category_id' => $cat, 'business' => true, 'vendor' => null, 'description' => null]];
        }

        return [null, null];
    }

    private function applyIntent(ExpenseStatementLine $line, array $intent, string $source): bool
    {
        $dirty = false;

        if ($intent['business']) {
            if (! $intent['category_id'] || ! isset($this->validCategories[$intent['category_id']])) {
                return false; // never confirm a business line without a real category
            }
            if (! $line->expense_category_id) {
                $line->expense_category_id = $intent['category_id'];
                $dirty = true;
            }
            // only set vendor/description from the exact-recipient memory, never from keyword guesses
            if ($source !== 'keyword') {
                if ($this->isBlank($line->vendor_name) && $intent['vendor']) {
                    $line->vendor_name = $intent['vendor'];
                    $dirty = true;
                }
                if ($this->isBlank($line->expense_description) && $intent['description']) {
                    $line->expense_description = $intent['description'];
                    $dirty = true;
                }
            }
            if ($dirty) {
                $line->review_status = ExpenseStatementLine::REVIEW_CONFIRMED;
            }
        } else {
            $line->review_status = ExpenseStatementLine::REVIEW_PERSONAL;
            $dirty = true;
        }

        if ($dirty) {
            $raw = $line->raw_data ?? [];
            $raw['auto_category_source'] = $source;
            $line->raw_data = $raw;
            $line->save();
        }

        return $dirty;
    }

    // ---- helpers ----

    private function vote(array &$bag, string $key, string $intentKey, ?int $categoryId, bool $business, ?string $vendor, ?string $description): void
    {
        if (! isset($bag[$key][$intentKey])) {
            $bag[$key][$intentKey] = ['count' => 0, 'category_id' => $categoryId, 'business' => $business, 'vendor' => $vendor, 'description' => $description];
        }
        $bag[$key][$intentKey]['count']++;
        // keep a vendor/description sample if we don't have one yet
        if ($vendor && ! $bag[$key][$intentKey]['vendor']) {
            $bag[$key][$intentKey]['vendor'] = $vendor;
        }
        if ($description && ! $bag[$key][$intentKey]['description']) {
            $bag[$key][$intentKey]['description'] = $description;
        }
    }

    private function resolveVotes(array $bag): array
    {
        $out = [];
        foreach ($bag as $key => $intents) {
            uasort($intents, fn ($a, $b) => $b['count'] <=> $a['count']);
            $winner = reset($intents);
            $out[$key] = [
                'category_id' => $winner['category_id'],
                'business' => $winner['business'],
                'vendor' => $winner['vendor'],
                'description' => $winner['description'],
            ];
        }

        return $out;
    }

    private function phoneKey(?string $phone): ?string
    {
        if (! $phone || str_contains($phone, '*')) {
            return null; // masked phones (M-Pesa statements) are not reliable keys
        }
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) >= 9 ? substr($digits, -9) : null;
    }

    private function nameKey(?string $name): ?string
    {
        $n = strtolower(trim((string) $name));
        $n = preg_replace('/[^a-z0-9 ]/', ' ', $n);
        $n = trim(preg_replace('/\s+/', ' ', $n));
        return strlen($n) > 2 ? $n : null;
    }

    /** @return array<int, string> */
    private function tokens(?string $text): array
    {
        $t = strtolower(trim((string) $text));
        if ($t === '' || in_array($t, self::GENERIC_DESCRIPTIONS, true)) {
            return [];
        }
        $t = preg_replace('/[^a-z0-9 ]/', ' ', $t);
        $out = [];
        foreach (explode(' ', $t) as $w) {
            if (strlen($w) >= 3 && ! ctype_digit($w) && ! in_array($w, self::STOP_TOKENS, true)) {
                $out[$w] = true;
            }
        }
        return array_keys($out);
    }

    private function isBlank(?string $v): bool
    {
        return $v === null || trim($v) === '';
    }

    private function blankToNull(?string $v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
}

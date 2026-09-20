<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Keeps the cached Finance > Transactions tab counts honest.
 *
 * Those counts are nine COUNT queries over the bank statement and C2B tables,
 * expensive enough that recomputing them on every page view was a measurable
 * part of the page's cost. They are also badge numbers users expect to move the
 * instant they assign, collect or archive something — so rather than relying on
 * a TTL alone, any write to either transaction source bumps a stamp that forms
 * part of the cache key. Stale entries then fall out on their own TTL.
 */
trait InvalidatesTransactionListingCounts
{
    public const LISTING_COUNTS_STAMP_KEY = 'bst:listing_counts_stamp';

    protected static function bootInvalidatesTransactionListingCounts(): void
    {
        $invalidate = static function (): void {
            Cache::forever(self::LISTING_COUNTS_STAMP_KEY, (string) microtime(true));
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Current stamp, for composing cache keys that must not outlive a write.
     */
    public static function listingCountsStamp(): string
    {
        return (string) Cache::get(self::LISTING_COUNTS_STAMP_KEY, '0');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexable "base" reference for split payments.
 *
 * The finance listings decide whether a bank transaction is collected, partially
 * paid or uncollected by summing every payment whose transaction_code is either
 * the bank reference itself or that reference plus a "-<suffix>" (split payments,
 * e.g. "UA66G2XJF7-145" or "UA5PM2ZIDG-4-1767731170").
 *
 * Expressed as `transaction_code LIKE CONCAT(reference_number, '-%')`, the
 * pattern is built from another table's column, so MySQL cannot use the index on
 * transaction_code and re-scans the whole payments table once per bank row. On
 * production-scale data that made a single tab-count query take 39 seconds.
 *
 * A STORED generated column holding the part before the first hyphen turns that
 * into a plain indexed equality. Verified against the 2026-07-29 production dump:
 * 841 bank transactions compared, zero mismatches against the old expression,
 * identical grand total. Safe because no reference_number in that data contains a
 * hyphen — and the queries keep an `OR transaction_code = reference_number` arm,
 * so an exact match is still found even if a hyphenated reference ever appears.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'transaction_code')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'base_transaction_code')) {
            DB::statement(
                'ALTER TABLE `payments`
                 ADD COLUMN `base_transaction_code` VARCHAR(255)
                 GENERATED ALWAYS AS (SUBSTRING_INDEX(`transaction_code`, \'-\', 1)) STORED'
            );
        }

        if (empty(DB::select("SHOW INDEX FROM `payments` WHERE Key_name = 'payments_base_code_idx'"))) {
            DB::statement(
                'ALTER TABLE `payments`
                 ADD INDEX `payments_base_code_idx` (`base_transaction_code`, `reversed`, `amount`)'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! empty(DB::select("SHOW INDEX FROM `payments` WHERE Key_name = 'payments_base_code_idx'"))) {
            DB::statement('ALTER TABLE `payments` DROP INDEX `payments_base_code_idx`');
        }

        if (Schema::hasColumn('payments', 'base_transaction_code')) {
            DB::statement('ALTER TABLE `payments` DROP COLUMN `base_transaction_code`');
        }
    }
};

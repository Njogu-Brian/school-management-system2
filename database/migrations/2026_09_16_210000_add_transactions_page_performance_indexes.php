<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the Finance > Transactions listing.
 *
 * Every view on that page filters on the same low-selectivity boolean trio and
 * then orders by date. MySQL can only use one index per table access, so with
 * only the existing single-column indexes it picks one boolean, then filesorts
 * the remainder. The composite indexes below let it satisfy the filter and the
 * ORDER BY from one index, which is what makes a real SQL LIMIT possible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex(
            'bank_statement_transactions',
            'bst_listing_idx',
            ['is_archived', 'is_duplicate', 'transaction_type', 'transaction_date']
        );

        // Covering index for the per-reference payment sums. Including `amount`
        // lets MySQL answer SUM(amount) from the index without touching rows.
        $this->addIndex(
            'payments',
            'payments_code_reversed_amount_idx',
            ['transaction_code', 'reversed', 'amount']
        );

        $this->addIndex(
            'mpesa_c2b_transactions',
            'c2b_listing_idx',
            ['is_duplicate', 'status', 'trans_time']
        );
    }

    public function down(): void
    {
        $this->dropIndex('bank_statement_transactions', 'bst_listing_idx');
        $this->dropIndex('payments', 'payments_code_reversed_amount_idx');
        $this->dropIndex('mpesa_c2b_transactions', 'c2b_listing_idx');
    }

    /**
     * Create an index only when the table, every column, and no index of that
     * name already exist. Production has drifted from a clean migration run, so
     * these have to be defensive rather than assume schema state.
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $name)) {
            return;
        }

        $cols = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$cols})");
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]));
    }
};

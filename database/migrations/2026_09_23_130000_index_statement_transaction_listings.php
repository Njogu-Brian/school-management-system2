<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            $table->index(['import_id', 'direction', 'group_key'], 'exp_stmt_lines_import_dir_group');
            $table->index(['direction', 'group_key'], 'exp_stmt_lines_dir_group');
        });

        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->index(
                ['is_archived', 'transaction_type', 'transaction_date'],
                'bst_listing_archived_type_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('expense_statement_lines', function (Blueprint $table) {
            $table->dropIndex('exp_stmt_lines_import_dir_group');
            $table->dropIndex('exp_stmt_lines_dir_group');
        });

        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropIndex('bst_listing_archived_type_date');
        });
    }
};

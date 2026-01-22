<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'version')) {
                $table->unsignedInteger('version')->default(0)->after('updated_at');
            }
        });

        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_transactions', 'version')) {
                $table->unsignedInteger('version')->default(0)->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'version')) {
                $table->dropColumn('version');
            }
        });

        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_transactions', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};

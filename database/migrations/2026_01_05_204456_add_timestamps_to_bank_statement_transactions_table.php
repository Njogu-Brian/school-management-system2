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
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            // Add timestamps if they don't exist
            if (!Schema::hasColumn('bank_statement_transactions', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('bank_statement_transactions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};

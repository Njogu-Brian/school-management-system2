<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Links duplicate bank transactions directly to the original bank transaction (not just payment).
     */
    public function up(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->foreignId('duplicate_of_transaction_id')->nullable()->after('duplicate_of_payment_id')
                ->constrained('bank_statement_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_transaction_id']);
        });
    }
};

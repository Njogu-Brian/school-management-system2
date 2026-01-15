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
            $table->boolean('is_swimming_transaction')->default(false)->after('is_shared')->comment('Marked as swimming payment, excluded from fee allocation');
            $table->decimal('swimming_allocated_amount', 10, 2)->default(0)->after('is_swimming_transaction')->comment('Total amount allocated to swimming');
            $table->index('is_swimming_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropIndex(['is_swimming_transaction']);
            $table->dropColumn(['is_swimming_transaction', 'swimming_allocated_amount']);
        });
    }
};

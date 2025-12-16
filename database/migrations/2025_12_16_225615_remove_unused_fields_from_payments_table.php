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
            // Remove bank_account_id (payment methods already have it)
            if (Schema::hasColumn('payments', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
            
            // Remove reference (transaction_code is used instead)
            if (Schema::hasColumn('payments', 'reference')) {
                $table->dropColumn('reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Restore bank_account_id
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            
            // Restore reference
            $table->string('reference')->nullable()->after('narration');
        });
    }
};

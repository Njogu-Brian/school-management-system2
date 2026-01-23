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
        if (!Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
                $table->boolean('is_swimming_transaction')->default(false)->after('is_duplicate')->comment('Marked as swimming payment, excluded from fee allocation');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
                $table->dropColumn('is_swimming_transaction');
            });
        }
    }
};

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
            $table->boolean('is_duplicate')->default(false)->after('payment_created');
            $table->foreignId('duplicate_of_payment_id')->nullable()->after('is_duplicate')->constrained('payments')->onDelete('set null');
            $table->boolean('is_archived')->default(false)->after('is_duplicate');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->onDelete('set null');
            $table->string('payer_name')->nullable()->after('phone_number');
            
            $table->index('is_duplicate');
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_payment_id']);
            $table->dropForeign(['archived_by']);
            $table->dropColumn([
                'is_duplicate',
                'duplicate_of_payment_id',
                'is_archived',
                'archived_at',
                'archived_by',
                'payer_name'
            ]);
        });
    }
};

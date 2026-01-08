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
            // Payment channel tracking
            $table->string('payment_channel')->nullable()->after('payment_method_id')
                ->comment('Source: stk_push, payment_link, paybill_manual, admin_entry, mobile_app, online_portal');
            $table->string('mpesa_receipt_number')->nullable()->after('payment_channel');
            $table->string('mpesa_phone_number')->nullable()->after('mpesa_receipt_number');
            $table->foreignId('payment_link_id')->nullable()->after('invoice_id')
                ->constrained('payment_links')->onDelete('set null');
            $table->foreignId('payment_transaction_id')->nullable()->after('payment_link_id')
                ->constrained('payment_transactions')->onDelete('set null');
            
            // Add indexes
            $table->index('payment_channel');
            $table->index('mpesa_receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_channel',
                'mpesa_receipt_number',
                'mpesa_phone_number',
                'payment_link_id',
                'payment_transaction_id'
            ]);
        });
    }
};


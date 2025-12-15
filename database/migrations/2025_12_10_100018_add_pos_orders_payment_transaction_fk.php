<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds the foreign key constraint from pos_orders to payment_transactions
     * after the payment_transactions table has been created.
     */
    public function up(): void
    {
        // Only add the foreign key if both tables exist
        if (Schema::hasTable('pos_orders') && Schema::hasTable('payment_transactions')) {
            // Check if foreign key already exists
            $hasFk = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'pos_orders' 
                AND COLUMN_NAME = 'payment_transaction_id' 
                AND REFERENCED_TABLE_NAME = 'payment_transactions'
            ");
            
            if (empty($hasFk)) {
                Schema::table('pos_orders', function (Blueprint $table) {
                    $table->foreign('payment_transaction_id')
                        ->references('id')
                        ->on('payment_transactions')
                        ->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropForeign(['payment_transaction_id']);
            });
        }
    }
};


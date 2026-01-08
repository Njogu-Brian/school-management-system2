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
        Schema::table('payment_transactions', function (Blueprint $table) {
            // M-PESA specific fields
            $table->string('mpesa_receipt_number')->nullable()->after('transaction_id');
            $table->string('phone_number')->nullable()->after('mpesa_receipt_number');
            $table->string('account_reference')->nullable()->after('phone_number');
            $table->timestamp('mpesa_transaction_date')->nullable()->after('paid_at');
            $table->foreignId('initiated_by')->nullable()->after('student_id')
                ->constrained('users')->onDelete('set null')
                ->comment('Admin who initiated payment for admin-prompted STK push');
            $table->text('admin_notes')->nullable()->after('failure_reason');
            $table->foreignId('payment_link_id')->nullable()->after('invoice_id')
                ->constrained('payment_links')->onDelete('set null');
            
            // Add indexes
            $table->index('mpesa_receipt_number');
            $table->index('phone_number');
            $table->index('account_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'mpesa_receipt_number',
                'phone_number',
                'account_reference',
                'mpesa_transaction_date',
                'initiated_by',
                'admin_notes',
                'payment_link_id'
            ]);
        });
    }
};


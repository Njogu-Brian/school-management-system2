<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_reminders', function (Blueprint $table) {
            // Link to payment plan installment instead of just invoice
            $table->foreignId('payment_plan_installment_id')->nullable()->after('invoice_id')->constrained('fee_payment_plan_installments')->onDelete('cascade');
            $table->foreignId('payment_plan_id')->nullable()->after('payment_plan_installment_id')->constrained('fee_payment_plans')->onDelete('cascade');
            
            // Add reminder rule type
            $table->enum('reminder_rule', ['before_due', 'on_due', 'after_overdue'])->default('before_due')->after('days_before_due');
            
            // Add WhatsApp channel support
            DB::statement("ALTER TABLE fee_reminders MODIFY COLUMN channel ENUM('email', 'sms', 'whatsapp', 'both') DEFAULT 'both'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_reminders', function (Blueprint $table) {
            $table->dropForeign(['payment_plan_installment_id']);
            $table->dropForeign(['payment_plan_id']);
            $table->dropColumn(['payment_plan_installment_id', 'payment_plan_id', 'reminder_rule']);
            DB::statement("ALTER TABLE fee_reminders MODIFY COLUMN channel ENUM('email', 'sms', 'both') DEFAULT 'both'");
        });
    }
};

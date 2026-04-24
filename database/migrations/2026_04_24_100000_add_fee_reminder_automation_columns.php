<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_reminders', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('payment_plan_id')->constrained('terms')->nullOnDelete();
            }
            if (!Schema::hasColumn('fee_reminders', 'fee_reminder_type')) {
                $table->string('fee_reminder_type', 32)->default('invoice')->after('term_id');
            }
            if (!Schema::hasColumn('fee_reminders', 'reason_code')) {
                $table->string('reason_code', 64)->nullable()->after('fee_reminder_type');
            }
            if (!Schema::hasColumn('fee_reminders', 'channels')) {
                $table->json('channels')->nullable()->after('channel');
            }
        });

        if (Schema::hasColumn('fee_reminders', 'fee_reminder_type')) {
            DB::table('fee_reminders')
                ->whereNotNull('payment_plan_installment_id')
                ->update(['fee_reminder_type' => 'installment']);
        }
    }

    public function down(): void
    {
        Schema::table('fee_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('fee_reminders', 'channels')) {
                $table->dropColumn('channels');
            }
            if (Schema::hasColumn('fee_reminders', 'reason_code')) {
                $table->dropColumn('reason_code');
            }
            if (Schema::hasColumn('fee_reminders', 'fee_reminder_type')) {
                $table->dropColumn('fee_reminder_type');
            }
            if (Schema::hasColumn('fee_reminders', 'term_id')) {
                $table->dropForeign(['term_id']);
                $table->dropColumn('term_id');
            }
        });
    }
};

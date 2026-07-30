<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduled_fee_communications')) {
            DB::statement("ALTER TABLE scheduled_fee_communications MODIFY COLUMN status ENUM('pending', 'sent', 'cancelled', 'active', 'completed', 'paused') DEFAULT 'pending'");
        }

        if (Schema::hasTable('scheduled_communications')) {
            DB::statement("ALTER TABLE scheduled_communications MODIFY COLUMN status ENUM('pending', 'sent', 'paused', 'cancelled') DEFAULT 'pending'");
        }

        if (Schema::hasTable('fee_reminders')) {
            DB::statement("ALTER TABLE fee_reminders MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'paused') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scheduled_fee_communications')) {
            DB::table('scheduled_fee_communications')->where('status', 'paused')->update(['status' => 'pending']);
            DB::statement("ALTER TABLE scheduled_fee_communications MODIFY COLUMN status ENUM('pending', 'sent', 'cancelled', 'active', 'completed') DEFAULT 'pending'");
        }

        if (Schema::hasTable('scheduled_communications')) {
            DB::table('scheduled_communications')->where('status', 'paused')->update(['status' => 'pending']);
            DB::statement("ALTER TABLE scheduled_communications MODIFY COLUMN status ENUM('pending', 'sent') DEFAULT 'pending'");
        }

        if (Schema::hasTable('fee_reminders')) {
            DB::table('fee_reminders')->where('status', 'paused')->update(['status' => 'pending']);
            DB::statement("ALTER TABLE fee_reminders MODIFY COLUMN status ENUM('pending', 'sent', 'failed') DEFAULT 'pending'");
        }
    }
};

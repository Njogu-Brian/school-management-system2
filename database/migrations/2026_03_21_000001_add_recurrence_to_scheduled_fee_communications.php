<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_fee_communications', function (Blueprint $table) {
            $table->string('recurrence_type', 20)->default('once')->after('send_at');
            $table->json('recurrence_times')->nullable()->after('recurrence_type');
            $table->json('recurrence_week_days')->nullable()->after('recurrence_times');
            $table->timestamp('recurrence_start_at')->nullable()->after('recurrence_week_days');
            $table->timestamp('recurrence_end_at')->nullable()->after('recurrence_start_at');
            $table->timestamp('recurrence_next_at')->nullable()->after('recurrence_end_at');
        });

        // Extend status enum to include 'active' and 'completed' for recurring (MySQL)
        if (\DB::getDriverName() === 'mysql') {
            \DB::statement("ALTER TABLE scheduled_fee_communications MODIFY COLUMN status ENUM('pending', 'sent', 'cancelled', 'active', 'completed') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('scheduled_fee_communications', function (Blueprint $table) {
            $table->dropColumn([
                'recurrence_type',
                'recurrence_times',
                'recurrence_week_days',
                'recurrence_start_at',
                'recurrence_end_at',
                'recurrence_next_at',
            ]);
        });

        if (\DB::getDriverName() === 'mysql') {
            \DB::statement("ALTER TABLE scheduled_fee_communications MODIFY COLUMN status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending'");
        }
    }
};

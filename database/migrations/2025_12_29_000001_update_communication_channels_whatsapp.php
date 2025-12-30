<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enums to support WhatsApp by converting to strings to avoid future enum churn.
        Schema::table('communication_logs', function (Blueprint $table) {
            if (Schema::hasColumn('communication_logs', 'channel')) {
                $table->string('channel', 30)->default('email')->change();
            }
            if (Schema::hasColumn('communication_logs', 'type')) {
                $table->string('type', 30)->default('email')->change();
            }
        });

        Schema::table('communication_templates', function (Blueprint $table) {
            if (Schema::hasColumn('communication_templates', 'type')) {
                $table->string('type', 30)->default('email')->change();
            }
        });
    }

    public function down(): void
    {
        // No-op rollback to avoid losing data; enums would need explicit casts.
    }
};



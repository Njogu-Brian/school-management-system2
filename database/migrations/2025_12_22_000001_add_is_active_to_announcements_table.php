<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('announcements', 'is_active')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('content');
            });

            // Backfill based on existing active column when present
            try {
                DB::statement('UPDATE announcements SET is_active = IFNULL(active, 1)');
            } catch (\Throwable $e) {
                // ignore if active column missing
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('announcements', 'is_active')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};


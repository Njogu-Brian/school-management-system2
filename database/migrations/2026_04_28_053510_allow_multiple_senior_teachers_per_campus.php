<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campus_senior_teachers')) {
            return;
        }

        Schema::table('campus_senior_teachers', function (Blueprint $table) {
            // Original migration makes campus unique; drop it so multiple senior teachers can share a campus
            // while classroom-level assignments define exact supervision scope.
            try {
                $table->dropUnique(['campus']);
            } catch (\Throwable $e) {
                // ignore if index name differs or already dropped
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('campus_senior_teachers')) {
            return;
        }

        Schema::table('campus_senior_teachers', function (Blueprint $table) {
            // Re-add campus uniqueness (best-effort).
            try {
                $table->unique('campus');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};


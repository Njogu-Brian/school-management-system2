<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check if the unique index already exists without using Doctrine
        $exists = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'invoices'
              AND INDEX_NAME = 'invoices_student_year_term_unique'
        ");

        if (!$exists || (int)$exists->c === 0) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'year', 'term'],
                    'invoices_student_year_term_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop by explicit index name
            try {
                $table->dropUnique('invoices_student_year_term_unique');
            } catch (\Throwable $e) {
                // ignore if it doesn't exist
            }
        });
    }
};

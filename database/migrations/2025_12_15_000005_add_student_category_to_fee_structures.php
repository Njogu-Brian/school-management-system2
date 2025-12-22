<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Add student_category_id to allow category-specific fee structures
            if (!Schema::hasColumn('fee_structures', 'student_category_id')) {
                $table->foreignId('student_category_id')
                    ->nullable()
                    ->after('stream_id')
                    ->constrained('student_categories')
                    ->onDelete('cascade');
            }
        });

        // Update unique constraint to include category (use raw SQL with guard to avoid FK index issues)
        try {
            DB::statement("ALTER TABLE `fee_structures` DROP INDEX `unique_active_structure`");
        } catch (\Throwable $e) {
            // Index may not exist or cannot be dropped due to FK; continue
        }

        try {
            DB::statement("
                ALTER TABLE `fee_structures`
                ADD UNIQUE `unique_active_structure_with_category`
                (`classroom_id`, `academic_year_id`, `term_id`, `stream_id`, `student_category_id`, `is_active`)
            ");
        } catch (\Throwable $e) {
            // Unique may already exist
        }
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'student_category_id')) {
                $table->dropForeign(['student_category_id']);
                $table->dropColumn('student_category_id');
            }
        });

        try {
            DB::statement("ALTER TABLE `fee_structures` DROP INDEX `unique_active_structure_with_category`");
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement("
                ALTER TABLE `fee_structures`
                ADD UNIQUE `unique_active_structure`
                (`classroom_id`, `academic_year_id`, `term_id`, `stream_id`, `is_active`)
            ");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};


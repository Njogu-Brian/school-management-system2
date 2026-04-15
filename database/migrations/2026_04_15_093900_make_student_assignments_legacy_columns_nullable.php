<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_assignments')) {
            return;
        }

        // The app now uses morning/evening trip & drop-off columns.
        // Keep legacy columns but allow NULL so inserts don't fail.
        // Use raw SQL to avoid requiring doctrine/dbal for column changes.
        try {
            DB::statement('ALTER TABLE `student_assignments` DROP FOREIGN KEY `student_assignments_trip_id_foreign`');
        } catch (\Throwable $e) {
            // ignore (constraint may not exist / may have different name)
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` DROP FOREIGN KEY `student_assignments_drop_off_point_id_foreign`');
        } catch (\Throwable $e) {
            // ignore
        }

        // Make nullable
        try {
            DB::statement('ALTER TABLE `student_assignments` MODIFY `trip_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // ignore (column may be missing in some installs)
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` MODIFY `drop_off_point_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        // Re-add foreign keys with SET NULL
        try {
            DB::statement('ALTER TABLE `student_assignments` ADD CONSTRAINT `student_assignments_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` ADD CONSTRAINT `student_assignments_drop_off_point_id_foreign` FOREIGN KEY (`drop_off_point_id`) REFERENCES `drop_off_points`(`id`) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_assignments')) {
            return;
        }

        // Revert to NOT NULL + CASCADE (may fail if NULLs exist).
        try {
            DB::statement('ALTER TABLE `student_assignments` DROP FOREIGN KEY `student_assignments_trip_id_foreign`');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` DROP FOREIGN KEY `student_assignments_drop_off_point_id_foreign`');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `student_assignments` MODIFY `trip_id` BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` MODIFY `drop_off_point_id` BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `student_assignments` ADD CONSTRAINT `student_assignments_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            DB::statement('ALTER TABLE `student_assignments` ADD CONSTRAINT `student_assignments_drop_off_point_id_foreign` FOREIGN KEY (`drop_off_point_id`) REFERENCES `drop_off_points`(`id`) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
    }
};


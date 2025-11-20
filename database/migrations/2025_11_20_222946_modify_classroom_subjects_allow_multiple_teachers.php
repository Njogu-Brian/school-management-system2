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
        // Use raw SQL to drop the unique index, as MySQL may prevent dropping it via schema builder
        // if it's referenced by foreign keys or other constraints
        DB::statement('ALTER TABLE `classroom_subjects` DROP INDEX `cls_sub_unique`');
        
        // Add a new unique constraint that includes staff_id
        // This allows multiple teachers for the same subject-classroom combination
        // Each teacher gets their own record
        Schema::table('classroom_subjects', function (Blueprint $table) {
            $table->unique(
                ['classroom_id', 'stream_id', 'subject_id', 'staff_id', 'academic_year_id', 'term_id'],
                'cls_sub_staff_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('cls_sub_staff_unique');
        });
        
        // Restore the original constraint using raw SQL
        DB::statement('ALTER TABLE `classroom_subjects` ADD UNIQUE KEY `cls_sub_unique` (`classroom_id`, `stream_id`, `subject_id`, `academic_year_id`, `term_id`)');
    }
};

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
        // First, find and drop any foreign keys that reference the unique index
        // MySQL requires dropping foreign keys before dropping the index they reference
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'classroom_subjects' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            // Check if this foreign key uses the unique index columns
            $fkColumns = DB::select("
                SELECT COLUMN_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'classroom_subjects' 
                AND CONSTRAINT_NAME = ?
                ORDER BY ORDINAL_POSITION
            ", [$fk->CONSTRAINT_NAME]);
            
            $fkColumnNames = array_column($fkColumns, 'COLUMN_NAME');
            $uniqueIndexColumns = ['classroom_id', 'stream_id', 'subject_id', 'academic_year_id', 'term_id'];
            
            // If the foreign key uses the same columns as the unique index, drop it
            if (count(array_intersect($fkColumnNames, $uniqueIndexColumns)) > 0) {
                DB::statement("ALTER TABLE `classroom_subjects` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }
        
        // Now drop the unique index
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

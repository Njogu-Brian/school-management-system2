<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            
            // Update unique constraint to include category
            // Drop any foreign keys that depend on the unique index before dropping it
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'fee_structures' 
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            foreach ($foreignKeys as $fk) {
                // Check if this foreign key uses the unique index columns
                $fkColumns = DB::select("
                    SELECT COLUMN_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'fee_structures' 
                      AND CONSTRAINT_NAME = ?
                ", [$fk->CONSTRAINT_NAME]);
                $uniqueIndexColumns = ['classroom_id', 'academic_year_id', 'term_id', 'stream_id', 'is_active'];
                $fkColumnNames = array_map(function($col){ return $col->COLUMN_NAME; }, $fkColumns);
                if (count(array_intersect($fkColumnNames, $uniqueIndexColumns)) > 0) {
                    DB::statement("ALTER TABLE `fee_structures` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }
            }
            // Now drop the unique index
            try {
                $table->dropUnique('unique_active_structure');
            } catch (\Exception $e) {
                // Constraint may not exist
            }
            
            // Add new unique constraint with category
            $table->unique(
                ['classroom_id', 'academic_year_id', 'term_id', 'stream_id', 'student_category_id', 'is_active'],
                'unique_active_structure_with_category'
            );
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            // Drop unique constraint
            try {
                $table->dropUnique('unique_active_structure_with_category');
            } catch (\Exception $e) {
                // May not exist
            }
            
            // Restore old constraint
            try {
                $table->unique(
                    ['classroom_id', 'academic_year_id', 'term_id', 'stream_id', 'is_active'],
                    'unique_active_structure'
                );
            } catch (\Exception $e) {
                // May fail
            }
            
            if (Schema::hasColumn('fee_structures', 'student_category_id')) {
                $table->dropForeign(['student_category_id']);
                $table->dropColumn('student_category_id');
            }
        });
    }
};


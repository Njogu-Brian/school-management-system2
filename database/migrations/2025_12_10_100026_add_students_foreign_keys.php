<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to students table
     * after student_categories, classrooms, streams tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        // Helper to check if foreign key exists
        $foreignKeyExists = function($table, $column): bool {
            try {
                $result = DB::selectOne("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$table, $column]);
                
                return $result && $result->count > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        Schema::table('students', function (Blueprint $table) use ($foreignKeyExists) {
            // Add FK for category_id
            if (Schema::hasTable('student_categories') && 
                Schema::hasColumn('students', 'category_id') &&
                !$foreignKeyExists('students', 'category_id')) {
                try {
                    $table->foreign('category_id')
                        ->references('id')
                        ->on('student_categories')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }

            // Add FK for classroom_id
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('students', 'classroom_id') &&
                !$foreignKeyExists('students', 'classroom_id')) {
                try {
                    $table->foreign('classroom_id')
                        ->references('id')
                        ->on('classrooms')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }

            // Add FK for stream_id
            if (Schema::hasTable('streams') && 
                Schema::hasColumn('students', 'stream_id') &&
                !$foreignKeyExists('students', 'stream_id')) {
                try {
                    $table->foreign('stream_id')
                        ->references('id')
                        ->on('streams')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }

            // Add FK for sibling_id (self-referencing)
            if (Schema::hasColumn('students', 'sibling_id') &&
                !$foreignKeyExists('students', 'sibling_id')) {
                try {
                    $table->foreign('sibling_id')
                        ->references('id')
                        ->on('students')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $columns = ['category_id', 'classroom_id', 'stream_id', 'sibling_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        try {
                            $table->dropForeign(['students_' . $column . '_foreign']);
                        } catch (\Exception $e) {
                            try {
                                $table->dropForeign([$column]);
                            } catch (\Exception $e2) {
                                // Ignore
                            }
                        }
                    }
                }
            });
        }
    }
};


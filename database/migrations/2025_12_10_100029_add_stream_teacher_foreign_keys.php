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
     * This migration adds foreign key constraints to stream_teacher table
     * after streams, users, and classrooms tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('stream_teacher')) {
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

        Schema::table('stream_teacher', function (Blueprint $table) use ($foreignKeyExists) {
            // Add FK for stream_id
            if (Schema::hasTable('streams') && 
                Schema::hasColumn('stream_teacher', 'stream_id') &&
                !$foreignKeyExists('stream_teacher', 'stream_id')) {
                try {
                    $table->foreign('stream_id')
                        ->references('id')
                        ->on('streams')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }

            // Add FK for teacher_id (references users.id)
            if (Schema::hasTable('users') && 
                Schema::hasColumn('stream_teacher', 'teacher_id') &&
                !$foreignKeyExists('stream_teacher', 'teacher_id')) {
                try {
                    $table->foreign('teacher_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }

            // Add FK for classroom_id (if column exists)
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('stream_teacher', 'classroom_id') &&
                !$foreignKeyExists('stream_teacher', 'classroom_id')) {
                try {
                    $table->foreign('classroom_id')
                        ->references('id')
                        ->on('classrooms')
                        ->onDelete('cascade');
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
        if (Schema::hasTable('stream_teacher')) {
            Schema::table('stream_teacher', function (Blueprint $table) {
                $columns = ['stream_id', 'teacher_id', 'classroom_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('stream_teacher', $column)) {
                        try {
                            $table->dropForeign(['stream_teacher_' . $column . '_foreign']);
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




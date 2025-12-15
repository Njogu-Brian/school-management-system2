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
     * This migration adds foreign key constraints to classroom_teacher table
     * after classrooms and users tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('classroom_teacher')) {
            return;
        }

        Schema::table('classroom_teacher', function (Blueprint $table) {
            // Add FK for classroom_id
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('classroom_teacher', 'classroom_id')) {
                $fkExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'classroom_teacher' 
                    AND COLUMN_NAME = 'classroom_id'
                    AND REFERENCED_TABLE_NAME = 'classrooms'
                ");
                
                if (!isset($fkExists[0]) || $fkExists[0]->count == 0) {
                    try {
                        $table->foreign('classroom_id')
                            ->references('id')
                            ->on('classrooms')
                            ->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Ignore if already exists
                    }
                }
            }

            // Add FK for teacher_id (references users.id)
            if (Schema::hasTable('users') && 
                Schema::hasColumn('classroom_teacher', 'teacher_id')) {
                // Check if foreign key already exists
                $fkExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'classroom_teacher' 
                    AND COLUMN_NAME = 'teacher_id'
                    AND REFERENCED_TABLE_NAME = 'users'
                ");
                
                if (!isset($fkExists[0]) || $fkExists[0]->count == 0) {
                    try {
                        $table->foreign('teacher_id')
                            ->references('id')
                            ->on('users')
                            ->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Ignore if already exists
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('classroom_teacher')) {
            Schema::table('classroom_teacher', function (Blueprint $table) {
                $columns = ['classroom_id', 'teacher_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('classroom_teacher', $column)) {
                        try {
                            $table->dropForeign(['classroom_teacher_' . $column . '_foreign']);
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




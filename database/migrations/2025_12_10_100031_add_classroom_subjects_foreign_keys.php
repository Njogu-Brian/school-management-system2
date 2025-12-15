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
     * This migration adds foreign key constraints to classroom_subjects table
     * after all referenced tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('classroom_subjects')) {
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

        Schema::table('classroom_subjects', function (Blueprint $table) use ($foreignKeyExists) {
            // Add foreign key for classroom_id
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('classroom_subjects', 'classroom_id') &&
                !$foreignKeyExists('classroom_subjects', 'classroom_id')) {
                try {
                    $table->foreign('classroom_id')
                        ->references('id')
                        ->on('classrooms')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
            
            // Add foreign key for stream_id
            if (Schema::hasTable('streams') && 
                Schema::hasColumn('classroom_subjects', 'stream_id') &&
                !$foreignKeyExists('classroom_subjects', 'stream_id')) {
                try {
                    $table->foreign('stream_id')
                        ->references('id')
                        ->on('streams')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
            
            // Add foreign key for subject_id
            if (Schema::hasTable('subjects') && 
                Schema::hasColumn('classroom_subjects', 'subject_id') &&
                !$foreignKeyExists('classroom_subjects', 'subject_id')) {
                try {
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('subjects')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
            
            // Add foreign key for staff_id
            if (Schema::hasTable('staff') && 
                Schema::hasColumn('classroom_subjects', 'staff_id') &&
                !$foreignKeyExists('classroom_subjects', 'staff_id')) {
                try {
                    $table->foreign('staff_id')
                        ->references('id')
                        ->on('staff')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
            
            // Add foreign key for academic_year_id
            if (Schema::hasTable('academic_years') && 
                Schema::hasColumn('classroom_subjects', 'academic_year_id') &&
                !$foreignKeyExists('classroom_subjects', 'academic_year_id')) {
                try {
                    $table->foreign('academic_year_id')
                        ->references('id')
                        ->on('academic_years')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if already exists
                }
            }
            
            // Add foreign key for term_id
            if (Schema::hasTable('terms') && 
                Schema::hasColumn('classroom_subjects', 'term_id') &&
                !$foreignKeyExists('classroom_subjects', 'term_id')) {
                try {
                    $table->foreign('term_id')
                        ->references('id')
                        ->on('terms')
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
        if (Schema::hasTable('classroom_subjects')) {
            Schema::table('classroom_subjects', function (Blueprint $table) {
                $foreignKeys = [
                    'classroom_subjects_classroom_id_foreign',
                    'classroom_subjects_stream_id_foreign',
                    'classroom_subjects_subject_id_foreign',
                    'classroom_subjects_staff_id_foreign',
                    'classroom_subjects_academic_year_id_foreign',
                    'classroom_subjects_term_id_foreign'
                ];
                
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign([$fk]);
                    } catch (\Exception $e) {
                        // Try alternative names
                        try {
                            $table->dropForeign([str_replace('classroom_subjects_', '', str_replace('_foreign', '', $fk))]);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};


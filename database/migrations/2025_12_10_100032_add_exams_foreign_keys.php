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
     * This migration adds foreign key constraints to exams table
     * after all referenced tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            return;
        }

        Schema::table('exams', function (Blueprint $table) {
            $foreignKeys = [
                'academic_year_id' => ['table' => 'academic_years', 'onDelete' => 'cascade'],
                'term_id' => ['table' => 'terms', 'onDelete' => 'cascade'],
                'classroom_id' => ['table' => 'classrooms', 'onDelete' => 'cascade'],
                'stream_id' => ['table' => 'streams', 'onDelete' => 'set null', 'nullable' => true],
                'subject_id' => ['table' => 'subjects', 'onDelete' => 'cascade'],
                'created_by' => ['table' => 'staff', 'onDelete' => 'set null', 'nullable' => true],
            ];
            
            foreach ($foreignKeys as $column => $config) {
                if (Schema::hasTable($config['table']) && 
                    Schema::hasColumn('exams', $column)) {
                    // Check if foreign key already exists
                    $fkExists = DB::select("
                        SELECT COUNT(*) as count
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'exams' 
                        AND COLUMN_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$column]);
                    
                    if (!isset($fkExists[0]) || $fkExists[0]->count == 0) {
                        try {
                            $foreignKey = $table->foreign($column)
                                ->references('id')
                                ->on($config['table']);
                            
                            if ($config['onDelete'] === 'cascade') {
                                $foreignKey->onDelete('cascade');
                            } else {
                                $foreignKey->onDelete('set null');
                            }
                        } catch (\Exception $e) {
                            // Ignore if already exists
                        }
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
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                $foreignKeys = [
                    'academic_year_id',
                    'term_id',
                    'classroom_id',
                    'stream_id',
                    'subject_id',
                    'created_by'
                ];
                
                foreach ($foreignKeys as $column) {
                    try {
                        $table->dropForeign(["exams_{$column}_foreign"]);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};


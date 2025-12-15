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
     * This migration adds foreign key constraints to classroom_stream table
     * after both classrooms and streams tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('classroom_stream')) {
            return;
        }

        Schema::table('classroom_stream', function (Blueprint $table) {
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('classroom_stream', 'classroom_id')) {
                $fkExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'classroom_stream' 
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
            
            if (Schema::hasTable('streams') && 
                Schema::hasColumn('classroom_stream', 'stream_id')) {
                $fkExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'classroom_stream' 
                    AND COLUMN_NAME = 'stream_id'
                    AND REFERENCED_TABLE_NAME = 'streams'
                ");
                
                if (!isset($fkExists[0]) || $fkExists[0]->count == 0) {
                    try {
                        $table->foreign('stream_id')
                            ->references('id')
                            ->on('streams')
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
        if (Schema::hasTable('classroom_stream')) {
            Schema::table('classroom_stream', function (Blueprint $table) {
                if (Schema::hasColumn('classroom_stream', 'classroom_id')) {
                    try {
                        $table->dropForeign(['classroom_stream_classroom_id_foreign']);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['classroom_id']);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
                
                if (Schema::hasColumn('classroom_stream', 'stream_id')) {
                    try {
                        $table->dropForeign(['classroom_stream_stream_id_foreign']);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['stream_id']);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};


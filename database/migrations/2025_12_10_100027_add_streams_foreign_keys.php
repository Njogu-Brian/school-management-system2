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
     * This migration adds foreign key constraints to streams table
     * after classrooms table has been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('streams')) {
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

        Schema::table('streams', function (Blueprint $table) use ($foreignKeyExists) {
            if (Schema::hasTable('classrooms') && 
                Schema::hasColumn('streams', 'classroom_id') &&
                !$foreignKeyExists('streams', 'classroom_id')) {
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
        if (Schema::hasTable('streams')) {
            Schema::table('streams', function (Blueprint $table) {
                if (Schema::hasColumn('streams', 'classroom_id')) {
                    try {
                        $table->dropForeign(['streams_classroom_id_foreign']);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['classroom_id']);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};


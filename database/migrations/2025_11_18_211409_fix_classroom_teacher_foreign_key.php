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
        Schema::table('classroom_teacher', function (Blueprint $table) {
            // Drop the old foreign key constraint if it exists (might be named differently)
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'classroom_teacher' 
                AND COLUMN_NAME = 'teacher_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
            }
        });

        Schema::table('classroom_teacher', function (Blueprint $table) {
            // Add the correct foreign key constraint referencing users table
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_teacher', function (Blueprint $table) {
            // Drop the foreign key to users
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'classroom_teacher' 
                AND COLUMN_NAME = 'teacher_id' 
                AND REFERENCED_TABLE_NAME = 'users'
            ");

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
            }
        });
    }
};

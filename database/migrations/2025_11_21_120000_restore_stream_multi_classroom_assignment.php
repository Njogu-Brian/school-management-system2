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
     * This migration restores the ability to assign streams to multiple classrooms
     * while keeping the primary classroom_id relationship.
     */
    public function up(): void
    {
        // Recreate the classroom_stream pivot table for additional classroom assignments
        if (!Schema::hasTable('classroom_stream')) {
            Schema::create('classroom_stream', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('stream_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                
                // Prevent duplicate assignments
                $table->unique(['classroom_id', 'stream_id'], 'classroom_stream_unique');
            });
        }

        // Populate the pivot table with existing primary classroom assignments
        // This ensures streams are available in their primary classroom
        DB::statement('
            INSERT IGNORE INTO classroom_stream (classroom_id, stream_id, created_at, updated_at)
            SELECT classroom_id, id, created_at, updated_at
            FROM streams
            WHERE classroom_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the pivot table
        Schema::dropIfExists('classroom_stream');
    }
};


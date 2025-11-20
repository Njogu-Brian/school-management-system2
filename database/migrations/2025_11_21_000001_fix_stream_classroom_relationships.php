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
     * This migration fixes the stream-classroom relationship to ensure:
     * 1. Each stream belongs to only one classroom (removes many-to-many pivot)
     * 2. Stream names are unique per classroom
     * 3. Teacher assignments are specific to classroom-stream combinations
     */
    public function up(): void
    {
        // Step 1: Make classroom_id in stream_teacher required (not nullable)
        Schema::table('stream_teacher', function (Blueprint $table) {
            // First, remove any null classroom_id entries (they shouldn't exist, but just in case)
            DB::table('stream_teacher')
                ->whereNull('classroom_id')
                ->delete();
            
            // Make classroom_id required
            $table->foreignId('classroom_id')->nullable(false)->change();
        });

        // Step 2: Ensure classroom_id in streams is not nullable
        Schema::table('streams', function (Blueprint $table) {
            // Remove any streams without a classroom_id (they shouldn't exist)
            DB::table('streams')
                ->whereNull('classroom_id')
                ->delete();
            
            // Make classroom_id not nullable
            $table->foreignId('classroom_id')->nullable(false)->change();
        });

        // Step 3: Add unique constraint on (name, classroom_id) to prevent duplicate stream names in same classroom
        Schema::table('streams', function (Blueprint $table) {
            // First, remove any duplicate (name, classroom_id) combinations
            $duplicates = DB::table('streams')
                ->select('name', 'classroom_id', DB::raw('COUNT(*) as count'))
                ->groupBy('name', 'classroom_id')
                ->having('count', '>', 1)
                ->get();
            
            foreach ($duplicates as $dup) {
                // Keep the first one, delete the rest
                $streams = DB::table('streams')
                    ->where('name', $dup->name)
                    ->where('classroom_id', $dup->classroom_id)
                    ->orderBy('id')
                    ->get();
                
                // Delete all except the first
                if ($streams->count() > 1) {
                    $idsToDelete = $streams->skip(1)->pluck('id')->toArray();
                    // Delete related stream_teacher entries first
                    DB::table('stream_teacher')
                        ->whereIn('stream_id', $idsToDelete)
                        ->delete();
                    // Then delete the duplicate streams
                    DB::table('streams')
                        ->whereIn('id', $idsToDelete)
                        ->delete();
                }
            }
            
            // Add unique constraint
            $table->unique(['name', 'classroom_id'], 'streams_name_classroom_unique');
        });

        // Step 4: Drop the classroom_stream pivot table (streams now only belong to one classroom)
        // First, ensure all streams have their classroom_id set correctly
        // (This should already be done, but just in case)
        DB::statement('
            UPDATE streams s
            INNER JOIN classroom_stream cs ON s.id = cs.stream_id
            SET s.classroom_id = cs.classroom_id
            WHERE s.classroom_id IS NULL OR s.classroom_id != cs.classroom_id
        ');
        
        // Now drop the pivot table
        Schema::dropIfExists('classroom_stream');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate classroom_stream pivot table
        Schema::create('classroom_stream', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Populate the pivot table from streams.classroom_id
        DB::statement('
            INSERT INTO classroom_stream (classroom_id, stream_id, created_at, updated_at)
            SELECT classroom_id, id, created_at, updated_at
            FROM streams
            WHERE classroom_id IS NOT NULL
        ');

        // Remove unique constraint
        Schema::table('streams', function (Blueprint $table) {
            $table->dropUnique('streams_name_classroom_unique');
        });

        // Make classroom_id nullable again in streams
        Schema::table('streams', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->change();
        });

        // Make classroom_id nullable again in stream_teacher
        Schema::table('stream_teacher', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->change();
        });
    }
};


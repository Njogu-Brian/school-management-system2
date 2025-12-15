<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_discounts table
     * after classrooms table has been created.
     */
    public function up(): void
    {
        // Only add foreign keys if pos_discounts table exists
        if (!Schema::hasTable('pos_discounts')) {
            return;
        }

        Schema::table('pos_discounts', function (Blueprint $table) {
            // Add foreign key to classrooms if table exists
            if (Schema::hasTable('classrooms') && Schema::hasColumn('pos_discounts', 'classroom_id')) {
                try {
                    $table->foreign('classroom_id')
                        ->references('id')
                        ->on('classrooms')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_discounts')) {
            Schema::table('pos_discounts', function (Blueprint $table) {
                // Drop foreign key if it exists
                if (Schema::hasColumn('pos_discounts', 'classroom_id')) {
                    try {
                        $table->dropForeign(['pos_discounts_classroom_id_foreign']);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['classroom_id']);
                        } catch (\Exception $e2) {
                            // Ignore if doesn't exist
                        }
                    }
                }
            });
        }
    }
};


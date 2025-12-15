<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_public_shop_links table
     * after students and classrooms tables have been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pos_public_shop_links')) {
            return;
        }

        Schema::table('pos_public_shop_links', function (Blueprint $table) {
            // Add foreign key to students if table exists
            if (Schema::hasTable('students') && Schema::hasColumn('pos_public_shop_links', 'student_id')) {
                try {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('students')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to classrooms if table exists
            if (Schema::hasTable('classrooms') && Schema::hasColumn('pos_public_shop_links', 'classroom_id')) {
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
        if (Schema::hasTable('pos_public_shop_links')) {
            Schema::table('pos_public_shop_links', function (Blueprint $table) {
                $columns = ['student_id', 'classroom_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('pos_public_shop_links', $column)) {
                        try {
                            $table->dropForeign(['pos_public_shop_links_' . $column . '_foreign']);
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


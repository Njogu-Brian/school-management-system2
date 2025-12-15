<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_orders table
     * after students, parent_info, users, and payment_transactions tables have been created.
     */
    public function up(): void
    {
        // Only add foreign keys if pos_orders table exists
        if (!Schema::hasTable('pos_orders')) {
            return;
        }

        Schema::table('pos_orders', function (Blueprint $table) {
            // Add foreign key to students if table exists
            if (Schema::hasTable('students') && Schema::hasColumn('pos_orders', 'student_id')) {
                try {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('students')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to parent_info if table exists
            if (Schema::hasTable('parent_info') && Schema::hasColumn('pos_orders', 'parent_id')) {
                try {
                    $table->foreign('parent_id')
                        ->references('id')
                        ->on('parent_info')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to users if table exists
            if (Schema::hasTable('users') && Schema::hasColumn('pos_orders', 'user_id')) {
                try {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
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
        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                // Drop foreign keys if they exist
                $columns = ['student_id', 'parent_id', 'user_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('pos_orders', $column)) {
                        try {
                            $table->dropForeign(['pos_orders_' . $column . '_foreign']);
                        } catch (\Exception $e) {
                            // Try alternative naming
                            try {
                                $table->dropForeign([$column]);
                            } catch (\Exception $e2) {
                                // Ignore if doesn't exist
                            }
                        }
                    }
                }
            });
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table doesn't exist yet (will be created later)
        if (!Schema::hasTable('student_requirements')) {
            return;
        }
        Schema::table('student_requirements', function (Blueprint $table) {
            // Add columns without foreign key constraints (will be added in separate migrations)
            if (!Schema::hasColumn('student_requirements', 'pos_order_id')) {
                $table->unsignedBigInteger('pos_order_id')->nullable()->after('requirement_template_id');
            }
            if (!Schema::hasColumn('student_requirements', 'pos_order_item_id')) {
                $table->unsignedBigInteger('pos_order_item_id')->nullable()->after('pos_order_id');
            }
            if (!Schema::hasColumn('student_requirements', 'purchased_through_pos')) {
                $table->boolean('purchased_through_pos')->default(false)->after('notified_parent');
            }
        });
        
        // Note: Foreign key constraints will be added in separate migrations
    }

    public function down(): void
    {
        if (Schema::hasTable('student_requirements')) {
            Schema::table('student_requirements', function (Blueprint $table) {
                // Drop foreign keys if they exist
                if (Schema::hasColumn('student_requirements', 'pos_order_id')) {
                    try {
                        $table->dropForeign(['pos_order_id']);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
                if (Schema::hasColumn('student_requirements', 'pos_order_item_id')) {
                    try {
                        $table->dropForeign(['pos_order_item_id']);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
                $table->dropColumnIfExists(['pos_order_id', 'pos_order_item_id', 'purchased_through_pos']);
            });
        }
    }
};




<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints for POS-related columns
     * added to requirement_templates and student_requirements tables.
     */
    public function up(): void
    {
        // Add FK for requirement_templates.pos_product_id
        if (Schema::hasTable('requirement_templates') && 
            Schema::hasColumn('requirement_templates', 'pos_product_id') &&
            Schema::hasTable('pos_products')) {
            try {
                Schema::table('requirement_templates', function (Blueprint $table) {
                    $table->foreign('pos_product_id')
                        ->references('id')
                        ->on('pos_products')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        // Add FKs for student_requirements
        if (Schema::hasTable('student_requirements')) {
            Schema::table('student_requirements', function (Blueprint $table) {
                // Add FK for pos_order_id
                if (Schema::hasColumn('student_requirements', 'pos_order_id') &&
                    Schema::hasTable('pos_orders')) {
                    try {
                        $table->foreign('pos_order_id')
                            ->references('id')
                            ->on('pos_orders')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }

                // Add FK for pos_order_item_id
                if (Schema::hasColumn('student_requirements', 'pos_order_item_id') &&
                    Schema::hasTable('pos_order_items')) {
                    try {
                        $table->foreign('pos_order_item_id')
                            ->references('id')
                            ->on('pos_order_items')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('requirement_templates')) {
            Schema::table('requirement_templates', function (Blueprint $table) {
                if (Schema::hasColumn('requirement_templates', 'pos_product_id')) {
                    try {
                        $table->dropForeign(['pos_product_id']);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            });
        }

        if (Schema::hasTable('student_requirements')) {
            Schema::table('student_requirements', function (Blueprint $table) {
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
            });
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_order_items table
     * after pos_orders, pos_products, pos_product_variants, and requirement_templates tables have been created.
     */
    public function up(): void
    {
        // Only add foreign keys if pos_order_items table exists
        if (!Schema::hasTable('pos_order_items')) {
            return;
        }

        Schema::table('pos_order_items', function (Blueprint $table) {
            // Add foreign key to pos_orders if table exists
            if (Schema::hasTable('pos_orders') && Schema::hasColumn('pos_order_items', 'order_id')) {
                try {
                    $table->foreign('order_id')
                        ->references('id')
                        ->on('pos_orders')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to pos_products if table exists
            if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_order_items', 'product_id')) {
                try {
                    $table->foreign('product_id')
                        ->references('id')
                        ->on('pos_products')
                        ->onDelete('restrict');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to pos_product_variants if table exists
            if (Schema::hasTable('pos_product_variants') && Schema::hasColumn('pos_order_items', 'variant_id')) {
                try {
                    $table->foreign('variant_id')
                        ->references('id')
                        ->on('pos_product_variants')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key may already exist, ignore
                }
            }

            // Add foreign key to requirement_templates if table exists
            if (Schema::hasTable('requirement_templates') && Schema::hasColumn('pos_order_items', 'requirement_template_id')) {
                try {
                    $table->foreign('requirement_template_id')
                        ->references('id')
                        ->on('requirement_templates')
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
        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                // Drop foreign keys if they exist
                $columns = ['order_id', 'product_id', 'variant_id', 'requirement_template_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('pos_order_items', $column)) {
                        try {
                            $table->dropForeign(['pos_order_items_' . $column . '_foreign']);
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


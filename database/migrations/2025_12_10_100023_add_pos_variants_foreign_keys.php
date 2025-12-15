<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds foreign key constraints to pos_product_variants table
     * after pos_products table has been created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pos_product_variants')) {
            return;
        }

        Schema::table('pos_product_variants', function (Blueprint $table) {
            if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_product_variants', 'product_id')) {
                try {
                    $table->foreign('product_id')
                        ->references('id')
                        ->on('pos_products')
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
        if (Schema::hasTable('pos_product_variants')) {
            Schema::table('pos_product_variants', function (Blueprint $table) {
                if (Schema::hasColumn('pos_product_variants', 'product_id')) {
                    try {
                        $table->dropForeign(['pos_product_variants_product_id_foreign']);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['product_id']);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};


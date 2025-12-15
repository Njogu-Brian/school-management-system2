<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('pos_products')) {
            return;
        }

        Schema::create('pos_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique()->nullable(); // Stock Keeping Unit
            $table->string('barcode')->nullable();
            $table->enum('type', ['stationery', 'uniform', 'other'])->default('stationery');
            // Add column without foreign key constraint (will be added in separate migration)
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            // Add requirement_type_id column without constraint first
            $table->unsignedBigInteger('requirement_type_id')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->nullable(); // Cost for profit calculation
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('allow_backorders')->default(false); // For uniforms
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('images')->nullable(); // Array of image paths
            $table->json('specifications')->nullable(); // Additional product specs
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index('category');
            $table->index('inventory_item_id');
            $table->index('requirement_type_id');
        });
        
        // Note: Foreign key constraints will be added in separate migrations
        // after inventory_items and requirement_types tables are created
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_products');
    }
};




<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            // Add columns without foreign key constraints (will be added in separate migrations)
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('requirement_template_id')->nullable();
            $table->string('product_name'); // Snapshot at time of order
            $table->string('variant_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('fulfillment_status', ['pending', 'partial', 'fulfilled', 'backordered', 'cancelled'])->default('pending');
            $table->integer('quantity_fulfilled')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'fulfillment_status']);
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('requirement_template_id');
        });
        
        // Note: Foreign key constraints will be added in separate migrations
        // after pos_orders, pos_products, pos_product_variants, and requirement_templates tables are created
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_items');
    }
};




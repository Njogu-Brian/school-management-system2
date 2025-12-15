<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_product_variants', function (Blueprint $table) {
            $table->id();
            // Add column without foreign key constraint (will be added in separate migration)
            $table->unsignedBigInteger('product_id');
            $table->string('name'); // e.g., "Size: Small", "Color: Red"
            $table->string('value'); // e.g., "Small", "Red"
            $table->string('variant_type')->default('size'); // size, color, style, etc.
            $table->decimal('price_adjustment', 10, 2)->default(0); // Additional price for this variant
            $table->integer('stock_quantity')->default(0);
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'variant_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_product_variants');
    }
};




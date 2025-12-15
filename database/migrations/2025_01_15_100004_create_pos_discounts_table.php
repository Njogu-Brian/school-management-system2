<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // Discount code
            $table->enum('type', ['percentage', 'fixed', 'bundle'])->default('percentage');
            $table->decimal('value', 10, 2)->default(0); // Percentage or fixed amount
            $table->enum('scope', ['all', 'category', 'product', 'class_bundle'])->default('all');
            $table->string('category')->nullable(); // If scope is category
            $table->json('product_ids')->nullable(); // If scope is product
            // Add column without foreign key constraint (will be added in separate migration)
            $table->unsignedBigInteger('classroom_id')->nullable(); // For class bundles
            $table->decimal('min_purchase_amount', 10, 2)->nullable();
            $table->integer('min_quantity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->nullable(); // Limit per user
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index(['start_date', 'end_date']);
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_discounts');
    }
};




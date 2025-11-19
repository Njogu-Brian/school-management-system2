<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // stationery, books, supplies, etc.
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->default('piece'); // piece, box, pack, etc.
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_stock_level', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('location')->nullable(); // storage location
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

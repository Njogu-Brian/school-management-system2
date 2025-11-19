<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('requirement_type_id')->nullable()->constrained()->onDelete('set null');
            $table->string('item_name');
            $table->string('brand')->nullable();
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_approved', 10, 2)->nullable();
            $table->decimal('quantity_issued', 10, 2)->default(0);
            $table->string('unit')->default('piece');
            $table->text('purpose')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};

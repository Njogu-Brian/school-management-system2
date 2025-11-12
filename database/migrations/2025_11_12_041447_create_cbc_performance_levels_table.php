<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbc_performance_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique(); // E, M, A, B
            $table->string('name'); // Exceeding, Meeting, Approaching, Below
            $table->decimal('min_percentage', 5, 2)->default(0);
            $table->decimal('max_percentage', 5, 2)->default(100);
            $table->text('description')->nullable();
            $table->string('color_code', 7)->default('#28a745'); // Hex color for display
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbc_performance_levels');
    }
};

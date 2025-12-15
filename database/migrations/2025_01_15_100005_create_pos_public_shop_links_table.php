<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_public_shop_links', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('name')->nullable(); // Friendly name for the link
            // Add columns without foreign key constraints (will be added in separate migrations)
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->enum('access_type', ['student', 'class', 'public'])->default('student');
            $table->boolean('show_requirements_only')->default(false); // Show only class requirements
            $table->boolean('allow_custom_items')->default(true);
            $table->date('expires_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['token', 'is_active']);
            $table->index(['student_id', 'is_active']);
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_public_shop_links');
    }
};




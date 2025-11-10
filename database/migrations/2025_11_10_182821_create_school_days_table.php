<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->enum('type', ['school_day', 'holiday', 'midterm_break', 'weekend', 'custom_off_day'])->default('school_day');
            $table->string('name')->nullable(); // e.g., "New Year", "Midterm Break Day 1"
            $table->text('description')->nullable();
            $table->boolean('is_kenyan_holiday')->default(false); // Auto-generated Kenyan holidays
            $table->boolean('is_custom')->default(false); // User-added custom days
            $table->timestamps();
            
            $table->index('date');
            $table->index('type');
            $table->index(['date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_days');
    }
};

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
        Schema::create('time_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Primary", "Secondary", "Lower Primary", etc.
            $table->string('level')->nullable(); // e.g., "Grade 1-3", "Form 1-4"
            $table->integer('period_number'); // 1, 2, 3, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(40);
            $table->boolean('is_break')->default(false);
            $table->string('break_type')->nullable(); // 'morning_break', 'lunch', 'afternoon_break'
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['name', 'period_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_periods');
    }
};

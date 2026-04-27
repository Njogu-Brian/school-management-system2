<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_generated_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('timetable_generation_runs')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('layout_period_id')->constrained('timetable_layout_periods')->cascadeOnDelete();
            $table->string('day');
            $table->enum('slot_type', ['lesson', 'break', 'activity'])->default('lesson');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('room')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'stream_id', 'layout_period_id'], 'run_stream_layout_unique');
            $table->index(['run_id', 'day'], 'run_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_generated_slots');
    }
};


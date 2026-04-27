<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slot_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('timetable_generation_runs')->cascadeOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('layout_period_id')->constrained('timetable_layout_periods')->cascadeOnDelete();
            $table->string('day');
            $table->foreignId('locked_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('locked_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('locked_label')->nullable();
            $table->string('locked_room')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['run_id', 'stream_id', 'layout_period_id'], 'lock_run_stream_layout_unique');
        });

        Schema::create('timetable_slot_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->nullable()->constrained('timetable_generation_runs')->nullOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('layout_period_id')->constrained('timetable_layout_periods')->cascadeOnDelete();
            $table->string('day');
            $table->date('effective_date')->nullable(); // null = applies to weekly pattern; set = substitution for that date
            $table->enum('slot_type', ['lesson', 'break', 'activity'])->default('lesson');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('room')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stream_id', 'effective_date'], 'override_stream_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slot_overrides');
        Schema::dropIfExists('timetable_slot_locks');
    }
};


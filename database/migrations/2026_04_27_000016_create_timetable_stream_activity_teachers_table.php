<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_stream_activity_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_requirement_id')
                ->constrained('timetable_stream_activity_requirements')
                ->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedSmallInteger('periods_per_week')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['activity_requirement_id', 'staff_id'], 'stream_activity_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_stream_activity_teachers');
    }
};


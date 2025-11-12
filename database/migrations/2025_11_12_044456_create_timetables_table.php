<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('day'); // Monday, Tuesday, etc.
            $table->integer('period'); // 1, 2, 3, etc.
            $table->string('start_time'); // 08:00
            $table->string('end_time'); // 08:40
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete(); // Teacher for this period
            $table->string('room')->nullable(); // Room number
            $table->boolean('is_break')->default(false);
            $table->json('meta')->nullable(); // Additional data
            $table->timestamps();

            $table->unique(['classroom_id', 'academic_year_id', 'term_id', 'day', 'period'], 'timetable_unique');
            $table->index(['staff_id', 'academic_year_id', 'term_id', 'day', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};

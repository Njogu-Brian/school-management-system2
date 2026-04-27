<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_stream_subject_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedSmallInteger('periods_per_week')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['stream_id', 'academic_year_id', 'term_id', 'subject_id', 'staff_id'], 'stream_subject_teacher_unique');
            $table->index(['academic_year_id', 'term_id'], 'stream_subject_teacher_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_stream_subject_teachers');
    }
};


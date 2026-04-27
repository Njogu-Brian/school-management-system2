<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_stream_activity_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('periods_per_week')->default(0);
            $table->boolean('is_teacher_assigned')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['stream_id', 'academic_year_id', 'term_id', 'name'], 'stream_activity_req_unique');
            $table->index(['academic_year_id', 'term_id'], 'stream_activity_req_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_stream_activity_requirements');
    }
};


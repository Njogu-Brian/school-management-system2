<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_stream_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('streams')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('timetable_layout_templates')->cascadeOnDelete();
            $table->json('overrides')->nullable(); // reserved for future per-stream tweaks
            $table->timestamps();

            $table->unique(['stream_id', 'academic_year_id', 'term_id'], 'stream_layout_unique');
            $table->index(['academic_year_id', 'term_id'], 'stream_layout_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_stream_layouts');
    }
};


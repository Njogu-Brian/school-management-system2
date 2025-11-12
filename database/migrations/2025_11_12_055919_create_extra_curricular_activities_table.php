<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_curricular_activities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Parade", "Sports", "Music"
            $table->string('type')->default('activity'); // activity, parade, assembly, etc.
            $table->string('day'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('period')->nullable(); // Period number if applicable
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->json('classroom_ids')->nullable(); // Array of classroom IDs
            $table->json('staff_ids')->nullable(); // Array of staff IDs responsible
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('repeat_weekly')->default(true);
            $table->timestamps();

            $table->index(['academic_year_id', 'term_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_curricular_activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_layout_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('timetable_layout_templates')->cascadeOnDelete();
            $table->string('day'); // Monday..Sunday
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('start_time'); // "08:00"
            $table->string('end_time');   // "08:40"
            $table->enum('slot_type', ['lesson', 'break', 'activity'])->default('lesson');
            $table->string('label')->nullable(); // Break/Lunch/Assembly
            $table->boolean('can_combine')->default(false);
            $table->unsignedTinyInteger('combine_size')->default(2); // if can_combine, how many consecutive slots
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'day', 'sort_order'], 'tpl_periods_day_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_layout_periods');
    }
};


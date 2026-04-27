<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_layout_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('days_active')->nullable(); // e.g. ["Monday","Tuesday",...]
            $table->string('default_start_time')->nullable(); // "08:00"
            $table->string('default_end_time')->nullable();   // "16:00"
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_layout_templates');
    }
};


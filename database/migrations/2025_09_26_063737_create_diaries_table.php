<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('staff')->cascadeOnDelete();
            $table->date('week_start');
            $table->json('entries'); // {day: {topic, activities, notes}}
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('diaries');
    }
};

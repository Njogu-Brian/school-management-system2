<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exam_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->enum('qtype',['mcq','structured','essay','rubric']);
            $table->longText('question');
            $table->json('options')->nullable();
            $table->json('correct_answers')->nullable();
            $table->decimal('marks', 6, 2)->unsigned()->default(1);
            $table->json('rubric')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('exam_items');
    }
};

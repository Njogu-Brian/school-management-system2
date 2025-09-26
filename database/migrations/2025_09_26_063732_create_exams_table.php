<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., CAT 1
            $table->enum('type', ['cat','midterm','endterm','sba','mock','quiz']);
            $table->enum('modality', ['physical','online']);
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('max_marks', 6, 2)->unsigned()->default(100);
            $table->decimal('weight', 5, 2)->unsigned()->default(100);
            $table->enum('status', ['draft','open','marking','moderation','approved','published','locked'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('exams');
    }
};

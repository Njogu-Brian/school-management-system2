<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('score_raw', 7, 2)->unsigned()->nullable();
            $table->decimal('score_moderated', 7, 2)->unsigned()->nullable();
            $table->string('grade_label')->nullable();
            $table->string('pl_level')->nullable();
            $table->string('remark')->nullable();
            $table->enum('status', ['draft','submitted','approved'])->default('draft');
            $table->json('audit')->nullable();
            $table->timestamps();
            $table->unique(['exam_id','student_id','subject_id'],'uniq_exam_student_subject');
        });
    }

    public function down(): void {
        Schema::dropIfExists('exam_marks');
    }
};

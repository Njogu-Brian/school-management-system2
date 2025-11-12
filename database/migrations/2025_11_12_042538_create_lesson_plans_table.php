<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_of_work_id')->nullable()->constrained('schemes_of_work')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('substrand_id')->nullable()->constrained('cbc_substrands')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('title');
            $table->string('lesson_number')->nullable(); // e.g., Lesson 1, Week 1
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->integer('duration_minutes')->default(40);
            $table->text('learning_objectives')->nullable(); // JSON array
            $table->text('learning_outcomes')->nullable(); // What learners will achieve
            $table->text('core_competencies')->nullable(); // JSON array
            $table->text('values')->nullable(); // JSON array
            $table->text('pclc')->nullable(); // Pertinent and Contemporary Issues
            $table->text('learning_resources')->nullable(); // JSON array
            $table->text('introduction')->nullable();
            $table->text('lesson_development')->nullable(); // Main content
            $table->text('activities')->nullable(); // JSON array of activities
            $table->text('assessment')->nullable(); // How learning will be assessed
            $table->text('conclusion')->nullable();
            $table->text('reflection')->nullable(); // Teacher's reflection after lesson
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->enum('execution_status', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->text('challenges')->nullable();
            $table->text('improvements')->nullable();
            $table->json('attendance_data')->nullable(); // Who attended
            $table->json('assessment_results')->nullable(); // Quick assessment results
            $table->timestamps();

            $table->index(['subject_id', 'classroom_id', 'academic_year_id', 'term_id'], 'lesson_plans_idx');
            $table->index('planned_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};

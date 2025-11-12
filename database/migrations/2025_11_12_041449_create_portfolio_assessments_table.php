<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->enum('portfolio_type', ['project', 'practical', 'creative', 'research', 'group_work', 'other'])->default('project');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('evidence_files')->nullable(); // Array of file paths
            $table->json('rubric_scores')->nullable(); // Competency scores
            $table->decimal('total_score', 6, 2)->nullable();
            $table->foreignId('performance_level_id')->nullable()->constrained('cbc_performance_levels')->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('assessment_date')->nullable();
            $table->enum('status', ['draft', 'submitted', 'assessed', 'published'])->default('draft');
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_assessments');
    }
};

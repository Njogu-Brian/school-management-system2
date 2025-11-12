<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schemes_of_work')) {
            Schema::dropIfExists('schemes_of_work');
        }
        
        Schema::create('schemes_of_work', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('total_lessons')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->json('strands_coverage')->nullable(); // Which strands are covered
            $table->json('substrands_coverage')->nullable(); // Which substrands are covered
            $table->text('general_remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'classroom_id', 'academic_year_id', 'term_id'], 'schemes_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes_of_work');
    }
};

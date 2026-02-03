<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->date('assessment_date')->nullable();
            $table->date('week_ending')->nullable();
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('assessment_type')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('out_of', 8, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('academic_group')->nullable();
            $table->timestamps();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');

            $table->index(['week_ending', 'classroom_id']);
            $table->index(['subject_id', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_academic_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->date('enrollment_date');
            $table->date('completion_date')->nullable();
            $table->enum('promotion_status', ['promoted', 'retained', 'demoted', 'transferred', 'graduated'])->nullable();
            $table->decimal('final_grade', 5, 2)->nullable(); // Overall GPA or final grade
            $table->integer('class_position')->nullable();
            $table->integer('stream_position')->nullable();
            $table->text('remarks')->nullable();
            $table->text('teacher_comments')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedBigInteger('promoted_by')->nullable();
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('set null');
            $table->foreign('promoted_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['student_id', 'academic_year_id']);
            $table->index('is_current');
            $table->index('enrollment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academic_history');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_reports', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending');
            $table->enum('campus', ['lower', 'upper'])->nullable();
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->unsignedInteger('total_learners')->nullable();
            $table->unsignedInteger('frequent_absentees')->nullable();
            $table->enum('discipline_level', ['Excellent', 'Good', 'Fair', 'Poor'])->nullable();
            $table->enum('homework_completion', ['High', 'Medium', 'Low'])->nullable();
            $table->unsignedInteger('learners_struggling')->nullable();
            $table->unsignedInteger('learners_improved')->nullable();
            $table->unsignedInteger('parents_to_contact')->nullable();
            $table->enum('classroom_condition', ['Good', 'Fair', 'Poor'])->nullable();
            $table->text('notes')->nullable();
            $table->string('academic_group')->nullable();
            $table->timestamps();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('class_teacher_id')->references('id')->on('staff')->onDelete('set null');
            $table->index(['week_ending', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_reports');
    }
};

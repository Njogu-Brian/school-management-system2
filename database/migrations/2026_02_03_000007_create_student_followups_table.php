<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_followups', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending');
            $table->enum('campus', ['lower', 'upper'])->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('classroom_id');
            $table->boolean('academic_concern')->nullable();
            $table->boolean('behavior_concern')->nullable();
            $table->text('action_taken')->nullable();
            $table->boolean('parent_contacted')->nullable();
            $table->enum('progress_status', ['Improving', 'Same', 'Worse'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->index(['week_ending', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_followups');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_reports', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending');
            $table->enum('campus', ['lower', 'upper'])->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('classroom_id');
            $table->text('topics_covered')->nullable();
            $table->enum('syllabus_status', ['On Track', 'Slightly Behind', 'Behind'])->nullable();
            $table->decimal('strong_percent', 5, 2)->nullable();
            $table->decimal('average_percent', 5, 2)->nullable();
            $table->decimal('struggling_percent', 5, 2)->nullable();
            $table->boolean('homework_given')->nullable();
            $table->boolean('test_done')->nullable();
            $table->boolean('marking_done')->nullable();
            $table->string('main_challenge')->nullable();
            $table->string('support_needed')->nullable();
            $table->string('academic_group')->nullable();
            $table->timestamps();

            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->index(['week_ending', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_reports');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_report_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('question_id');
            $table->longText('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('academic_report_submissions')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('academic_report_questions')->cascadeOnDelete();
            $table->unique(['submission_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_report_answers');
    }
};


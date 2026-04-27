<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_report_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('type'); // short_text|long_text|single_select|multi_select|file_upload
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable(); // for select options, constraints, etc.
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('academic_report_templates')->cascadeOnDelete();
            $table->index(['template_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_report_questions');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_report_submissions')) {
            return;
        }
        Schema::create('academic_report_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->json('submitted_for')->nullable(); // class_context info, role info, etc.
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('academic_report_templates')->cascadeOnDelete();
            // No FK to users (some deployments use non-FK-compatible auth table engines).
            $table->index(['template_id', 'created_at']);
            $table->index(['submitted_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_report_submissions');
    }
};


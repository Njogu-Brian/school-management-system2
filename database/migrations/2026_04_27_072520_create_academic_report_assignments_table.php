<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_report_assignments')) {
            return;
        }
        Schema::create('academic_report_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('target_type'); // role|user|class_context
            $table->string('role_name')->nullable(); // when target_type=role
            $table->unsignedBigInteger('user_id')->nullable(); // when target_type=user
            $table->unsignedBigInteger('classroom_id')->nullable(); // when target_type=class_context
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();

            // Keep only the template FK (other FK constraints may fail in some deployments if core tables are non-InnoDB).
            $table->foreign('template_id')->references('id')->on('academic_report_templates')->cascadeOnDelete();

            $table->index(['template_id', 'target_type']);
            $table->index(['role_name']);
            $table->index(['user_id']);
            // Custom short index name to avoid MySQL identifier length limit (64 chars).
            $table->index(['classroom_id', 'stream_id', 'subject_id'], 'ara_cls_stream_subj_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_report_assignments');
    }
};


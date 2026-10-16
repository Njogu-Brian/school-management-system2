<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_mark_change_batches')) {
            Schema::create('exam_mark_change_batches', function (Blueprint $table) {
                $table->id();
                $table->string('status', 20)->default('open')->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exam_mark_change_items')) {
            Schema::create('exam_mark_change_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('exam_mark_change_batches')->cascadeOnDelete();
                $table->unsignedBigInteger('exam_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('editor_id')->nullable();
                $table->string('editor_name');
                $table->string('student_name');
                $table->string('exam_name');
                $table->string('subject_name')->nullable();
                $table->string('classroom_name')->nullable();
                $table->decimal('score_from', 8, 2);
                $table->decimal('score_to', 8, 2);
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->unique(['batch_id', 'exam_id', 'student_id'], 'exam_mark_change_items_unique');
                $table->index(['batch_id', 'exam_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_mark_change_items');
        Schema::dropIfExists('exam_mark_change_batches');
    }
};

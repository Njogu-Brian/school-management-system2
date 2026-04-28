<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->unsignedBigInteger('staff_id');
            $table->timestamps();

            // One class teacher per classroom+stream slot.
            $table->unique(['classroom_id', 'stream_id'], 'cls_stream_class_teacher_unique');
            $table->index(['staff_id']);

            $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_assignments');
    }
};


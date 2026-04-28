<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_teacher_classroom_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senior_teacher_id'); // users.id
            $table->unsignedBigInteger('classroom_id');      // classrooms.id
            $table->timestamps();

            // One senior teacher per classroom (to avoid scope overlap confusion).
            $table->unique(['classroom_id']);
            $table->index(['senior_teacher_id']);

            $table->foreign('senior_teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_teacher_classroom_assignments');
    }
};


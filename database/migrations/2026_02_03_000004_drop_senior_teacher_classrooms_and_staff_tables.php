<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Senior teacher scope is now campus-only; drop per-classroom and per-staff assignment tables.
     */
    public function up(): void
    {
        Schema::dropIfExists('senior_teacher_staff');
        Schema::dropIfExists('senior_teacher_classrooms');
    }

    public function down(): void
    {
        Schema::create('senior_teacher_classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senior_teacher_id');
            $table->unsignedBigInteger('classroom_id');
            $table->timestamps();
            $table->foreign('senior_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->unique(['senior_teacher_id', 'classroom_id']);
        });

        Schema::create('senior_teacher_staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senior_teacher_id');
            $table->unsignedBigInteger('staff_id');
            $table->timestamps();
            $table->foreign('senior_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->unique(['senior_teacher_id', 'staff_id']);
        });
    }
};

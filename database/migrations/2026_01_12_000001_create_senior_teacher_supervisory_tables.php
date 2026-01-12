<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Senior Teacher to Classroom supervisory assignments
        Schema::create('senior_teacher_classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senior_teacher_id'); // references users.id
            $table->unsignedBigInteger('classroom_id');
            $table->timestamps();

            $table->foreign('senior_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            
            // Prevent duplicate assignments
            $table->unique(['senior_teacher_id', 'classroom_id']);
        });

        // Senior Teacher to Staff supervisory assignments
        Schema::create('senior_teacher_staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senior_teacher_id'); // references users.id
            $table->unsignedBigInteger('staff_id'); // references staff.id
            $table->timestamps();

            $table->foreign('senior_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            
            // Prevent duplicate assignments
            $table->unique(['senior_teacher_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senior_teacher_staff');
        Schema::dropIfExists('senior_teacher_classrooms');
    }
};


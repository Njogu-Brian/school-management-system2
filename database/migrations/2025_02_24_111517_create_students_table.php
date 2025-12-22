<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->string('nemis_number')->nullable();
            $table->string('knec_assessment_number')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('birth_certificate_path')->nullable();
            $table->unsignedBigInteger('sibling_id')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('parent_id')->references('id')->on('parent_info')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('student_categories')->onDelete('set null');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('set null');
            $table->foreign('sibling_id')->references('id')->on('students')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};

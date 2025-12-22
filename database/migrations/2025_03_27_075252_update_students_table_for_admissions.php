<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStudentsTableForAdmissions extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Adding new fields for student admissions
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->string('nemis_number')->nullable();
            $table->string('knec_assessment_number')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('birth_certificate_path')->nullable();
            $table->unsignedBigInteger('sibling_id')->nullable();
            
            // Foreign Keys
            $table->foreign('category_id')->references('id')->on('student_categories')->onDelete('set null');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('set null');
            $table->foreign('sibling_id')->references('id')->on('students')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['stream_id']);
            $table->dropForeign(['sibling_id']);

            $table->dropColumn([
                'first_name', 'middle_name', 'last_name', 'dob', 'gender',
                'category_id', 'classroom_id', 'stream_id',
                'nemis_number', 'knec_assessment_number', 
                'previous_school', 'photo_path', 'birth_certificate_path',
                'sibling_id'
            ]);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles (e.g. Teacher, Driver, Admin Staff)
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Job Titles (linked to department)
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });

        // Custom fields (e.g. passport_no, salary, blood_group)
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // e.g. staff, student, finance
            $table->string('label');  // e.g. Blood Group
            $table->string('field_key'); // e.g. blood_group
            $table->string('field_type')->default('text'); // text, number, email, date, file
            $table->boolean('required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('staff_roles');
    }
};

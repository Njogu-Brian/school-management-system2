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
        Schema::create('student_medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->enum('record_type', ['vaccination', 'checkup', 'medication', 'incident', 'certificate', 'other'])->default('other');
            $table->date('record_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('clinic_hospital')->nullable();
            $table->string('medication_name')->nullable();
            $table->text('medication_dosage')->nullable();
            $table->date('medication_start_date')->nullable();
            $table->date('medication_end_date')->nullable();
            $table->string('vaccination_name')->nullable();
            $table->date('vaccination_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('certificate_type')->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['student_id', 'record_type']);
            $table->index('record_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_medical_records');
    }
};

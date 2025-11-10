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
        Schema::create('student_disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('incident_type'); // e.g., 'misconduct', 'bullying', 'academic_dishonesty', 'violence', 'theft', etc.
            $table->string('severity')->default('minor'); // minor, moderate, major, severe
            $table->text('description');
            $table->text('witnesses')->nullable();
            $table->enum('action_taken', ['warning', 'verbal_warning', 'written_warning', 'detention', 'suspension', 'expulsion', 'parent_meeting', 'counseling', 'other'])->nullable();
            $table->text('action_details')->nullable();
            $table->date('action_date')->nullable();
            $table->text('improvement_plan')->nullable();
            $table->boolean('parent_notified')->default(false);
            $table->date('parent_notification_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->boolean('resolved')->default(false);
            $table->date('resolved_date')->nullable();
            $table->unsignedBigInteger('reported_by'); // staff member who reported
            $table->unsignedBigInteger('action_taken_by')->nullable(); // staff member who took action
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('action_taken_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['student_id', 'incident_date']);
            $table->index('severity');
            $table->index('resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_disciplinary_records');
    }
};

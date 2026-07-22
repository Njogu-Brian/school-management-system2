<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_concerns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('category', 40); // financial, academic, teacher, transport, meals, administration
            $table->text('description');
            $table->string('status', 30)->default('open'); // open, in_progress, resolved, closed
            $table->foreignId('raised_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'status']);
        });

        Schema::create('student_concern_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_concern_id')->constrained('student_concerns')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_concern_id', 'staff_id'], 'concern_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_concern_staff');
        Schema::dropIfExists('student_concerns');
    }
};

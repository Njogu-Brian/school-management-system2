<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_fee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('votehead_id')->constrained('voteheads')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['votehead_id', 'student_id', 'attendance_date'], 'activity_fee_attendance_unique_session');
            $table->index(['votehead_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_fee_attendances');
    }
};

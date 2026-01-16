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
        Schema::create('swimming_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->decimal('session_cost', 10, 2)->nullable()->comment('Per-visit cost at time of attendance');
            $table->boolean('termly_fee_covered')->default(false)->comment('Whether covered by termly optional fee');
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            
            // One attendance record per student per date
            $table->unique(['student_id', 'attendance_date'], 'unique_student_date');
            $table->index(['classroom_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swimming_attendance');
    }
};

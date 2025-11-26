<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('hostel_rooms')->cascadeOnDelete();
            $table->string('bed_number')->nullable();
            $table->date('allocation_date');
            $table->date('deallocation_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('hostel_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('allocation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_allocations');
    }
};


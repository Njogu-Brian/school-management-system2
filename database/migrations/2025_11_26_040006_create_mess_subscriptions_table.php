<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mess_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->enum('meal_plan', ['full', 'breakfast_only', 'lunch_dinner', 'custom'])->default('full');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->json('custom_meals')->nullable(); // For custom meal plans
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('hostel_id');
            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mess_subscriptions');
    }
};

